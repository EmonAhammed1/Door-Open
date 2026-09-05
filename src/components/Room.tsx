import { useCallback, useRef, useState } from "react";
import roomImg from "../assets/room-interior.jpg";
import { AccountIcon, CartIcon, Logo, MenuIcon, Ornament, SearchIcon } from "./Brand";
import { Atmosphere } from "./Atmosphere";
import { Parchment } from "./Parchment";
import { HotspotChips, HotspotLayer, HotspotModal } from "./Hotspots";
import { RoomSections } from "./RoomSections";
import { BRAND, HOME, LINTEL_ROOM, NAV, type Hotspot } from "../data/site";
import { toPx, useCoverGeometry } from "../hooks/useCoverGeometry";
import { cn } from "../utils/cn";

interface RoomProps {
  arrived: boolean;
  soundOn: boolean;
  onToggleSound: () => void;
  onOpenMenu: () => void;
  onReturnToThreshold: () => void;
}

/**
 * HOMEPAGE — "Inside the Room".
 * The hero uses the same cover-fitted artwork as the transition's final frame,
 * so the hand-off from DoorStage is invisible.
 */
export function Room({ arrived, soundOn, onToggleSound, onOpenMenu, onReturnToThreshold }: RoomProps) {
  const heroRef = useRef<HTMLElement>(null);
  const geo = useCoverGeometry(heroRef, roomImg);
  const [active, setActive] = useState<Hotspot | null>(null);
  const open = useCallback((h: Hotspot) => setActive(h), []);
  const close = useCallback(() => setActive(null), []);
  const lintel = geo ? toPx(geo, LINTEL_ROOM.x, LINTEL_ROOM.y) : null;
  const d = (s: number) => ({ ["--d" as string]: `${s}s` });

  return (
    <main id="home" className="relative bg-ink text-cream">
      {/* ============================ HERO ============================ */}
      <section ref={heroRef} className="relative h-[100svh] min-h-[620px] overflow-hidden">
        <div className="absolute inset-0 bg-cover bg-center" style={{ backgroundImage: `url(${roomImg})` }} />
        {/* atmosphere + legibility scrims fade in after the door transition hands off */}
        <div
          className={cn(
            "pointer-events-none absolute inset-0 transition-opacity duration-[1600ms] ease-out",
            arrived ? "opacity-100" : "opacity-0",
          )}
        >
          <Atmosphere embers={18} />
          <div className="absolute inset-x-0 top-0 h-44 bg-gradient-to-b from-ink/85 via-ink/30 to-transparent" />
          <div className="absolute inset-x-0 bottom-0 h-64 bg-gradient-to-t from-ink via-ink/55 to-transparent" />
        </div>

        {/* carved lintel text */}
        {lintel && geo && (
          <div
            className={cn("lintel absolute -translate-x-1/2 -translate-y-1/2 opacity-0", arrived && "fade-in")}
            style={{ left: lintel.x, top: lintel.y, fontSize: geo.dispW * LINTEL_ROOM.size, ...d(1.2) }}
          >
            <span className="mr-[0.9em] opacity-80">✠</span>
            {BRAND.lintel}
            <span className="ml-[0.9em] opacity-80">✠</span>
          </div>
        )}

        {/* ---------- top navigation ---------- */}
        <header
          className={cn(
            "absolute inset-x-0 top-0 z-20 flex items-start justify-between px-5 pt-5 opacity-0 sm:px-10 sm:pt-7",
            arrived && "fade-in",
          )}
          style={d(0.2)}
        >
          <Logo onClick={onReturnToThreshold} />
          <nav className="hidden items-center gap-6 pt-3 lg:flex xl:gap-8" aria-label="Primary">
            {NAV.map((item) => (
              <a key={item.label} href={item.href} className={cn("nav-link", item.active && "is-active")}>
                {item.label}
              </a>
            ))}
          </nav>
          <div className="flex items-center gap-4 pt-2 sm:gap-5">
            <button className="icon-btn" aria-label="Search">
              <SearchIcon size={21} />
            </button>
            <button className="icon-btn hidden sm:block" aria-label="Account">
              <AccountIcon size={21} />
            </button>
            <button className="icon-btn" aria-label="Cart">
              <CartIcon size={21} />
            </button>
            <button
              className={cn("icon-btn flex items-center gap-2 pl-1", soundOn && "text-gold-light")}
              onClick={onToggleSound}
              aria-pressed={soundOn}
              aria-label="Toggle ambient sound"
              title="Ambient sound"
            >
              <span className={cn("eq", !soundOn && "is-off")} aria-hidden="true">
                <i />
                <i />
                <i />
                <i />
              </span>
            </button>
            <button className="icon-btn lg:hidden" aria-label="Menu" onClick={onOpenMenu}>
              <MenuIcon size={22} />
            </button>
          </div>
        </header>

        {/* ---------- headline ---------- */}
        <div className="absolute left-1/2 top-[40%] z-10 w-full max-w-3xl -translate-x-1/2 -translate-y-1/2 px-6 text-center">
          <p className={cn("smallcaps text-[0.6rem] text-gold-light/90 opacity-0", arrived && "rise")} style={d(0.5)}>
            {HOME.eyebrow}
          </p>
          <h1
            className={cn(
              "display text-glow mt-3 text-[clamp(1.9rem,4.4vw,3.7rem)] leading-[1.1] tracking-[0.03em] text-cream opacity-0",
              arrived && "rise",
            )}
            style={d(0.65)}
          >
            {HOME.title}
          </h1>
          <Ornament className={cn("mx-auto my-4 text-gold-light opacity-0", arrived && "rise")} size={20} />
          <p
            className={cn("smallcaps text-shadow-soft text-[0.6rem] leading-[2.1] text-cream/85 opacity-0", arrived && "rise")}
            style={d(0.95)}
          >
            {HOME.subtitle.map((l) => (
              <span key={l} className="block">
                {l}
              </span>
            ))}
          </p>
        </div>

        {/* ---------- hotspots (desktop / tablet) ---------- */}
        <HotspotLayer geo={geo} activeId={active?.id ?? null} onOpen={open} visible={arrived} />

        {/* ---------- banner on the right wall ---------- */}
        <Parchment
          hanging
          className={cn("absolute right-[3%] top-[12%] hidden w-[98px] opacity-0 xl:block", arrived && "fade-in")}
          style={d(1.1)}
        />

        {/* ---------- bottom-left ---------- */}
        <div
          className={cn(
            "body absolute bottom-7 left-5 z-10 text-[clamp(1.05rem,1.5vw,1.4rem)] italic leading-[1.4] text-gold-light/90 opacity-0 sm:bottom-12 sm:left-10",
            arrived && "rise",
          )}
          style={d(1.3)}
        >
          {HOME.bottomLeft.map((l) => (
            <span key={l} className="block">
              {l}
            </span>
          ))}
        </div>

        {/* ---------- bottom-right quote ---------- */}
        <div
          className={cn(
            "absolute bottom-12 right-10 z-10 hidden flex-col items-end text-right opacity-0 sm:flex",
            arrived && "rise",
          )}
          style={d(1.45)}
        >
          <Ornament size={16} className="mb-3 text-gold" />
          <p className="body text-[clamp(1.05rem,1.5vw,1.4rem)] italic leading-[1.4] text-cream/90">
            {HOME.quote.map((l) => (
              <span key={l} className="block">
                {l}
              </span>
            ))}
          </p>
        </div>

        {/* ---------- mobile: chips replace hotspots ---------- */}
        <HotspotChips
          onOpen={open}
          className={cn("absolute inset-x-0 bottom-[7.5rem] z-10 opacity-0", arrived && "fade-in")}
        />

        {/* ---------- scroll cue ---------- */}
        <a
          href="#the-rooms"
          className={cn(
            "absolute bottom-6 left-1/2 z-10 hidden -translate-x-1/2 flex-col items-center gap-2 text-gold/70 opacity-0 transition-colors hover:text-gold-light md:flex",
            arrived && "fade-in",
          )}
          style={d(2)}
          aria-label="Scroll to explore"
        >
          <span className="smallcaps text-[0.5rem]">Step further in</span>
          <span className="h-8 w-px bg-gradient-to-b from-gold/70 to-transparent" />
        </a>
      </section>

      {/* ============================ BELOW THE FOLD ============================ */}
      <RoomSections onOpenHotspot={open} />

      <HotspotModal hotspot={active} onClose={close} onNavigate={setActive} />
    </main>
  );
}
