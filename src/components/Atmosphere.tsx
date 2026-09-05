import { useMemo } from "react";

/**
 * Stackable atmosphere: vignette, breathing candle light, floating embers, film grain.
 * Purely decorative — pointer-events are disabled.
 */
export function Atmosphere({ embers = 16, flicker = true }: { embers?: number; flicker?: boolean }) {
  const motes = useMemo(
    () =>
      Array.from({ length: embers }, (_, i) => ({
        id: i,
        left: 8 + Math.random() * 84,
        dur: 14 + Math.random() * 16,
        delay: -Math.random() * 30,
        drift: (Math.random() - 0.5) * 120,
        size: 2 + Math.random() * 2.5,
        opacity: 0.4 + Math.random() * 0.5,
      })),
    [embers],
  );

  return (
    <div className="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
      {flicker && <div className="flicker" />}
      {motes.map((m) => (
        <span
          key={m.id}
          className="ember"
          style={{
            left: `${m.left}%`,
            width: m.size,
            height: m.size,
            animationDuration: `${m.dur}s`,
            animationDelay: `${m.delay}s`,
            ["--drift" as string]: `${m.drift}px`,
          }}
        />
      ))}
      <div className="vignette" />
      <div className="grain" />
    </div>
  );
}
