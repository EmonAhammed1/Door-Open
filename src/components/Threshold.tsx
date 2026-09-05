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
      <div className="absolute inset-0 flex items-center justify-center px-5">
        <div className="text-scrim relative mx-auto max-w-[680px] px-6 py-14 text-center sm:px-14">
          <h1
            className="display rise text-glow text-[clamp(1.55rem,3.3vw,2.7rem)] leading-[1.28] tracking-[0.06em] text-cream"
            style={{ ["--d" as string]: "0.5s" }}
          >
            {THRESHOLD.headline.map((l) => (
              <span key={l} className="block">
                {l}
              </span>
            ))}
          </h1>
          <Ornament className="rise mx-auto my-5 text-gold-light sm:my-6" size={20} />
          <p
            className="body rise text-shadow-soft text-[clamp(1.1rem,1.55vw,1.5rem)] leading-[1.55] text-cream/90"
            style={{ ["--d" as string]: "0.75s" }}
          >
            {THRESHOLD.body.map((l) => (
              <span key={l} className="block">
                {l}
              </span>
            ))}
          </p>
          <div className="rise mt-9 sm:mt-11" style={{ ["--d" as string]: "1s" }}>
            <button className="btn-gold" onClick={onEnter}>
              <span>{THRESHOLD.cta}</span>
              <ArrowRight className="arrow" size={15} />
            </button>
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
