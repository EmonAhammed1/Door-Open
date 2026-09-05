import { Ornament } from "./Brand";
import { PARCHMENT_WORDS } from "../data/site";
import { cn } from "../utils/cn";

/**
 * A hanging parchment / cloth banner with the six words stacked vertically.
 * `hanging` adds a rod + cords so it reads as a wall banner (homepage right wall).
 */
export function Parchment({
  className,
  hanging = false,
  words = PARCHMENT_WORDS,
  style,
}: {
  className?: string;
  hanging?: boolean;
  words?: string[];
  style?: React.CSSProperties;
}) {
  return (
    <div className={cn("pointer-events-none select-none", className)} style={style} aria-hidden="true">
      {hanging && (
        <div className="relative mx-auto mb-[-2px] h-7 w-[112%] -translate-x-[5.5%]">
          {/* rod */}
          <span className="absolute left-0 right-0 top-5 h-[3px] rounded-full bg-gradient-to-r from-[#2a1c10] via-[#5a4028] to-[#2a1c10] shadow-[0_2px_6px_rgba(0,0,0,.7)]" />
          {/* cords */}
          <span className="absolute left-[10%] top-0 h-5 w-px bg-gold-deep/70" />
          <span className="absolute right-[10%] top-0 h-5 w-px bg-gold-deep/70" />
        </div>
      )}
      <div className={cn("parchment flex w-full flex-col items-center px-3 py-6", hanging && "banner-sway")}>
        <Ornament size={13} className="mb-3 text-[#4a3722]/70" />
        <ul className="flex flex-col items-center gap-[0.95em]">
          {words.map((w) => (
            <li key={w} className="parchment-word">
              {w}
            </li>
          ))}
        </ul>
        <Ornament size={13} className="mt-3 text-[#4a3722]/70" />
      </div>
    </div>
  );
}
