import { useState } from "react";
import heroFullImg from "../../assets/drumi/hero-full.png";
import innerSanctuaryImg from "../../assets/drumi/inner-sanctuary.jpg";
import doorLeafLeftImg from "../../assets/drumi/door-leaf-left-clean.png";
import doorLeafRightImg from "../../assets/drumi/door-leaf-right-clean.png";
import doorFrameOverlayImg from "../../assets/drumi/door-frame-overlay.png";

interface DrumiDoorStageProps {
  soundOn: boolean;
  onToggleSound: () => void;
  onPlayDoorOpen: () => void;
  onPlayChime: (freq?: number) => void;
  onScrollToFounder: () => void;
}

export function DrumiDoorStage({
  soundOn,
  onToggleSound,
  onPlayDoorOpen,
  onPlayChime,
  onScrollToFounder,
}: DrumiDoorStageProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [isHovered, setIsHovered] = useState(false);

  const toggleDoors = () => {
    if (!isOpen) {
      onPlayDoorOpen();
      setIsOpen(true);
    } else {
      onPlayChime(440);
      setIsOpen(false);
    }
  };

  return (
    <section className="relative w-full bg-[#ede6df] overflow-hidden select-none">
      {/* Sound Toggle Button (Floating luxury button top right) */}
      <div className="absolute top-4 right-5 z-40 flex items-center gap-3">
        <button
          type="button"
          onClick={onToggleSound}
          className={`flex items-center gap-2 px-3 py-1.5 rounded-full border backdrop-blur-md text-[11px] uppercase tracking-[0.2em] transition-all duration-300 shadow-sm ${
            soundOn
              ? "bg-[#9a7470]/90 border-[#c4a9a6] text-[#fbf7f4]"
              : "bg-[#f5efe9]/80 border-[#d8c9be] text-[#7d6957] hover:bg-[#ede3da]"
          }`}
          title="Toggle Sanctuary Ambience"
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

      {/* Main Hero Container */}
      <div className="relative w-full max-w-[960px] mx-auto flex flex-col items-center">
        {/* Header Branding (exact luxury typography from mockup) */}
        <div className="pt-8 pb-3 px-4 flex flex-col items-center text-center z-30">
          {/* Sacred Droplet Emblem */}
          <div className="w-8 h-8 mb-2 flex items-center justify-center text-[#7d6957]">
            <svg
              viewBox="0 0 40 48"
              fill="none"
              stroke="currentColor"
              strokeWidth="1.8"
              className="w-7 h-7 drop-shadow-sm"
            >
              <path
                d="M20 2C20 2 6 22 6 32C6 39.732 12.268 46 20 46C27.732 46 34 39.732 34 32C34 22 20 2 20 2Z"
                stroke="currentColor"
              />
              <circle cx="20" cy="32" r="7" stroke="currentColor" />
              <circle cx="20" cy="22" r="1.2" fill="currentColor" />
              <circle cx="20" cy="26" r="1.2" fill="currentColor" />
              <circle cx="20" cy="30" r="1.2" fill="currentColor" />
            </svg>
          </div>

          <h1 className="font-['Cinzel',serif] text-[2.1rem] sm:text-[2.6rem] tracking-[0.38em] text-[#6e5d4e] uppercase font-normal leading-none mb-2 pl-[0.38em]">
            D R U M I
          </h1>
          <p className="font-['Cormorant_Garamond',serif] italic text-[#7a6a5b] text-[1.05rem] sm:text-[1.2rem] leading-tight">
            A sanctuary for your dreams.
          </p>
          <p className="font-['Cormorant_Garamond',serif] italic text-[#7a6a5b] text-[1.05rem] sm:text-[1.2rem] leading-tight">
            A journey back to yourself.
          </p>
        </div>

        {/* 3D Interactive Door Scene */}
        <div
          className="relative w-full aspect-[682/438] max-h-[720px] overflow-hidden cursor-pointer group"
          style={{ perspective: "1400px" }}
          onClick={toggleDoors}
        >
          {/* Layer 1: Background Lake Sanctuary (visible through open door) */}
          <div
            className={`absolute inset-0 transition-transform duration-[2000ms] ease-out origin-center ${
              isOpen ? "scale-110 brightness-105" : "scale-100 brightness-95"
            }`}
            style={{
              backgroundImage: `url(${innerSanctuaryImg})`,
              backgroundPosition: "center 42%",
              backgroundSize: "cover",
            }}
          >
            {/* Golden hour sun rays & haze */}
            <div
              className={`absolute inset-0 bg-gradient-to-t from-[#c59e78]/30 via-[#f8e5c8]/20 to-transparent transition-opacity duration-1000 ${
                isOpen ? "opacity-90" : "opacity-40"
              }`}
            />
          </div>

          {/* Layer 2: Background architectural surround (walls, pots, curtains) */}
          <div
            className="absolute inset-0 pointer-events-none z-10"
            style={{
              backgroundImage: `url(${doorFrameOverlayImg})`,
              backgroundSize: "100% 100%",
              backgroundPosition: "center",
            }}
          />

          {/* Layer 3: Left 3D Door Leaf */}
          <div
            className="absolute top-[13.2%] left-[20.2%] w-[28.4%] h-[81.2%] z-20 transition-all duration-[1600ms] cubic-bezier(0.25, 1, 0.5, 1)"
            style={{
              transformOrigin: "left center",
              transform: isOpen
                ? "rotateY(-84deg) scaleX(0.96)"
                : isHovered
                ? "rotateY(-10deg)"
                : "rotateY(-3deg)",
              filter: isOpen
                ? "brightness(0.7) drop-shadow(10px 0 15px rgba(0,0,0,0.35))"
                : "brightness(1)",
            }}
          >
            <img
              src={doorLeafLeftImg}
              alt="DRUMI Left Door"
              className="w-full h-full object-fill pointer-events-none"
            />
          </div>

          {/* Layer 4: Right 3D Door Leaf */}
          <div
            className="absolute top-[13.2%] left-[51.3%] w-[28.4%] h-[81.2%] z-20 transition-all duration-[1600ms] cubic-bezier(0.25, 1, 0.5, 1)"
            style={{
              transformOrigin: "right center",
              transform: isOpen
                ? "rotateY(84deg) scaleX(0.96)"
                : isHovered
                ? "rotateY(10deg)"
                : "rotateY(3deg)",
              filter: isOpen
                ? "brightness(0.7) drop-shadow(-10px 0 15px rgba(0,0,0,0.35))"
                : "brightness(1)",
            }}
          >
            <img
              src={doorLeafRightImg}
              alt="DRUMI Right Door"
              className="w-full h-full object-fill pointer-events-none"
            />
          </div>

          {/* Golden Light Burst Beam when doors open */}
          <div
            className={`absolute top-[15%] left-[25%] right-[25%] bottom-[10%] z-25 pointer-events-none transition-all duration-1000 ${
              isOpen
                ? "opacity-80 scale-125 bg-radial from-[#fff4d6]/60 via-[#eacfa0]/25 to-transparent blur-md"
                : "opacity-0 scale-75"
            }`}
          />

          {/* Center Seam Glow when doors are closed */}
          {!isOpen && (
            <div className="absolute top-[15%] left-[48.6%] w-[2.8%] h-[78%] z-15 pointer-events-none bg-gradient-to-b from-[#ffe5ba]/80 via-[#f8d49a]/90 to-[#d8a870]/40 blur-[2px] animate-pulse" />
          )}

          {/* Layer 5: Fallback & Perfect Mockup Match Layer (when doors are at rest) */}
          <div
            className={`absolute inset-0 pointer-events-none z-15 transition-opacity duration-700 ${
              isOpen ? "opacity-0" : "opacity-100"
            }`}
            style={{
              backgroundImage: `url(${heroFullImg})`,
              backgroundSize: "100% 100%",
            }}
          />

          {/* Interactive Button in the Doorway Center */}
          <div
            className="absolute bottom-[4.5%] left-0 right-0 z-30 flex flex-col items-center justify-center pointer-events-auto"
            onClick={(e) => {
              e.stopPropagation();
              toggleDoors();
            }}
            onMouseEnter={() => setIsHovered(true)}
            onMouseLeave={() => setIsHovered(false)}
          >
            <button
              type="button"
              className="px-6 py-2 sm:px-8 sm:py-2.5 rounded-full bg-[#9a7470] hover:bg-[#886460] active:scale-95 text-[#fbf7f4] font-['Cinzel',serif] text-[0.7rem] sm:text-[0.8rem] uppercase tracking-[0.25em] font-medium shadow-[0_4px_16px_rgba(100,60,60,0.3)] transition-all duration-300 border border-[#bfa29f]/40 flex items-center gap-2 group/btn"
            >
              <span>{isOpen ? "CLOSE THE DOORS" : "JOIN THE JOURNEY"}</span>
              <svg
                viewBox="0 0 20 20"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.8"
                className={`w-3.5 h-3.5 transition-transform duration-300 ${
                  isOpen ? "rotate-180" : "group-hover/btn:translate-x-0.5"
                }`}
              >
                <path d="M4 10H16M16 10L11 5M16 10L11 15" />
              </svg>
            </button>

            <span className="mt-2 text-[#b09680] font-['Cinzel',serif] text-[0.58rem] sm:text-[0.66rem] uppercase tracking-[0.28em] font-medium drop-shadow-sm">
              {isOpen ? "YOU ARE IN THE INNER WORLD" : "STEP INTO YOUR INNER WORLD"}
            </span>
          </div>
        </div>

        {/* Action Prompt when Doors are Open */}
        {isOpen && (
          <div className="w-full py-4 px-6 bg-[#f7f2ed] border-y border-[#dfd2c6] flex flex-wrap items-center justify-between gap-3 text-center sm:text-left z-20 animate-fadeIn">
            <div className="flex items-center gap-2">
              <span className="text-[#a07b78] text-base">✦</span>
              <span className="font-['Cinzel',serif] text-[0.76rem] tracking-[0.16em] uppercase text-[#6e5d4e]">
                Sanctuary Doorway Revealed
              </span>
            </div>
            <div className="flex items-center gap-3 mx-auto sm:mx-0">
              <button
                type="button"
                onClick={onScrollToFounder}
                className="px-4 py-1.5 rounded-full border border-[#8e6e6a] text-[#8e6e6a] hover:bg-[#8e6e6a] hover:text-[#fff] text-[0.68rem] tracking-[0.18em] uppercase font-['Cinzel',serif] transition-colors"
              >
                Discover Founder Story ↓
              </button>
              <button
                type="button"
                onClick={toggleDoors}
                className="px-3 py-1.5 text-[0.68rem] tracking-[0.18em] uppercase text-[#7d6957] hover:text-[#3a3028]"
              >
                Step Back
              </button>
            </div>
          </div>
        )}
      </div>
    </section>
  );
}
