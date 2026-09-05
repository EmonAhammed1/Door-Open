import { useEffect, useLayoutEffect, useState, type RefObject } from "react";

/**
 * Cover-fit geometry: replicates CSS `background-size: cover; background-position: center`
 * mathematically so we can pin DOM elements (door panels, hotspots, lintel text)
 * to exact features of the artwork at any viewport size.
 */
export interface CoverGeometry {
  cw: number; // container width
  ch: number; // container height
  dispW: number; // displayed image width
  dispH: number; // displayed image height
  offX: number; // image offset inside container (can be negative)
  offY: number;
}

export function computeCover(cw: number, ch: number, iw: number, ih: number): CoverGeometry {
  const scale = Math.max(cw / iw, ch / ih);
  const dispW = iw * scale;
  const dispH = ih * scale;
  return { cw, ch, dispW, dispH, offX: (cw - dispW) / 2, offY: (ch - dispH) / 2 };
}

/** Convert image fractions (0–1) into container pixels. */
export function toPx(g: CoverGeometry, fx: number, fy: number) {
  return { x: g.offX + fx * g.dispW, y: g.offY + fy * g.dispH };
}

/** Natural size of an image, resolved once it's loaded. */
export function useNaturalSize(src: string) {
  const [size, setSize] = useState<{ w: number; h: number } | null>(null);
  useEffect(() => {
    let alive = true;
    const img = new Image();
    img.onload = () => alive && setSize({ w: img.naturalWidth, h: img.naturalHeight });
    img.src = src;
    if (img.complete && img.naturalWidth) setSize({ w: img.naturalWidth, h: img.naturalHeight });
    return () => {
      alive = false;
    };
  }, [src]);
  return size;
}

/** Live size of a container element. */
export function useElementSize(ref: RefObject<HTMLElement | null>) {
  const [size, setSize] = useState({ w: 0, h: 0 });
  useLayoutEffect(() => {
    const el = ref.current;
    if (!el) return;
    const update = () => setSize({ w: el.clientWidth, h: el.clientHeight });
    update();
    const ro = new ResizeObserver(update);
    ro.observe(el);
    window.addEventListener("orientationchange", update);
    return () => {
      ro.disconnect();
      window.removeEventListener("orientationchange", update);
    };
  }, [ref]);
  return size;
}

/** Convenience: geometry for `src` covering the element in `ref` (null until both are known). */
export function useCoverGeometry(ref: RefObject<HTMLElement | null>, src: string): CoverGeometry | null {
  const natural = useNaturalSize(src);
  const { w, h } = useElementSize(ref);
  if (!natural || !w || !h) return null;
  return computeCover(w, h, natural.w, natural.h);
}
