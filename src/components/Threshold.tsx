import { ArrowRight, Logo, MenuIcon, Ornament } from "./Brand";
import { Atmosphere } from "./Atmosphere";
import { Parchment } from "./Parchment";
import { THRESHOLD } from "../data/site";
import { cn } from "../utils/cn";

interface ThresholdProps {
  leaving: boolean;
  soundOn: boolean;
  onEnter: () => void;
  onToggleSound: () => void;
  onOpenMenu: () => void;
}

/**
 * INTRO / THRESHOLD — all UI that floats above the door scene.
 * It fades and gently scales away when the visitor enters.
 */
export function Threshold({ leaving, soundOn, onEnter, onToggleSound, onOpenMenu }: ThresholdProps) {
  return (
    <div className={cn("threshold-ui fixed inset-0 z-20", leaving && "is-leaving")}>
      <Atmosphere embers={14} />

      {/* Top bar */}
      <header className="absolute inset-x-0 top-0 z-10 flex items-start justify-between px-5 pt-5 sm:px-10 sm:pt-7">
        <div className="rise" style={{ ["--d" as string]: "0.2s" }}>
          <Logo />
        </div>
        <nav className="rise flex items-center gap-5 pt-2 sm:gap-8" style={{ ["--d" as string]: "0.4s" }} aria-label="Threshold">
          <button className="nav-link" onClick={onEnter}>
            Enter
          </button>
          <button
            className={cn("nav-link flex items-center gap-2", soundOn && "is-active")}
            onClick={onToggleSound}
            aria-pressed={soundOn}
          >
            Sound
            <span className={cn("eq", !soundOn && "is-off")} aria-hidden="true">
              <i />
              <i />
              <i />
              <i />
            </span>
          </button>
          <button className="nav-link flex items-center gap-2" onClick={onOpenMenu}>
            Menu
            <MenuIcon size={18} />
          </button>
        </nav>
      </header>

      {/* Parchment on the left wall */}
      <Parchment
        className="fade-in absolute left-[3.2%] top-[30%] hidden w-[90px] lg:block xl:w-[100px]"
        style={{ ["--d" as string]: "0.9s" }}
      />

      {/* Centre copy */}
      <div className="absolute inset-0 flex items-center justify-center px-5 pointer-events-none">
        <div className="text-scrim relative mx-auto max-w-[680px] px-6 py-10 text-center sm:px-12 pointer-events-auto">
          {/* Sacred Droplet Emblem */}
          <div className="flex justify-center mb-3 text-gold/90">
            <svg
              viewBox="0 0 40 48"
              fill="none"
              stroke="currentColor"
              strokeWidth="1.8"
              className="w-8 h-9 drop-shadow-md"
            >
              <path d="M20 2C20 2 6 22 6 32C6 39.732 12.268 46 20 46C27.732 46 34 39.732 34 32C34 22 20 2 20 2Z" />
              <circle cx="20" cy="32" r="7" />
              <circle cx="20" cy="22" r="1.2" fill="currentColor" />
              <circle cx="20" cy="26" r="1.2" fill="currentColor" />
              <circle cx="20" cy="30" r="1.2" fill="currentColor" />
            </svg>
          </div>

          <h1
            className="display rise text-glow text-[clamp(2.2rem,4.2vw,3.3rem)] leading-[1.15] tracking-[0.35em] text-cream pl-[0.35em]"
            style={{ ["--d" as string]: "0.5s" }}
          >
            DRUMI
          </h1>

          <div className="mt-3.5 space-y-1">
            {THRESHOLD.body.map((l) => (
              <p
                key={l}
                className="font-['Cormorant_Garamond',serif] italic text-shadow-soft text-[clamp(1.15rem,1.65vw,1.45rem)] leading-[1.45] text-cream/95"
              >
                {l}
              </p>
            ))}
          </div>

          <div className="rise mt-8 sm:mt-10 flex flex-col items-center justify-center" style={{ ["--d" as string]: "1s" }}>
            <button
              className="px-11 py-3.5 rounded-[4px] bg-[#9a7470] hover:bg-[#86615d] active:scale-95 text-cream font-['Cinzel',serif] text-[0.82rem] sm:text-[0.88rem] uppercase tracking-[0.26em] font-medium shadow-[0_8px_24px_rgba(80,45,45,0.35)] transition-all duration-300 border border-[#c4a29f]/45 flex items-center justify-center cursor-pointer"
              onClick={onEnter}
            >
              <span>{THRESHOLD.cta}</span>
            </button>
            <span className="mt-3 font-['Cinzel',serif] text-[0.62rem] sm:text-[0.7rem] uppercase tracking-[0.32em] font-medium text-cream/80 drop-shadow-sm">
              {THRESHOLD.subCta}
            </span>
          </div>
        </div>
      </div>

      {/* Bottom lines */}
      <footer className="absolute inset-x-0 bottom-0 z-10 flex items-end justify-between px-5 pb-5 sm:px-10 sm:pb-7">
        <p
          className="smallcaps rise text-[0.55rem] leading-[1.95] text-cream/80 sm:text-[0.62rem]"
          style={{ ["--d" as string]: "1.2s" }}
        >
          {THRESHOLD.bottomLeft.map((l) => (
            <span key={l} className="block">
              {l}
            </span>
          ))}
        </p>
        <div
          className="fade-in absolute bottom-0 left-1/2 hidden -translate-x-1/2 flex-col items-center text-gold/80 sm:flex"
          style={{ ["--d" as string]: "1.4s" }}
        >
          <Ornament size={14} />
          <span className="mt-1 h-10 w-px bg-gradient-to-b from-gold/70 to-transparent" />
        </div>
        <p
          className="smallcaps rise text-right text-[0.55rem] leading-[1.95] text-cream/80 sm:text-[0.62rem]"
          style={{ ["--d" as string]: "1.2s" }}
        >
          {THRESHOLD.bottomRight.map((l) => (
            <span key={l} className="block">
              {l}
            </span>
          ))}
        </p>
      </footer>
    </div>
  );
}
