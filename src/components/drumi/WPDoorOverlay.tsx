/**
 * WPDoorOverlay — Super-smooth WordPress door intro component
 * ────────────────────────────────────────────────────────────
 * Props:
 *  phase       — animation phase driven by WPOverlay
 *  onEnter     — called when user clicks the CTA button (click-trigger mode)
 *  onArrived   — called when the camera walk-through completes
 *  buttonText  — CTA label (configurable from WP plugin settings)
 *  triggerMode — "auto" (no button shown) | "click" (button visible, user triggers)
 *
 * Visuals:
 *  • frame-surround-clean.png — RGBA, arch hole is alpha=0 → WP page visible through it
 *  • panel-left/right-clean.png — door leaves that swing open with 3D rotateY
 *  • Gold glow blooms through the arch as doors open
 *  • On "entering" — smooth cinematic zoom, arch + panels fade, onArrived fires
 */

import { useRef, useEffect } from "react";
import panelLeftImg     from "../../assets/drumi/panel-left-clean.png";
import panelRightImg    from "../../assets/drumi/panel-right-clean.png";
import frameSurroundImg from "../../assets/drumi/frame-surround-clean.png";
import { computeCover, toPx, useNaturalSize, useElementSize } from "../../hooks/useCoverGeometry";

export type WPPhase = "idle" | "opening" | "entering" | "done";

interface WPDoorOverlayProps {
  phase:        WPPhase;
  onEnter:      () => void;
  onArrived:    () => void;
  buttonText?:  string;
  triggerMode?: "auto" | "click";
}

// ── Exact doorway geometry (fractions of 1376×768 frame image) ──────────────
const DOOR_GEO = {
  left:   380 / 1376,
  right:  996 / 1376,
  top:    160 / 768,
  bottom: 695 / 768,
};

// ── Easing ───────────────────────────────────────────────────────────────────
const ENTER_MS  = 1600; // cinematic walk-through duration
const easeInOut = (t: number) => t < 0.5 ? 4*t*t*t : 1 - Math.pow(-2*t + 2, 3) / 2;
const easeOut3  = (t: number) => 1 - Math.pow(1 - t, 3);

export function WPDoorOverlay({
  phase,
  onEnter,
  onArrived,
  buttonText  = "JOIN THE JOURNEY",
  triggerMode = "auto",
}: WPDoorOverlayProps) {
  const stageRef  = useRef<HTMLDivElement>(null);
  const sceneRef  = useRef<HTMLDivElement>(null);
  const frameRef  = useRef<HTMLDivElement>(null);
  const panelsRef = useRef<HTMLDivElement>(null);
  const glowRef   = useRef<HTMLDivElement>(null);
  const seamRef   = useRef<HTMLDivElement>(null);
  const ctaRef    = useRef<HTMLDivElement>(null);

  // Geometry based on frame image (1376×768)
  const natural = useNaturalSize(frameSurroundImg);
  const { w: cw, h: ch } = useElementSize(stageRef);
  const geo = natural && cw && ch
    ? computeCover(cw, ch, natural.w, natural.h)
    : null;

  let rect: { left: number; top: number; width: number; height: number } | null = null;
  let center = { x: cw / 2, y: ch / 2 };

  if (geo) {
    const tl = toPx(geo, DOOR_GEO.left,  DOOR_GEO.top);
    const br = toPx(geo, DOOR_GEO.right, DOOR_GEO.bottom);
    rect   = { left: tl.x, top: tl.y, width: br.x - tl.x, height: br.y - tl.y };
    center = { x: (tl.x + br.x) / 2, y: (tl.y + br.y) / 2 };
  }

  const rectRef   = useRef(rect);   rectRef.current   = rect;
  const centerRef = useRef(center); centerRef.current = center;
  const sizeRef   = useRef({ cw, ch }); sizeRef.current = { cw, ch };

  // ── Cinematic camera walk-through (rAF) ───────────────────────────────────
  useEffect(() => {
    if (phase !== "entering") return;

    const r      = rectRef.current;
    const P      = centerRef.current;
    const { cw: W, ch: H } = sizeRef.current;
    const scene  = sceneRef.current;
    const frame  = frameRef.current;
    const panels = panelsRef.current;
    const glow   = glowRef.current;
    const cta    = ctaRef.current;

    if (!r || !scene || !frame || !panels || !glow) { onArrived(); return; }

    // Calculate scale needed for the doorway to swallow the entire viewport
    const need = Math.max(
      P.x / Math.max(1, P.x - r.left),
      (W - P.x) / Math.max(1, r.left + r.width - P.x),
      P.y / Math.max(1, P.y - r.top),
      (H - P.y) / Math.max(1, r.top + r.height - P.y),
    );
    const sEnd = Math.min(4.0, need * 1.08);

    const start = performance.now();
    let raf = 0;

    const tick = (now: number) => {
      const t = Math.min(1, (now - start) / ENTER_MS);
      const e = easeInOut(t);
      const s = 1 + (sEnd - 1) * e;

      // ── Zoom scene toward doorway ──────────────────────────────────────
      scene.style.transform = `scale(${s})`;

      // ── Fade arch walls + panels (starts at 30%, gone by 65%) ─────────
      const wallFade = Math.max(0, 1 - easeOut3(Math.max(0, (t - 0.3) / 0.35)));
      frame.style.opacity  = String(wallFade);
      panels.style.opacity = String(wallFade);
      if (cta) cta.style.opacity = "0";

      // ── Glow: builds early, fades mid-way ─────────────────────────────
      glow.style.opacity = String(
        t < 0.35
          ? easeOut3(t / 0.35) * 0.9
          : Math.max(0, 0.9 * (1 - easeOut3((t - 0.35) / 0.65)))
      );

      if (t < 1) {
        raf = requestAnimationFrame(tick);
      } else {
        // Ensure clean end state
        scene.style.transform = `scale(${sEnd})`;
        frame.style.opacity   = "0";
        panels.style.opacity  = "0";
        glow.style.opacity    = "0";
        onArrived();
      }
    };

    raf = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(raf);
  }, [phase, onArrived]);

  const origin = `${center.x}px ${center.y}px`;
  const isOpen = phase === "opening" || phase === "entering";

  if (phase === "done") return null;

  return (
    <div
      ref={stageRef}
      className="fixed inset-0 w-full h-full overflow-hidden select-none"
      style={{ background: "transparent", perspective: "1400px" }}
    >
      {/* ── Scaled scene (everything inside scales together) ─────────── */}
      <div
        ref={sceneRef}
        className="absolute inset-0 will-change-transform"
        style={{ transformOrigin: origin }}
      >
        {/* ── Door panels (z-10) ──────────────────────────────────────── */}
        {rect && geo && (
          <div ref={panelsRef} className="absolute inset-0 pointer-events-none z-10">
            {/* Left door leaf */}
            <div
              className="absolute"
              style={{
                left:   rect.left,
                top:    rect.top,
                width:  rect.width / 2 + 1.5,
                height: rect.height,
                transformOrigin: "left center",
                transform: isOpen
                  ? "perspective(1200px) rotateY(-98deg) scaleX(0.88)"
                  : "perspective(1200px) rotateY(0deg)",
                filter: isOpen
                  ? "brightness(0.5) drop-shadow(6px 0 20px rgba(0,0,0,0.4))"
                  : "brightness(1) drop-shadow(2px 0 8px rgba(0,0,0,0.15))",
                transition:
                  "transform 1200ms cubic-bezier(0.34, 1.2, 0.64, 1), filter 1200ms ease",
                willChange: "transform",
              }}
            >
              <img
                src={panelLeftImg}
                alt=""
                className="w-full h-full object-fill pointer-events-none block"
              />
            </div>

            {/* Right door leaf */}
            <div
              className="absolute"
              style={{
                left:   rect.left + rect.width / 2 - 1.5,
                top:    rect.top,
                width:  rect.width / 2 + 1.5,
                height: rect.height,
                transformOrigin: "right center",
                transform: isOpen
                  ? "perspective(1200px) rotateY(98deg) scaleX(0.88)"
                  : "perspective(1200px) rotateY(0deg)",
                filter: isOpen
                  ? "brightness(0.5) drop-shadow(-6px 0 20px rgba(0,0,0,0.4))"
                  : "brightness(1) drop-shadow(-2px 0 8px rgba(0,0,0,0.15))",
                transition:
                  "transform 1200ms cubic-bezier(0.34, 1.2, 0.64, 1), filter 1200ms ease",
                willChange: "transform",
              }}
            >
              <img
                src={panelRightImg}
                alt=""
                className="w-full h-full object-fill pointer-events-none block"
              />
            </div>

            {/* Center seam glow (pre-open flicker) */}
            {!isOpen && rect && (
              <div
                ref={seamRef}
                className="absolute pointer-events-none animate-pulse"
                style={{
                  left:   center.x - 3,
                  top:    rect.top,
                  width:  6,
                  height: rect.height,
                  background:
                    "linear-gradient(to bottom, transparent 0%, rgba(255,220,140,0.9) 20%, rgba(255,235,160,1) 50%, rgba(255,220,140,0.9) 80%, transparent 100%)",
                  filter: "blur(3px)",
                }}
              />
            )}
          </div>
        )}

        {/* ── Stone arch frame (z-20) — transparent hole = WP site visible ─ */}
        <div
          ref={frameRef}
          className="absolute inset-0 pointer-events-none z-20"
          style={{
            backgroundImage:    `url(${frameSurroundImg})`,
            backgroundSize:     geo ? `${geo.dispW}px ${geo.dispH}px` : "cover",
            backgroundPosition: geo ? `${geo.offX}px ${geo.offY}px` : "center",
          }}
        />

        {/* ── CTA Button (z-40) ─────────────────────────────────────────── */}
        <div
          ref={ctaRef}
          className={`absolute bottom-[6%] sm:bottom-[5%] left-0 right-0 flex flex-col items-center z-40
            transition-all duration-500 ease-out
            ${isOpen ? "opacity-0 translate-y-4 pointer-events-none" : "opacity-100 translate-y-0 pointer-events-auto"}
            ${triggerMode === "auto" ? "pointer-events-none" : ""}
          `}
        >
          {triggerMode === "click" ? (
            /* Interactive button in click-trigger mode */
            <button
              type="button"
              onClick={onEnter}
              className="group relative px-8 py-3.5 sm:px-10 sm:py-4 rounded-full
                bg-[#9a7470] hover:bg-[#7f5e5b] active:scale-[0.97]
                text-[#fbf7f4] font-['Cinzel',serif] text-[0.76rem] sm:text-[0.86rem]
                uppercase tracking-[0.28em] font-medium cursor-pointer
                shadow-[0_8px_32px_rgba(100,55,50,0.45),0_2px_8px_rgba(0,0,0,0.2)]
                border border-[#c4a29f]/50
                transition-all duration-300
                flex items-center gap-2.5"
            >
              <span>{buttonText}</span>
              <svg
                viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.8"
                className="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1"
              >
                <path d="M4 10H16M16 10L11 5M16 10L11 15" />
              </svg>
              {/* Shimmer */}
              <span className="absolute inset-0 rounded-full overflow-hidden pointer-events-none">
                <span className="absolute inset-0 bg-gradient-to-r from-transparent via-white/15 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700" />
              </span>
            </button>
          ) : (
            /* Auto mode — decorative label only */
            <div className="flex items-center gap-3 text-[#9a7470]/80">
              <div className="h-px w-8 bg-current opacity-50" />
              <span className="font-['Cinzel',serif] text-[0.62rem] uppercase tracking-[0.35em]">
                {buttonText}
              </span>
              <div className="h-px w-8 bg-current opacity-50" />
            </div>
          )}

          <span className="mt-2.5 font-['Cinzel',serif] text-[0.58rem] sm:text-[0.64rem]
            uppercase tracking-[0.32em] text-[#b09070]/70 drop-shadow-sm">
            STEP INTO YOUR INNER WORLD
          </span>
        </div>
      </div>

      {/* ── Gold glow bloom (outside scaled scene for correct sizing) ─────── */}
      <div
        ref={glowRef}
        className="absolute inset-0 pointer-events-none z-50 opacity-0"
        style={{
          background: `radial-gradient(ellipse 40% 45% at ${center.x}px ${center.y}px,
            rgba(255,242,198,0.85) 0%,
            rgba(248,215,140,0.4) 35%,
            rgba(235,190,100,0.12) 65%,
            transparent 100%)`,
        }}
      />
    </div>
  );
}
