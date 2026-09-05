/**
 * WordPress Overlay Mode — Entry Component
 * ────────────────────────────────────────
 * Shows the door animation as a transparent overlay over the WordPress site.
 * The stone arch frame has a transparent hole → the WP homepage shows through.
 *
 * Flow:
 *  1. Component mounts, auto-start timer fires (800ms)
 *  2. Doors swing open (phase: "opening") → door panels rotate away
 *  3. Camera walks through (phase: "entering") → scene zooms, arch fades out
 *  4. onArrived → postMessage("door-animation-done") → WP plugin fades the iframe
 */

import { useCallback, useEffect, useRef, useState } from "react";
import { WPDoorOverlay, type WPPhase } from "./components/drumi/WPDoorOverlay";
import { useDrumiSound } from "./hooks/useDrumiSound";

const TEXT_FADE_MS    = 200;
const DOOR_OPEN_MS    = 1400;
const AUTO_START_DELAY = 900; // wait for WP page to paint before auto-opening

export default function WPOverlay() {
  const [phase, setPhase] = useState<WPPhase>("idle");
  const { playDoorOpen } = useDrumiSound();
  const enteringRef = useRef(false);
  const timers = useRef<number[]>([]);

  const handleEnter = useCallback(() => {
    if (phase !== "idle" || enteringRef.current) return;
    enteringRef.current = true;
    playDoorOpen();

    timers.current.push(
      window.setTimeout(() => setPhase("opening"), TEXT_FADE_MS)
    );
    timers.current.push(
      window.setTimeout(() => setPhase("entering"), TEXT_FADE_MS + DOOR_OPEN_MS)
    );
  }, [phase, playDoorOpen]);

  const handleArrived = useCallback(() => {
    setPhase("done");
    // Notify the WordPress parent page — overlay should now fade out
    try {
      window.parent.postMessage({ type: "door-animation-done" }, "*");
    } catch {
      // Fallback if same-origin access is restricted
      window.dispatchEvent(new CustomEvent("door-animation-done"));
    }
  }, []);

  // Auto-start: open the doors after a short delay so the WP page is visible first
  useEffect(() => {
    const id = window.setTimeout(handleEnter, AUTO_START_DELAY);
    timers.current.push(id);
    return () => timers.current.forEach(clearTimeout);
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <WPDoorOverlay
      phase={phase}
      onArrived={handleArrived}
    />
  );
}
