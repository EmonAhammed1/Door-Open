import { useRef, useEffect } from "react";
import doorsClosedImg from "../../assets/drumi/doors-closed.jpg";
import doorsOpenImg from "../../assets/drumi/doors-open.jpg";
import panelLeftImg from "../../assets/drumi/panel-left-clean.png";
import panelRightImg from "../../assets/drumi/panel-right-clean.png";
import frameSurroundImg from "../../assets/drumi/frame-surround-clean.png";
import { computeCover, toPx, useNaturalSize, useElementSize } from "../../hooks/useCoverGeometry";

export type DrumiPhase = "idle" | "opening" | "entering" | "inside";

interface DrumiThresholdProps {
  phase: DrumiPhase;
  soundOn: boolean;
  onEnter: () => void;
  onArrived: () => void;
  onToggleSound: () => void;
}

// Exact fractions measured from 1376 x 768 architectural render
const DOOR_GEOMETRY = {
  left: 380 / 1376,
  right: 996 / 1376,
  top: 160 / 768,
  bottom: 695 / 768,
};

const ENTER_MS = 1400;
const easeInOut = (t: number) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
const easeOut = (t: number) => 1 - Math.pow(1 - t, 3);

export function DrumiThreshold({
  phase,
  soundOn,
  onEnter,
  onArrived,
  onToggleSound,
}: DrumiThresholdProps) {
  const stageRef = useRef<HTMLDivElement>(null);
  const sceneRef = useRef<HTMLDivElement>(null);
  const baseRef = useRef<HTMLDivElement>(null);
  const openBgRef = useRef<HTMLDivElement>(null);
  const clipRef = useRef<HTMLDivElement>(null);
  const panelsRef = useRef<HTMLDivElement>(null);
  const glowRef = useRef<HTMLDivElement>(null);

  const natural = useNaturalSize(doorsClosedImg);
  const { w: cw, h: ch } = useElementSize(stageRef);
  const geo = natural && cw && ch ? computeCover(cw, ch, natural.w, natural.h) : null;

  let rect: { left: number; top: number; width: number; height: number } | null = null;
  let center = { x: cw / 2, y: ch / 2 };

  if (geo) {
    const tl = toPx(geo, DOOR_GEOMETRY.left, DOOR_GEOMETRY.top);
    const br = toPx(geo, DOOR_GEOMETRY.right, DOOR_GEOMETRY.bottom);
    rect = { left: tl.x, top: tl.y, width: br.x - tl.x, height: br.y - tl.y };
    center = { x: (tl.x + br.x) / 2, y: (tl.y + br.y) / 2 };
  }

  const rectRef = useRef(rect);
  rectRef.current = rect;
  const centerRef = useRef(center);
  centerRef.current = center;
  const sizeRef = useRef({ cw, ch });
  sizeRef.current = { cw, ch };

  // Smooth cinematic camera walk-through into the doorway
  useEffect(() => {
    if (phase !== "entering") return;

    const r = rectRef.current;
    const P = centerRef.current;
    const { cw: curW, ch: curH } = sizeRef.current;
    const scene = sceneRef.current;
    const base = baseRef.current;
    const openBg = openBgRef.current;
    const clip = clipRef.current;
    const panels = panelsRef.current;
    const glow = glowRef.current;

    if (!r || !scene || !base || !openBg || !clip || !panels || !glow) {
      onArrived();
      return;
    }

    const need = Math.max(
      P.x / Math.max(1, P.x - r.left),
      (curW - P.x) / Math.max(1, r.left + r.width - P.x),
      P.y / Math.max(1, P.y - r.top),
      (curH - P.y) / Math.max(1, r.top + r.height - P.y)
    );
    const sEnd = Math.min(3.6, need * 1.05);
    const inset0 = { t: r.top, rgt: curW - r.left - r.width, b: curH - r.top - r.height, l: r.left };
    const start = performance.now();
    let raf = 0;

    const frame = (now: number) => {
      const t = Math.min(1, (now - start) / ENTER_MS);
      const e = easeInOut(t);
      const s = 1 + (sEnd - 1) * e;
      const k = 1.25 - 0.25 * easeOut(t);

      scene.style.transform = `scale(${s})`;
      openBg.style.transform = `scale(${k / s})`;

      const shrink = 1 - easeOut(Math.min(1, t * 1.2));
      clip.style.clipPath = `inset(${inset0.t * shrink}px ${inset0.rgt * shrink}px ${inset0.b * shrink}px ${inset0.l * shrink}px)`;

      const op = String(1 - easeInOut(Math.min(1, t / 0.6)));
      base.style.opacity = op;
      panels.style.opacity = op;

      glow.style.opacity = String(t < 0.4 ? 0.6 + 0.4 * (t / 0.4) : Math.max(0, 1 - (t - 0.4) / 0.6));

      if (t < 1) {
        raf = requestAnimationFrame(frame);
      } else {
        onArrived();
      }
    };

    raf = requestAnimationFrame(frame);
    return () => cancelAnimationFrame(raf);
  }, [phase, onArrived]);

  const origin = `${center.x}px ${center.y}px`;
  const isOpen = phase === "opening" || phase === "entering";

  return (
    <div
      ref={stageRef}
      className="fixed inset-0 w-full h-full z-30 overflow-hidden bg-[#e8ded5] select-none"
      style={{ perspective: "1500px" }}
    >
      {/* Sound Toggle (Top Right) */}
      <div className="absolute top-5 right-6 z-50 pointer-events-auto">
        <button
          type="button"
          onClick={onToggleSound}
          className={`flex items-center gap-2 px-3.5 py-1.5 rounded-full border backdrop-blur-md text-[11px] uppercase tracking-[0.2em] transition-all shadow-sm cursor-pointer ${
            soundOn
              ? "bg-[#9a7470]/90 border-[#c4a9a6] text-[#fbf7f4]"
              : "bg-[#f5efe9]/80 border-[#d8c9be] text-[#7d6957] hover:bg-[#ede3da]"
          }`}
        >
          <span className="relative flex h-2 w-2">
            {soundOn && (
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#eedcd7] opacity-75"></span>
            )}
            <span
              className={`relative inline-flex rounded-full h-2 w-2 ${
                soundOn ? "bg-[#fbf7f4]" : "bg-[#a68d75]"
              }`}
            ></span>
          </span>
          <span>{soundOn ? "SOUND ON" : "SOUND OFF"}</span>
        </button>
      </div>

      {/* Brand Header Typography - ALWAYS VISIBLE, NEVER CUT OFF */}
      <div
        className={`absolute top-0 left-0 right-0 z-45 pt-6 sm:pt-8 pb-3 px-4 flex flex-col items-center text-center pointer-events-none transition-opacity duration-300 ${
          isOpen ? "opacity-0" : "opacity-100"
        }`}
      >
        {/* Sacred Droplet Emblem */}
        <div className="w-8 h-8 mb-1.5 flex items-center justify-center text-[#7d6957]">
          <svg viewBox="0 0 40 48" fill="none" stroke="currentColor" strokeWidth="1.8" className="w-7 h-7 drop-shadow-sm">
            <path d="M20 2C20 2 6 22 6 32C6 39.732 12.268 46 20 46C27.732 46 34 39.732 34 32C34 22 20 2 20 2Z" />
            <circle cx="20" cy="32" r="7" />
            <circle cx="20" cy="22" r="1.2" fill="currentColor" />
            <circle cx="20" cy="26" r="1.2" fill="currentColor" />
            <circle cx="20" cy="30" r="1.2" fill="currentColor" />
          </svg>
        </div>

        <h1 className="font-['Cinzel',serif] text-[2.2rem] sm:text-[3rem] tracking-[0.38em] text-[#5e4e41] uppercase font-normal leading-tight pl-[0.38em] drop-shadow-sm">
          D R U M I
        </h1>
        <p className="font-['Cormorant_Garamond',serif] italic text-[#726254] text-[1.1rem] sm:text-[1.3rem] leading-tight">
          A sanctuary for your dreams.
        </p>
        <p className="font-['Cormorant_Garamond',serif] italic text-[#726254] text-[1.1rem] sm:text-[1.3rem] leading-tight">
          A journey back to yourself.
        </p>
      </div>

      {/* Main 3D Scaling Scene */}
      <div
        ref={sceneRef}
        className="relative w-full h-full origin-center transition-transform"
        style={{ transformOrigin: origin }}
      >
        {/* 1. Base Layer: Closed Doors Artwork */}
        <div
          ref={baseRef}
          className="absolute inset-0 transition-opacity duration-500"
          style={{
            backgroundImage: `url(${doorsClosedImg})`,
            backgroundSize: geo ? `${geo.dispW}px ${geo.dispH}px` : "cover",
            backgroundPosition: geo ? `${geo.offX}px ${geo.offY}px` : "center",
          }}
        />

        {/* 2. Open Door Lake Sanctuary View (Clipped to Doorway Opening) */}
        <div
          ref={clipRef}
          className="absolute inset-0 pointer-events-none"
          style={
            rect
              ? {
                  clipPath: `inset(${rect.top}px ${cw - rect.left - rect.width}px ${ch - rect.top - rect.height}px ${rect.left}px)`,
                }
              : undefined
          }
        >
          {/* This is the 100% clean open archway with NO DOORS AT ALL */}
          <div
            ref={openBgRef}
            className="absolute inset-0 transition-transform"
            style={{
              backgroundImage: `url(${doorsOpenImg})`,
              backgroundSize: geo ? `${geo.dispW}px ${geo.dispH}px` : "cover",
              backgroundPosition: geo ? `${geo.offX}px ${geo.offY}px` : "center",
              transformOrigin: origin,
              transform: "scale(1.2)",
            }}
          />
        </div>

        {/* 3. Mathematical Arched Door Panels (3D Rotate Y) */}
        {rect && geo && (
          <div ref={panelsRef} className="absolute inset-0 pointer-events-none">
            {/* Left Door Panel - Swings open completely and fades out so NO DOOR IS VISIBLE */}
            <div
              className="absolute transition-all duration-[1200ms] cubic-bezier(0.25, 1, 0.5, 1)"
              style={{
                left: rect.left,
                top: rect.top,
                width: rect.width / 2 + 0.5,
                height: rect.height,
                transformOrigin: "left center",
                transform: isOpen ? "rotateY(-115deg) scaleX(0.85)" : "rotateY(0deg)",
                opacity: isOpen ? 0 : 1,
                filter: isOpen ? "brightness(0.5)" : "brightness(1)",
              }}
            >
              <img
                src={panelLeftImg}
                alt="Left Door"
                className="w-full h-full object-fill pointer-events-none"
              />
            </div>

            {/* Right Door Panel - Swings open completely and fades out so NO DOOR IS VISIBLE */}
            <div
              className="absolute transition-all duration-[1200ms] cubic-bezier(0.25, 1, 0.5, 1)"
              style={{
                left: rect.left + rect.width / 2 - 0.5,
                top: rect.top,
                width: rect.width / 2 + 0.5,
                height: rect.height,
                transformOrigin: "right center",
                transform: isOpen ? "rotateY(115deg) scaleX(0.85)" : "rotateY(0deg)",
                opacity: isOpen ? 0 : 1,
                filter: isOpen ? "brightness(0.5)" : "brightness(1)",
              }}
            >
              <img
                src={panelRightImg}
                alt="Right Door"
                className="w-full h-full object-fill pointer-events-none"
              />
            </div>
          </div>
        )}

        {/* 4. Frame Surround (Wall, potted olive tree, sheer curtains, floor) */}
        <div
          className="absolute inset-0 pointer-events-none"
          style={{
            backgroundImage: `url(${frameSurroundImg})`,
            backgroundSize: geo ? `${geo.dispW}px ${geo.dispH}px` : "cover",
            backgroundPosition: geo ? `${geo.offX}px ${geo.offY}px` : "center",
          }}
        />

        {/* 5. CTA Button in front of the Door Threshold */}
        <div
          className={`absolute bottom-[5%] sm:bottom-[6%] left-0 right-0 flex flex-col items-center justify-center z-40 transition-all duration-300 ${
            isOpen ? "opacity-0 pointer-events-none scale-95" : "opacity-100 pointer-events-auto"
          }`}
        >
          <button
            type="button"
            onClick={onEnter}
            className="px-8 py-3 sm:px-10 sm:py-3.5 rounded-full bg-[#9a7470] hover:bg-[#886460] active:scale-95 text-[#fbf7f4] font-['Cinzel',serif] text-[0.76rem] sm:text-[0.88rem] uppercase tracking-[0.26em] font-medium shadow-[0_8px_24px_rgba(100,60,60,0.35)] transition-all duration-300 border border-[#bfa29f]/40 flex items-center gap-2 group cursor-pointer"
          >
            <span>JOIN THE JOURNEY</span>
            <svg
              viewBox="0 0 20 20"
              fill="none"
              stroke="currentColor"
              strokeWidth="1.8"
              className="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1"
            >
              <path d="M4 10H16M16 10L11 5M16 10L11 15" />
            </svg>
          </button>

          <span className="mt-2.5 text-[#af9680] font-['Cinzel',serif] text-[0.62rem] sm:text-[0.72rem] uppercase tracking-[0.3em] font-medium drop-shadow-sm">
            STEP INTO YOUR INNER WORLD
          </span>
        </div>
      </div>

      {/* Light Bloom through Doorway */}
      <div
        ref={glowRef}
        className="absolute inset-0 pointer-events-none z-35 transition-opacity duration-700 opacity-0 bg-radial from-[#fff5dd]/80 via-[#f5d9ad]/30 to-transparent"
      />
    </div>
  );
}
