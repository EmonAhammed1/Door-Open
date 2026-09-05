import type { ReactElement, SVGProps } from "react";
import { BRAND } from "../data/site";
import { cn } from "../utils/cn";

/* ------------------------------------------------------------------ */
/* Ornament — the small cross with a centre ring used throughout       */
/* ------------------------------------------------------------------ */
export function Ornament({ className, size = 22 }: { className?: string; size?: number }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1"
      strokeLinecap="round"
      className={className}
      aria-hidden="true"
    >
      <circle cx="12" cy="12" r="2.4" />
      <path d="M12 1.5v6M12 16.5v6M1.5 12h6M16.5 12h6" />
      <path d="M12 1.5l-1.6 2.2M12 1.5l1.6 2.2M12 22.5l-1.6-2.2M12 22.5l1.6-2.2M1.5 12l2.2-1.6M1.5 12l2.2 1.6M22.5 12l-2.2-1.6M22.5 12l-2.2 1.6" />
      <circle cx="12" cy="6" r="0.6" fill="currentColor" />
      <circle cx="12" cy="18" r="0.6" fill="currentColor" />
      <circle cx="6" cy="12" r="0.6" fill="currentColor" />
      <circle cx="18" cy="12" r="0.6" fill="currentColor" />
    </svg>
  );
}

/* Ornament with hairlines on either side */
export function OrnamentRule({ className, width = 140 }: { className?: string; width?: number }) {
  return (
    <div className={cn("flex items-center justify-center gap-3 text-gold", className)} style={{ width }}>
      <span className="h-px flex-1 bg-gradient-to-r from-transparent to-gold/60" />
      <Ornament size={16} />
      <span className="h-px flex-1 bg-gradient-to-l from-transparent to-gold/60" />
    </div>
  );
}

/* ------------------------------------------------------------------ */
/* Logo                                                                */
/* ------------------------------------------------------------------ */
export function Logo({
  className,
  compact = false,
  onClick,
}: {
  className?: string;
  compact?: boolean;
  onClick?: () => void;
}) {
  return (
    <a
      href="#home"
      onClick={(e) => {
        if (onClick) {
          e.preventDefault();
          onClick();
        }
      }}
      className={cn("group inline-flex flex-col items-center text-cream", className)}
      aria-label={BRAND.name}
    >
      <span
        className={cn(
          "display text-center leading-[1.05] tracking-[0.12em]",
          compact ? "text-[13px] sm:text-[15px]" : "text-[15px] sm:text-[19px] md:text-[21px]",
        )}
      >
        {BRAND.nameLines.map((l) => (
          <span key={l} className="block">
            {l}
          </span>
        ))}
      </span>
      <span className="my-1.5 flex items-center gap-1.5 text-gold/80">
        <span className="h-px w-5 bg-gold/50" />
        <Ornament size={11} />
        <span className="h-px w-5 bg-gold/50" />
      </span>
      <span
        className={cn(
          "display uppercase text-gold-light/85 transition-colors group-hover:text-gold-light",
          compact ? "text-[6.5px] tracking-[0.3em]" : "text-[7px] tracking-[0.32em] sm:text-[8px]",
        )}
      >
        {BRAND.tagline}
      </span>
    </a>
  );
}

/* ------------------------------------------------------------------ */
/* Icons                                                               */
/* ------------------------------------------------------------------ */
type IconProps = SVGProps<SVGSVGElement> & { size?: number };
const base = (size: number, props: IconProps) => ({
  width: size,
  height: size,
  viewBox: "0 0 24 24",
  fill: "none",
  stroke: "currentColor",
  strokeWidth: 1.3,
  strokeLinecap: "round" as const,
  strokeLinejoin: "round" as const,
  "aria-hidden": true,
  ...props,
});

export const SearchIcon = ({ size = 20, ...p }: IconProps) => (
  <svg {...base(size, p)}>
    <circle cx="11" cy="11" r="7" />
    <path d="M20 20l-3.8-3.8" />
  </svg>
);
export const AccountIcon = ({ size = 20, ...p }: IconProps) => (
  <svg {...base(size, p)}>
    <circle cx="12" cy="8" r="4" />
    <path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7" />
  </svg>
);
export const CartIcon = ({ size = 20, ...p }: IconProps) => (
  <svg {...base(size, p)}>
    <path d="M5 8h14l-1 12H6L5 8z" />
    <path d="M9 8V6a3 3 0 0 1 6 0v2" />
  </svg>
);
export const MenuIcon = ({ size = 20, ...p }: IconProps) => (
  <svg {...base(size, p)}>
    <path d="M3 7h18M3 12h18M3 17h18" />
  </svg>
);
export const CloseIcon = ({ size = 20, ...p }: IconProps) => (
  <svg {...base(size, p)}>
    <path d="M5 5l14 14M19 5L5 19" />
  </svg>
);
export const ArrowRight = ({ size = 16, ...p }: IconProps) => (
  <svg {...base(size, p)}>
    <path d="M3 12h17M14 6l6 6-6 6" />
  </svg>
);
export const PlusIcon = ({ size = 14, ...p }: IconProps) => (
  <svg {...base(size, p)} strokeWidth={1.4}>
    <path d="M12 3v18M3 12h18" />
  </svg>
);

/* Socials */
export const InstagramIcon = ({ size = 18, ...p }: IconProps) => (
  <svg {...base(size, p)}>
    <rect x="3" y="3" width="18" height="18" rx="5" />
    <circle cx="12" cy="12" r="4" />
    <circle cx="17.5" cy="6.5" r="0.8" fill="currentColor" />
  </svg>
);
export const FacebookIcon = ({ size = 18, ...p }: IconProps) => (
  <svg {...base(size, p)}>
    <path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z" />
  </svg>
);
export const YoutubeIcon = ({ size = 18, ...p }: IconProps) => (
  <svg {...base(size, p)}>
    <rect x="2.5" y="6" width="19" height="12" rx="4" />
    <path d="M10 9.5v5l4.5-2.5L10 9.5z" fill="currentColor" stroke="none" />
  </svg>
);
export const TiktokIcon = ({ size = 18, ...p }: IconProps) => (
  <svg {...base(size, p)}>
    <path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5" />
    <path d="M14 3c.5 3 2.5 5 5.5 5.5" />
  </svg>
);
export const PinterestIcon = ({ size = 18, ...p }: IconProps) => (
  <svg {...base(size, p)}>
    <circle cx="12" cy="12" r="9" />
    <path d="M10 21l2-7M12 14c2.5.5 4.5-1.5 4.5-4 0-2.5-2-4.2-4.6-4.2S7.5 7.6 7.5 10c0 1.2.5 2 1.2 2.4" />
  </svg>
);

export const SOCIAL_ICONS = {
  instagram: InstagramIcon,
  facebook: FacebookIcon,
  youtube: YoutubeIcon,
  tiktok: TiktokIcon,
  pinterest: PinterestIcon,
};

/* Room icons (used on the cards + modal) */
export const MortarIcon = ({ size = 32, ...p }: IconProps) => (
  <svg {...base(size, p)} strokeWidth={1}>
    <path d="M4 10h16c0 5-3 9-8 9s-8-4-8-9z" />
    <path d="M3 10h18" />
    <path d="M9 9.5L18 3.5l1.5 1.5-7 6" />
    <path d="M9 19v2h6v-2" />
  </svg>
);
export const CandleIcon = ({ size = 32, ...p }: IconProps) => (
  <svg {...base(size, p)} strokeWidth={1}>
    <path d="M12 2c1.6 2 2.4 3.4 2.4 4.6A2.4 2.4 0 0 1 12 9a2.4 2.4 0 0 1-2.4-2.4C9.6 5.4 10.4 4 12 2z" />
    <path d="M12 9v3" />
    <path d="M8 12h8v9H8z" />
    <path d="M5 21h14" />
  </svg>
);
export const DoorIcon = ({ size = 32, ...p }: IconProps) => (
  <svg {...base(size, p)} strokeWidth={1}>
    <path d="M5 21V9a7 7 0 0 1 14 0v12" />
    <path d="M3 21h18" />
    <path d="M12 21V9M8 21v-9M16 21v-9" />
    <circle cx="10.5" cy="15" r="0.5" fill="currentColor" />
  </svg>
);
export const HandsIcon = ({ size = 32, ...p }: IconProps) => (
  <svg {...base(size, p)} strokeWidth={1}>
    <path d="M12 3c1.2 1.5 1.8 2.6 1.8 3.5A1.8 1.8 0 0 1 12 8.3a1.8 1.8 0 0 1-1.8-1.8c0-.9.6-2 1.8-3.5z" />
    <path d="M3 13c2-2 4-2 6 0l3 3 3-3c2-2 4-2 6 0" />
    <path d="M4 15c2 3 5 5 8 5s6-2 8-5" />
  </svg>
);
export const QuillIcon = ({ size = 32, ...p }: IconProps) => (
  <svg {...base(size, p)} strokeWidth={1}>
    <path d="M20 4c-6 0-11 4-13 11l-3 5 5-3c7-2 11-7 11-13z" />
    <path d="M6 18L15 9" />
    <path d="M3 21h9" />
  </svg>
);
export const CircleKnotIcon = ({ size = 32, ...p }: IconProps) => (
  <svg {...base(size, p)} strokeWidth={1}>
    <circle cx="12" cy="12" r="8" />
    <circle cx="12" cy="8" r="3" />
    <circle cx="8.5" cy="14" r="3" />
    <circle cx="15.5" cy="14" r="3" />
  </svg>
);

export const ROOM_ICONS: Record<string, (p: IconProps) => ReactElement> = {
  apothecary: MortarIcon,
  shop: CandleIcon,
  "the-rooms": DoorIcon,
  services: HandsIcon,
  journal: QuillIcon,
  connect: CircleKnotIcon,
};
