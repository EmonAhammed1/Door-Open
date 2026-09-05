import { useEffect } from "react";
import { ArrowRight, CloseIcon, Ornament, PlusIcon, ROOM_ICONS } from "./Brand";
import { HOTSPOTS, type Hotspot } from "../data/site";
import { toPx, type CoverGeometry } from "../hooks/useCoverGeometry";
import { cn } from "../utils/cn";

/* ------------------------------------------------------------------ */
/* "+" markers pinned to objects on the table                          */
/* ------------------------------------------------------------------ */
export function HotspotLayer({
  geo,
  activeId,
  onOpen,
  visible,
}: {
  geo: CoverGeometry | null;
  activeId: string | null;
  onOpen: (h: Hotspot) => void;
  visible: boolean;
}) {
  if (!geo || !visible) return null;
  return (
    <div className="absolute inset-0 z-10 hidden md:block" aria-label="Explore the table">
      {HOTSPOTS.map((h, i) => {
        const p = toPx(geo, h.x, h.y);
        return (
          <button
            key={h.id}
            type="button"
            className={cn("hotspot", h.flip && "hotspot--flip", activeId === h.id && "is-active")}
            style={{ left: p.x, top: p.y, ["--d" as string]: `${0.9 + i * 0.12}s` }}
            onClick={() => onOpen(h)}
            aria-haspopup="dialog"
            aria-label={`Open ${h.label}`}
          >
            <span className="hotspot__dot">
              <PlusIcon className="hotspot__plus" />
            </span>
            <span className="hotspot__label">{h.label}</span>
          </button>
        );
      })}
    </div>
  );
}

/* ------------------------------------------------------------------ */
/* Mobile fallback: horizontally scrolling chips                       */
/* ------------------------------------------------------------------ */
export function HotspotChips({ onOpen, className }: { onOpen: (h: Hotspot) => void; className?: string }) {
  return (
    <div className={cn("md:hidden", className)}>
      <p className="smallcaps mb-2 px-5 text-[0.52rem] text-gold-light/80">Explore the table</p>
      <div className="no-scrollbar flex snap-x snap-mandatory gap-2.5 overflow-x-auto px-5 pb-1">
        {HOTSPOTS.map((h) => (
          <button key={h.id} type="button" className="chip" onClick={() => onOpen(h)}>
            <span className="chip__dot">
              <PlusIcon size={10} />
            </span>
            {h.label}
          </button>
        ))}
        <span className="w-2 flex-shrink-0" />
      </div>
    </div>
  );
}

/* ------------------------------------------------------------------ */
/* Modal                                                               */
/* ------------------------------------------------------------------ */
export function HotspotModal({
  hotspot,
  onClose,
  onNavigate,
}: {
  hotspot: Hotspot | null;
  onClose: () => void;
  onNavigate: (h: Hotspot) => void;
}) {
  useEffect(() => {
    if (!hotspot) return;
    const idx = HOTSPOTS.findIndex((h) => h.id === hotspot.id);
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") onClose();
      if (e.key === "ArrowRight") onNavigate(HOTSPOTS[(idx + 1) % HOTSPOTS.length]);
      if (e.key === "ArrowLeft") onNavigate(HOTSPOTS[(idx - 1 + HOTSPOTS.length) % HOTSPOTS.length]);
    };
    window.addEventListener("keydown", onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      window.removeEventListener("keydown", onKey);
      document.body.style.overflow = prev;
    };
  }, [hotspot, onClose, onNavigate]);

  if (!hotspot) return null;
  const Icon = ROOM_ICONS[hotspot.id];
  const idx = HOTSPOTS.findIndex((h) => h.id === hotspot.id);
  const prev = HOTSPOTS[(idx - 1 + HOTSPOTS.length) % HOTSPOTS.length];
  const next = HOTSPOTS[(idx + 1) % HOTSPOTS.length];

  return (
    <div className="modal-backdrop flex items-center justify-center p-4" onClick={onClose}>
      <div
        key={hotspot.id}
        className="modal-card px-8 pb-8 pt-10 text-center sm:px-12"
        role="dialog"
        aria-modal="true"
        aria-labelledby="hotspot-title"
        onClick={(e) => e.stopPropagation()}
      >
        <button
          type="button"
          className="icon-btn absolute right-4 top-4 p-2"
          onClick={onClose}
          aria-label="Close"
        >
          <CloseIcon size={18} />
        </button>

        <div className="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full border border-gold/40 bg-ink/60 text-gold-light shadow-[0_0_40px_rgba(224,138,60,0.25)]">
          {Icon && <Icon size={30} />}
        </div>
        <p className="smallcaps text-[0.58rem] text-gold">{hotspot.kicker}</p>
        <h3 id="hotspot-title" className="display mt-3 text-[1.55rem] leading-tight text-cream sm:text-[1.8rem]">
          {hotspot.label}
        </h3>
        <Ornament size={16} className="mx-auto my-4 text-gold" />
        <p className="body text-[1.12rem] leading-[1.6] text-cream-2">{hotspot.blurb}</p>

        <a href={hotspot.href} className="btn-gold btn-gold--sm mt-8" onClick={onClose}>
          <span>{hotspot.cta}</span>
          <ArrowRight className="arrow" size={14} />
        </a>

        <div className="mt-8 flex items-center justify-between border-t border-gold/15 pt-4">
          <button type="button" className="nav-link" onClick={() => onNavigate(prev)}>
            ← {prev.label}
          </button>
          <span className="display text-[0.6rem] tracking-[0.3em] text-gold/60">
            {idx + 1} / {HOTSPOTS.length}
          </span>
          <button type="button" className="nav-link" onClick={() => onNavigate(next)}>
            {next.label} →
          </button>
        </div>
      </div>
    </div>
  );
}
