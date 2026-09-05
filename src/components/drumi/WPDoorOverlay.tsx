/**
 * WPDoorOverlay
 * ─────────────
 * A WordPress-specific door intro overlay.
 *
 * Key difference from DrumiThreshold:
 *  - NO inner sanctuary background image → the hole in frame-surround-clean.png
 *    is truly transparent, so the WORDPRESS WEBSITE shows through the arch.
 *  - On "entering" phase → scene scales up (zoom toward the doorway), then
 *    the entire overlay fades out → the WP site is already fully visible.
 *  - postMessage({ type: "door-animation-done" }) tells the WP page to remove overlay.
 */

import { useRef, useEffect } from "react";
import panelLeftImg  from "../../assets/drumi/panel-left-clean.png";
import panelRightImg from "../../assets/drumi/panel-right-clean.png";
import frameSurroundImg from "../../assets/drumi/frame-surround-clean.png";
import { computeCover, toPx, useNaturalSize, useElementSize } from "../../hooks/useCoverGeometry";

export type WPPhase = "idle" | "opening" | "entering" | "done";

interface WPDoorOverlayProps {
  phase: WPPhase;
  onArrived: () => void;
}

// Exact doorway geometry in fractions of the 1376 × 768 frame artwork
const DOOR_GEO = {
  left:   380 / 1376,
  right:  996 / 1376,
  top:    160 / 768,
  bottom: 695 / 768,
};

const ENTER_MS = 1400;
const easeInOut = (t: number) => (t < 0.5 ? 4*t*t*t : 1 - Math.pow(-2*t+2, 3) / 2);

export function WPDoorOverlay({ phase, onArrived }: WPDoorOverlayProps) {
  const stageRef  = useRef<HTMLDivElement>(null);
  const sceneRef  = useRef<HTMLDivElement>(null);
  const frameRef  = useRef<HTMLDivElement>(null);
  const panelsRef = useRef<HTMLDivElement>(null);
  const glowRef   = useRef<HTMLDivElement>(null);

  // Geometry is based on the FRAME image dimensions (1376×768)
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

  // ── Camera walk-through animation ────────────────────────────────────────
  useEffect(() => {
    if (phase !== "entering") return;

    const r  = rectRef.current;
    const P  = centerRef.current;
    const { cw: curW, ch: curH } = sizeRef.current;
    const scene  = sceneRef.current;
    const frame  = frameRef.current;
    const panels = panelsRef.current;
    const glow   = glowRef.current;

    if (!r || !scene || !frame || !panels || !glow) { onArrived(); return; }

    // How far we need to zoom so the doorway swallows the viewport
    const need = Math.max(
      P.x / Math.max(1, P.x - r.left),
      (curW - P.x) / Math.max(1, r.left + r.width - P.x),
      P.y / Math.max(1, P.y - r.top),
      (curH - P.y) / Math.max(1, r.top + r.height - P.y),
    );
    const sEnd = Math.min(3.5, need * 1.1);

    const start = performance.now();
    let raf = 0;

    const tick = (now: number) => {
      const t = Math.min(1, (now - start) / ENTER_MS);
      const e = easeInOut(t);
      const s = 1 + (sEnd - 1) * e;

      // Zoom the scene toward the doorway center
      scene.style.transform = `scale(${s})`;

      // Fade out the arch walls + door panels as we fly through
      const wallOp = String(Math.max(0, 1 - easeInOut(Math.min(1, t / 0.6))));
      frame.style.opacity  = wallOp;
      panels.style.opacity = wallOp;

      // Glow peak at 40% then dies
      glow.style.opacity = String(
        t < 0.4 ? 0.4 + 0.6 * (t / 0.4) : Math.max(0, 1 - (t - 0.4) / 0.6)
      );

      if (t < 1) {
        raf = requestAnimationFrame(tick);
      } else {
        onArrived();
      }
    };

    raf = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(raf);
  }, [phase, onArrived]);

  const origin = `${center.x}px ${center.y}px`;
  const isOpen = phase === "opening" || phase === "entering";

  // After animation done — render nothing (overlay will be hidden by WP plugin)
  if (phase === "done") return null;

  return (
    <div
      ref={stageRef}
      className="fixed inset-0 w-full h-full overflow-hidden select-none"
      style={{
        // ✅ Transparent background — WP site shows through the arch hole
        background: "transparent",
        perspective: "1500px",
      }}
    >
      {/* ── Scaled scene ───────────────────────────────────────────────── */}
      <div
        ref={sceneRef}
        className="absolute inset-0"
        style={{ transformOrigin: origin }}
      >
        {/* Door panels — swing open to reveal more of the WP site */}
        {rect && geo && (
          <div ref={panelsRef} className="absolute inset-0 pointer-events-none z-10">
            {/* Left panel */}
            <div
              className="absolute transition-all duration-[1100ms]"
              style={{
                left: rect.left,
                top: rect.top,
                width:  rect.width / 2 + 1,
                height: rect.height,
                transformOrigin: "left center",
                transform: isOpen ? "perspective(900px) rotateY(-96deg) scaleX(0.9)" : "perspective(900px) rotateY(0deg)",
                filter: isOpen ? "brightness(0.55)" : "brightness(1)",
                transition: "transform 1100ms cubic-bezier(0.25, 1, 0.5, 1), filter 1100ms ease",
              }}
            >
              <img src={panelLeftImg} alt="" className="w-full h-full object-fill pointer-events-none" />
            </div>

            {/* Right panel */}
            <div
              className="absolute transition-all duration-[1100ms]"
              style={{
                left: rect.left + rect.width / 2 - 1,
                top: rect.top,
                width:  rect.width / 2 + 1,
                height: rect.height,
                transformOrigin: "right center",
                transform: isOpen ? "perspective(900px) rotateY(96deg) scaleX(0.9)" : "perspective(900px) rotateY(0deg)",
                filter: isOpen ? "brightness(0.55)" : "brightness(1)",
                transition: "transform 1100ms cubic-bezier(0.25, 1, 0.5, 1), filter 1100ms ease",
              }}
            >
              <img src={panelRightImg} alt="" className="w-full h-full object-fill pointer-events-none" />
            </div>
          </div>
        )}

        {/* Stone arch frame — transparent hole → WP site visible through arch */}
        <div
          ref={frameRef}
          className="absolute inset-0 pointer-events-none z-20"
          style={{
            backgroundImage: `url(${frameSurroundImg})`,
            backgroundSize:     geo ? `${geo.dispW}px ${geo.dispH}px` : "cover",
            backgroundPosition: geo ? `${geo.offX}px ${geo.offY}px` : "center",
          }}
        />

        {/* CTA button — "Enter" — hidden once doors open */}
        <div
          className={`absolute bottom-[5%] left-0 right-0 flex flex-col items-center z-40 transition-all duration-300 ${
            isOpen ? "opacity-0 pointer-events-none" : "opacity-100 pointer-events-auto"
          }`}
        >
          <button
            type="button"
            className="px-8 py-3 rounded-full bg-[#9a7470] hover:bg-[#886460] active:scale-95 text-[#fbf7f4] font-['Cinzel',serif] text-[0.76rem] uppercase tracking-[0.26em] font-medium shadow-[0_8px_24px_rgba(100,60,60,0.35)] transition-all duration-300 border border-[#bfa29f]/40 flex items-center gap-2 cursor-pointer"
            // The button is decorative — the auto-start fires the enter sequence
          >
            <span>JOIN THE JOURNEY</span>
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.8" className="w-4 h-4">
              <path d="M4 10H16M16 10L11 5M16 10L11 15" />
            </svg>
          </button>
          <span className="mt-2.5 text-[#af9680] font-['Cinzel',serif] text-[0.65rem] uppercase tracking-[0.3em] drop-shadow-sm">
            STEP INTO YOUR INNER WORLD
          </span>
        </div>
      </div>

      {/* Light glow bloom — lights up as doors swing open */}
      <div
        ref={glowRef}
        className="absolute inset-0 pointer-events-none z-30 opacity-0"
        style={{
          background: `radial-gradient(ellipse 45% 50% at ${center.x}px ${center.y}px, rgba(255,240,200,0.7) 0%, rgba(245,215,160,0.25) 45%, transparent 75%)`,
        }}
      />
    </div>
  );
}
