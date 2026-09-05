import { useEffect } from "react";
import { CloseIcon, Logo, Ornament } from "./Brand";
import { NAV } from "../data/site";

interface MenuOverlayProps {
  open: boolean;
  onClose: () => void;
  onEnter?: () => void;
}

/** Full-screen menu used by the "MENU" link on the threshold and the mobile hamburger. */
export function MenuOverlay({ open, onClose, onEnter }: MenuOverlayProps) {
  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => e.key === "Escape" && onClose();
    window.addEventListener("keydown", onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      window.removeEventListener("keydown", onKey);
      document.body.style.overflow = prev;
    };
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div className="menu-overlay flex flex-col" role="dialog" aria-modal="true" aria-label="Menu">
      <div className="grain" />
      <div className="flex items-start justify-between px-5 pt-5 sm:px-10 sm:pt-7">
        <Logo onClick={onClose} />
        <button className="icon-btn nav-link flex items-center gap-2 pt-2" onClick={onClose}>
          Close <CloseIcon size={18} />
        </button>
      </div>
      <nav className="flex flex-1 flex-col items-center justify-center gap-5 px-6 text-center sm:gap-6">
        {NAV.map((item, i) => (
          <a
            key={item.label}
            href={item.href}
            className="menu-link rise"
            style={{ ["--d" as string]: `${0.1 + i * 0.06}s` }}
            onClick={() => {
              onClose();
              if (onEnter) onEnter();
            }}
          >
            {item.label}
          </a>
        ))}
      </nav>
      <div className="flex flex-col items-center gap-3 pb-8 text-gold/80">
        <Ornament size={16} />
        <p className="smallcaps text-[0.58rem] text-cream/60">Same Roots. Different Generations. Always Home.</p>
      </div>
    </div>
  );
}
