/**
 * WordPress Overlay Mode
 * ─────────────────────
 * This is a standalone entry for the WordPress intro overlay.
 * It shows ONLY the door animation (no inner room, no UI chrome).
 * 
 * Behaviour:
 *  • Preloads the WordPress site BEHIND the overlay (transparent background)
 *  • Auto-starts the door open animation after a short delay
 *  • When the walk-through is complete → postMessage('door-done') to parent
 *  • The parent WP page fades the overlay out
 */

import { useCallback, useEffect, useRef, useState } from "react";
import { DrumiThreshold, type DrumiPhase } from "./components/drumi/DrumiThreshold";
import { useDrumiSound } from "./hooks/useDrumiSound";

const TEXT_FADE_MS = 200;
const DOOR_OPEN_MS = 1400;
const AUTO_START_DELAY = 800; // ms before auto-opening doors

export default function WPOverlay() {
  const [phase, setPhase] = useState<DrumiPhase>("idle");
  const { soundOn, toggleSound, playDoorOpen } = useDrumiSound();
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
    setPhase("inside");
    // Notify the WordPress parent page — overlay is done, fade it out
    try {
      window.parent.postMessage({ type: "door-animation-done" }, "*");
    } catch {
      // same-origin fallback: dispatch custom event
      window.dispatchEvent(new CustomEvent("door-animation-done"));
    }
  }, []);

  // Auto-start the animation shortly after load
  useEffect(() => {
    const id = window.setTimeout(() => handleEnter(), AUTO_START_DELAY);
    timers.current.push(id);
    return () => timers.current.forEach(clearTimeout);
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  // Once "inside" phase → this component has nothing to render
  if (phase === "inside") return null;

  return (
    <DrumiThreshold
      phase={phase}
      soundOn={soundOn}
      onEnter={handleEnter}
      onArrived={handleArrived}
      onToggleSound={toggleSound}
    />
  );
}
