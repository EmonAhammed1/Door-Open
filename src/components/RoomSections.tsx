import { useState } from "react";
import { ArrowRight, Ornament, ROOM_ICONS, SOCIAL_ICONS } from "./Brand";
import { BRAND, FOOTER, HOTSPOTS, type Hotspot } from "../data/site";

/**
 * Below-the-fold homepage sections:
 *  1. "Six Doors Within the Room" — cards mirroring the table hotspots
 *  2. About + Connect (newsletter)
 *  3. Footer (logo · legal · socials)
 */
export function RoomSections({ onOpenHotspot }: { onOpenHotspot: (h: Hotspot) => void }) {
  const [email, setEmail] = useState("");
  const [joined, setJoined] = useState(false);

  return (
    <>
      {/* ---------------- SIX DOORS ---------------- */}
      <section id="the-rooms" className="relative overflow-hidden bg-ink px-5 py-24 sm:px-10 sm:py-32">
        <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_60%_40%_at_50%_0%,rgba(224,138,60,0.12),transparent_60%)]" />
        <div className="grain" />
        <div className="relative mx-auto max-w-6xl">
          <div className="mx-auto max-w-2xl text-center">
            <p className="smallcaps text-[0.6rem] text-gold">Step further in</p>
            <h2 className="display mt-4 text-[clamp(1.6rem,3.2vw,2.6rem)] leading-tight text-cream">
              Six Doors Within the Room
            </h2>
            <Ornament className="mx-auto my-5 text-gold" size={18} />
            <p className="body text-[1.2rem] leading-[1.6] text-cream-2">
              Everything on the table leads somewhere. Choose a door — each one opens onto a practice, a place, or a
              person waiting to be remembered.
            </p>
          </div>

          <div className="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {HOTSPOTS.map((h, i) => {
              const Icon = ROOM_ICONS[h.id];
              return (
                <button
                  key={h.id}
                  type="button"
                  id={h.id === "the-rooms" ? undefined : h.id}
                  className="room-card text-left"
                  onClick={() => onOpenHotspot(h)}
                  style={{ animationDelay: `${i * 0.08}s` }}
                >
                  <div className="room-card__icon mb-6">{Icon && <Icon size={34} />}</div>
                  <p className="smallcaps text-[0.55rem] text-gold/80">{h.kicker}</p>
                  <h3 className="display mt-2 text-[1.35rem] text-cream">{h.label}</h3>
                  <p className="body mt-3 text-[1.08rem] leading-[1.55] text-cream-2/90">{h.blurb}</p>
                  <span className="room-card__cta mt-6">
                    {h.cta} <span>→</span>
                  </span>
                </button>
              );
            })}
          </div>
        </div>
      </section>

      {/* ---------------- ABOUT + CONNECT ---------------- */}
      <section className="relative overflow-hidden border-t border-gold/15 bg-ink-2 px-5 py-24 sm:px-10 sm:py-28">
        <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_50%_60%_at_100%_100%,rgba(224,138,60,0.14),transparent_60%)]" />
        <div className="grain" />
        <div className="relative mx-auto grid max-w-6xl gap-16 lg:grid-cols-2 lg:gap-24">
          <div id="about">
            <p className="smallcaps text-[0.6rem] text-gold">About the Room</p>
            <h2 className="display mt-4 text-[clamp(1.5rem,2.8vw,2.3rem)] leading-tight text-cream">
              Built from memory. Kept by hand.
            </h2>
            <Ornament className="my-5 text-gold" size={18} />
            <p className="body text-[1.2rem] leading-[1.65] text-cream-2">
              The Ancestors’ Room began as one family’s altar and grew into a gathering place — for the ones learning
              their lineage for the first time, and the ones who never forgot. Here, tradition is not a museum. It is
              practiced, questioned, cooked, sung, and passed forward.
            </p>
            <blockquote className="body mt-8 border-l border-gold/50 pl-6 text-[1.35rem] italic leading-[1.5] text-gold-light/90">
              “Same roots. Different generations. Always home.”
            </blockquote>
            <a href="#about" className="room-card__cta mt-8">
              Read our story <span>→</span>
            </a>
          </div>

          <div id="connect" className="relative">
            <div className="border border-gold/25 bg-ink/60 p-8 shadow-[0_30px_80px_rgba(0,0,0,0.5)] sm:p-10">
              <p className="smallcaps text-[0.6rem] text-gold">Connect</p>
              <h3 className="display mt-3 text-[1.5rem] text-cream">Join the Circle</h3>
              <p className="body mt-3 text-[1.12rem] leading-[1.6] text-cream-2">
                New-moon letters, gathering dates, teachings from the journal, and first word when the apothecary
                shelves are restocked. No noise. Only what matters.
              </p>
              {joined ? (
                <div className="mt-8 border border-gold/30 bg-gold/10 px-5 py-6 text-center">
                  <Ornament className="mx-auto mb-3 text-gold-light" size={18} />
                  <p className="display text-[0.95rem] tracking-[0.1em] text-cream">Welcome home.</p>
                  <p className="body mt-1 text-cream-2 italic">Your first letter arrives with the next new moon.</p>
                </div>
              ) : (
                <form
                  className="mt-6"
                  onSubmit={(e) => {
                    e.preventDefault();
                    if (email.trim()) setJoined(true);
                  }}
                >
                  <label className="smallcaps block text-[0.52rem] text-cream/60" htmlFor="join-email">
                    Your email
                  </label>
                  <input
                    id="join-email"
                    type="email"
                    required
                    className="input-line"
                    placeholder="where shall we write to you?"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                  />
                  <button type="submit" className="btn-solid mt-6 inline-flex w-full items-center justify-center gap-3 sm:w-auto">
                    Join the Circle <ArrowRight size={14} />
                  </button>
                  <p className="body mt-4 text-[0.95rem] text-cream/50 italic">
                    You may leave the circle at any time. Your details are never shared.
                  </p>
                </form>
              )}
            </div>
          </div>
        </div>
      </section>

      {/* ---------------- FOOTER ---------------- */}
      <footer className="relative border-t border-gold/20 bg-ink px-5 py-8 sm:px-10">
        <div className="grain" />
        <div className="relative mx-auto flex max-w-7xl flex-col items-center gap-7 lg:flex-row lg:items-center lg:justify-between">
          <div className="flex flex-col items-center lg:items-start">
            <span className="display text-[0.78rem] tracking-[0.2em] text-cream">{BRAND.name.toUpperCase()}</span>
            <span className="display mt-1.5 text-[0.55rem] tracking-[0.3em] text-gold/90 uppercase">{BRAND.tagline}</span>
          </div>

          <nav className="flex flex-wrap items-center justify-center gap-x-8 gap-y-3" aria-label="Legal">
            {FOOTER.legal.map((l) => (
              <a key={l.label} href={l.href} className="body text-[1rem] text-cream/75 transition-colors hover:text-gold-light">
                {l.label}
              </a>
            ))}
          </nav>

          <div className="flex items-center gap-5">
            <span className="body text-[1rem] text-cream/75 italic">{FOOTER.followLabel}</span>
            <div className="flex items-center gap-4">
              {FOOTER.socials.map((s) => {
                const Icon = SOCIAL_ICONS[s];
                return (
                  <a key={s} href={`#${s}`} className="icon-btn" aria-label={s}>
                    <Icon size={17} />
                  </a>
                );
              })}
            </div>
          </div>
        </div>
        <div className="relative mx-auto mt-7 flex max-w-7xl items-center justify-center gap-4 text-gold/50">
          <span className="hairline w-24" />
          <Ornament size={14} />
          <span className="hairline w-24" />
        </div>
        <p className="body relative mt-4 text-center text-[0.9rem] text-cream/40">
          © {new Date().getFullYear()} {BRAND.name}. All rights reserved.
        </p>
      </footer>
    </>
  );
}
