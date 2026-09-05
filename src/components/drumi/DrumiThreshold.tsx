import { useRef } from "react";
import innerSanctuaryImg from "../../assets/drumi/inner-sanctuary.jpg";
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
  onCloseDoors: () => void;
  onToggleSound: () => void;
}

// Exact doorway geometry in fractions of the 1376 x 768 frame
const DOOR_GEOMETRY = {
  left: 380 / 1376,
  right: 996 / 1376,
  top: 160 / 768,
  bottom: 695 / 768,
};

export function DrumiThreshold({
  phase,
  soundOn,
  onEnter,
  onArrived,
  onCloseDoors,
  onToggleSound,
}: DrumiThresholdProps) {
  const stageRef = useRef<HTMLDivElement>(null);

  const natural = useNaturalSize(innerSanctuaryImg);
  const { w: cw, h: ch } = useElementSize(stageRef);
  const geo = natural && cw && ch ? computeCover(cw, ch, natural.w, natural.h) : null;

  let rect: { left: number; top: number; width: number; height: number } | null = null;

  if (geo) {
    const tl = toPx(geo, DOOR_GEOMETRY.left, DOOR_GEOMETRY.top);
    const br = toPx(geo, DOOR_GEOMETRY.right, DOOR_GEOMETRY.bottom);
    rect = { left: tl.x, top: tl.y, width: br.x - tl.x, height: br.y - tl.y };
  }

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

      {/* Brand Header Typography - Stable & Crisp at Top */}
      <div className="absolute top-0 left-0 right-0 z-45 pt-6 sm:pt-8 pb-3 px-4 flex flex-col items-center text-center pointer-events-none">
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

      {/* Main Architectural Scene - ZERO ZOOMING, COMPLETELY STABLE */}
      <div className="relative w-full h-full">
        {/* 1. Inside Image (Sanctuary with terrace, lanterns, lake - visible behind open doors) */}
        <div
          className="absolute inset-0 transition-opacity duration-700"
          style={{
            backgroundImage: `url(${innerSanctuaryImg})`,
            backgroundSize: geo ? `${geo.dispW}px ${geo.dispH}px` : "cover",
            backgroundPosition: geo ? `${geo.offX}px ${geo.offY}px` : "center",
          }}
        />

        {/* 2. Double Arched Door Panels (Opens in 3D without any camera zoom!) */}
        {rect && geo && (
          <div className="absolute inset-0 pointer-events-none z-10">
            {/* Left Door Panel */}
            <div
              className="absolute transition-transform duration-[1200ms] cubic-bezier(0.25, 1, 0.5, 1)"
              style={{
                left: rect.left,
                top: rect.top,
                width: rect.width / 2 + 1,
                height: rect.height,
                transformOrigin: "left center",
                transform: isOpen ? "rotateY(-86deg)" : "rotateY(0deg)",
                filter: isOpen
                  ? "brightness(0.7) drop-shadow(14px 0 20px rgba(0,0,0,0.45))"
                  : "brightness(1)",
              }}
            >
              <img
                src={panelLeftImg}
                alt="Left Door"
                className="w-full h-full object-fill pointer-events-none"
              />
            </div>

            {/* Right Door Panel */}
            <div
              className="absolute transition-transform duration-[1200ms] cubic-bezier(0.25, 1, 0.5, 1)"
              style={{
                left: rect.left + rect.width / 2 - 1,
                top: rect.top,
                width: rect.width / 2 + 1,
                height: rect.height,
                transformOrigin: "right center",
                transform: isOpen ? "rotateY(86deg)" : "rotateY(0deg)",
                filter: isOpen
                  ? "brightness(0.7) drop-shadow(-14px 0 20px rgba(0,0,0,0.45))"
                  : "brightness(1)",
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

        {/* 3. The Single Architectural Stone Archway Frame (Walls, olive tree, curtains, floor) */}
        <div
          className="absolute inset-0 pointer-events-none z-20"
          style={{
            backgroundImage: `url(${frameSurroundImg})`,
            backgroundSize: geo ? `${geo.dispW}px ${geo.dispH}px` : "cover",
            backgroundPosition: geo ? `${geo.offX}px ${geo.offY}px` : "center",
          }}
        />

        {/* 4. Controls at the Bottom of the Portal */}
        <div className="absolute bottom-[5%] sm:bottom-[6%] left-0 right-0 flex flex-col items-center justify-center z-40">
          {!isOpen ? (
            /* Closed State CTA */
            <div className="flex flex-col items-center animate-fadeIn">
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
          ) : (
            /* Open State Controls: Doors are open, showing the sanctuary! */
            <div className="flex flex-col sm:flex-row items-center gap-3 animate-fadeIn">
              <button
                type="button"
                onClick={onArrived}
                className="px-8 py-3 rounded-full bg-[#9a7470] hover:bg-[#886460] text-white font-['Cinzel',serif] text-[0.76rem] sm:text-[0.84rem] uppercase tracking-[0.24em] font-medium shadow-[0_8px_24px_rgba(100,60,60,0.4)] transition-all duration-300 border border-[#c4a9a6]/50 flex items-center gap-2 cursor-pointer group"
              >
                <span>STEP INSIDE SANCTUARY</span>
                <span className="transition-transform group-hover:translate-x-1 font-sans">→</span>
              </button>

              <button
                type="button"
                onClick={onCloseDoors}
                className="px-6 py-2.5 rounded-full bg-[#342921]/70 hover:bg-[#342921]/90 text-white/90 font-['Cinzel',serif] text-[0.7rem] sm:text-[0.76rem] uppercase tracking-[0.2em] font-medium backdrop-blur-md transition-all border border-white/20 cursor-pointer"
              >
                <span>↺ Close Doors</span>
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Gentle Warm Light Glow when doors open */}
      <div
        className={`absolute inset-0 pointer-events-none z-25 transition-opacity duration-1000 ${
          isOpen ? "opacity-60 bg-radial from-[#fff4d6]/40 via-[#f8e5c8]/15 to-transparent" : "opacity-0"
        }`}
      />
    </div>
  );
}
