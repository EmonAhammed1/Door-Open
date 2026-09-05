import { useEffect, useRef } from "react";
import doorsImg from "../assets/threshold-doors.jpg";
import roomImg from "../assets/room-interior.jpg";
import { computeCover, toPx, useElementSize, useNaturalSize } from "../hooks/useCoverGeometry";
import { BRAND, DOOR_RECT, LINTEL_THRESHOLD } from "../data/site";
import { cn } from "../utils/cn";

export type DoorPhase = "idle" | "opening" | "entering" | "done";

interface DoorStageProps {
  phase: DoorPhase;
  onArrived: () => void;
}

/** Duration of the camera "walk-through" (ms). */
const ENTER_MS = 1800;
const easeInOut = (t: number) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
const easeOut = (t: number) => 1 - Math.pow(1 - t, 3);

/**
 * Layer stack (all inside one scaled "scene" so alignment never drifts):
 *   1. base       – the threshold artwork (walls, frame, closed doors)
 *   2. roomclip   – the room artwork, clipped to the doorway opening
 *   3. panels     – two copies of the door-leaf pixels, hinged with 3D rotateY
 *   4. lintel     – editable carved text over the plank
 * On "opening" the panels swing inward; on "entering" the scene zooms toward
 * the doorway while the clip expands and the threshold fades, landing on the
 * exact cover-fitted room image the homepage uses (seamless hand-off).
 */
export function DoorStage({ phase, onArrived }: DoorStageProps) {
  const stageRef = useRef<HTMLDivElement>(null);
  const sceneRef = useRef<HTMLDivElement>(null);
  const baseRef = useRef<HTMLDivElement>(null);
  const clipRef = useRef<HTMLDivElement>(null);
  const roomInnerRef = useRef<HTMLDivElement>(null);
  const panelsRef = useRef<HTMLDivElement>(null);
  const lintelRef = useRef<HTMLDivElement>(null);
  const glowRef = useRef<HTMLDivElement>(null);
  const seamRef = useRef<HTMLDivElement>(null);

  const natural = useNaturalSize(doorsImg);
  const { w: cwLive, h: chLive } = useElementSize(stageRef);
  const live = natural && cwLive && chLive ? { geo: computeCover(cwLive, chLive, natural.w, natural.h), cw: cwLive, ch: chLive } : null;
  // Freeze the measured frame while the camera walks through, so a late resize
  // (e.g. a scrollbar appearing) can't re-write the panel/clip geometry mid-animation.
  const frozen = useRef<typeof live>(null);
  if (phase === "entering") {
    if (!frozen.current && live) frozen.current = live;
  } else {
    frozen.current = null;
  }
  const frame = frozen.current ?? live;
  const geo = frame?.geo ?? null;
  const cw = frame?.cw ?? 0;
  const ch = frame?.ch ?? 0;

  // Door leaf rectangle + hinge centre, in stage pixels
  let rect: { left: number; top: number; width: number; height: number } | null = null;
  let center = { x: cw / 2, y: ch / 2 };
  if (geo) {
    const tl = toPx(geo, DOOR_RECT.left, DOOR_RECT.top);
    const br = toPx(geo, DOOR_RECT.right, DOOR_RECT.bottom);
    rect = { left: tl.x, top: tl.y, width: br.x - tl.x, height: br.y - tl.y };
    center = { x: (tl.x + br.x) / 2, y: (tl.y + br.y) / 2 };
  }
  const rectRef = useRef(rect);
  rectRef.current = rect;
  const centerRef = useRef(center);
  centerRef.current = center;
  const sizeRef = useRef({ cw, ch });
  sizeRef.current = { cw, ch };

  /* ---------------- camera walk-through (rAF) ---------------- */
  useEffect(() => {
    if (phase !== "entering") return;
    const r = rectRef.current;
    const P = centerRef.current;
    const { cw, ch } = sizeRef.current;
    const scene = sceneRef.current;
    const base = baseRef.current;
    const clip = clipRef.current;
    const inner = roomInnerRef.current;
    const panels = panelsRef.current;
    const lintel = lintelRef.current;
    const glow = glowRef.current;
    if (!r || !scene || !base || !clip || !inner || !panels || !glow) {
      onArrived();
      return;
    }
    // Scale needed for the doorway to swallow the viewport (about the hinge centre)
    const need = Math.max(
      P.x / Math.max(1, P.x - r.left),
      (cw - P.x) / Math.max(1, r.left + r.width - P.x),
      P.y / Math.max(1, P.y - r.top),
      (ch - P.y) / Math.max(1, r.top + r.height - P.y),
    );
    const sEnd = Math.min(3.2, need * 1.05);
    const inset0 = { t: r.top, rgt: cw - r.left - r.width, b: ch - r.top - r.height, l: r.left };
    const start = performance.now();
    let raf = 0;

    const frame = (now: number) => {
      const t = Math.min(1, (now - start) / ENTER_MS);
      const e = easeInOut(t);
      const s = 1 + (sEnd - 1) * e;
      const k = 1.3 - 0.3 * easeOut(t); // room settles from 1.3 → 1
      scene.style.transform = `scale(${s})`;
      inner.style.transform = `scale(${k / s})`;
      const shrink = 1 - easeOut(Math.min(1, t * 1.15));
      clip.style.clipPath = `inset(${inset0.t * shrink}px ${inset0.rgt * shrink}px ${inset0.b * shrink}px ${inset0.l * shrink}px)`;
      const op = String(1 - easeInOut(Math.min(1, t / 0.7)));
      base.style.opacity = op;
      panels.style.opacity = op;
      if (lintel) lintel.style.opacity = op;
      glow.style.opacity = String(t < 0.4 ? 0.55 + 0.45 * (t / 0.4) : Math.max(0, 1 - (t - 0.4) / 0.6));
      if (t < 1) {
        raf = requestAnimationFrame(frame);
      } else {
        setTimeout(() => {
          if (stageRef.current) {
            stageRef.current.style.transition = "opacity 800ms cubic-bezier(0.4, 0, 0.2, 1)";
            stageRef.current.style.opacity = "0";
            setTimeout(onArrived, 800);
          } else {
            onArrived();
          }
        }, 250);
      }
    };
    raf = requestAnimationFrame(frame);
    return () => cancelAnimationFrame(raf);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [phase]);

  const bg = geo
    ? {
        backgroundImage: `url(${doorsImg})`,
        backgroundSize: `${geo.dispW}px ${geo.dispH}px`,
      }
    : { backgroundImage: `url(${doorsImg})`, backgroundSize: "cover", backgroundPosition: "center" };

  const origin = `${center.x}px ${center.y}px`;
  const lintelPos = geo ? toPx(geo, LINTEL_THRESHOLD.x, LINTEL_THRESHOLD.y) : null;
  const entering = phase === "entering";

  return (
    <div
      ref={stageRef}
      className={cn("door-stage", phase === "opening" && "is-open", entering && "is-entering")}
      aria-hidden="true"
    >
      <div ref={sceneRef} className="door-scene" style={{ transformOrigin: origin }}>
        {/* 1. threshold artwork */}
        <div
          ref={baseRef}
          className="door-base"
          style={{ ...bg, backgroundPosition: geo ? `${geo.offX}px ${geo.offY}px` : "center" }}
        />

        {/* 2. the room, visible only through the doorway */}
        <div
          ref={clipRef}
          className="door-roomclip"
          style={
            rect
              ? {
                  // rAF overrides this inline value during "entering"; React leaves it alone
                  // because the prop value itself doesn't change between renders.
                  clipPath: `inset(${rect.top}px ${cw - rect.left - rect.width}px ${ch - rect.top - rect.height}px ${rect.left}px)`,
                }
              : undefined
          }
        >
          <div
            ref={roomInnerRef}
            className="door-roominner"
            style={{
              backgroundImage: `url(${roomImg})`,
              transformOrigin: origin,
              transform: "scale(1.3)",
            }}
          />
        </div>

        {/* 3. door leaves */}
        <div ref={panelsRef} className="door-panels">
          {rect && geo && (
            <>
              <div
                className="door-panel door-panel--left"
                style={{
                  ...bg,
                  left: rect.left,
                  top: rect.top,
                  width: rect.width / 2 + 0.5,
                  height: rect.height,
                  backgroundPosition: `${geo.offX - rect.left}px ${geo.offY - rect.top}px`,
                }}
              >
                <span className="door-panel__shade" />
              </div>
              <div
                className="door-panel door-panel--right"
                style={{
                  ...bg,
                  left: rect.left + rect.width / 2 - 0.5,
                  top: rect.top,
                  width: rect.width / 2 + 0.5,
                  height: rect.height,
                  backgroundPosition: `${geo.offX - (rect.left + rect.width / 2 - 0.5)}px ${geo.offY - rect.top}px`,
                }}
              >
                <span className="door-panel__shade" />
              </div>
              {/* flickering light in the seam before the doors open */}
              <div
                ref={seamRef}
                className="door-seam"
                style={{
                  left: center.x - rect.width * 0.22,
                  top: rect.top - rect.height * 0.05,
                  width: rect.width * 0.44,
                  height: rect.height * 1.18,
                }}
              />
            </>
          )}
        </div>

        {/* 4. carved lintel text (editable) */}
        {lintelPos && (
          <div
            ref={lintelRef}
            className="lintel absolute -translate-x-1/2 -translate-y-1/2"
            style={{ left: lintelPos.x, top: lintelPos.y, fontSize: geo!.dispW * LINTEL_THRESHOLD.size }}
          >
            <span className="mr-[0.9em] opacity-80">✠</span>
            {BRAND.lintel}
            <span className="ml-[0.9em] opacity-80">✠</span>
          </div>
        )}
      </div>

      {/* light bloom through the doorway (outside the scaled scene) */}
      <div
        ref={glowRef}
        className="door-glow"
        style={{ ["--gx" as string]: `${center.x}px`, ["--gy" as string]: `${center.y}px` }}
      />
    </div>
  );
}
