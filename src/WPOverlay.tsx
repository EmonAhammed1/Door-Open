/**
 * WordPress Overlay Entry — reads config from URL params injected by the PHP plugin
 * ──────────────────────────────────────────────────────────────────────────────────
 * URL params (injected by PHP plugin based on settings):
 *   ?trigger=auto|click   — auto-start vs wait for button click
 *   ?btn=<text>           — button label (URL encoded)
 *   ?redirect=<url>       — where to navigate after animation (widget mode)
 *
 * Modes:
 *  "auto"  — doors open automatically after a short delay (site intro mode)
 *  "click" — a styled button is shown; doors open only when clicked (widget/shortcode mode)
 *
 * After animation completes:
 *  • Sends postMessage({ type: "door-animation-done" }) to parent WP page
 *  • If redirect URL is set → navigate to it (for widget mode where a new page opens)
 */

import { useCallback, useEffect, useRef, useState } from "react";
import { WPDoorOverlay, type WPPhase } from "./components/drumi/WPDoorOverlay";
import { useDrumiSound } from "./hooks/useDrumiSound";

// ── Read URL params set by the PHP plugin ─────────────────────────────────────
function getParam(key: string, fallback = "") {
  try {
    return new URLSearchParams(window.location.search).get(key) ?? fallback;
  } catch {
    return fallback;
  }
}

const TRIGGER_MODE  = (getParam("trigger", "auto") as "auto" | "click");
const BUTTON_TEXT   = getParam("btn", "JOIN THE JOURNEY");
const REDIRECT_URL  = getParam("redirect", "");

// ── Timings ───────────────────────────────────────────────────────────────────
const DOOR_SWING_MS   = 200;  // delay before phase → "opening"
const DOOR_OPEN_MS    = 1400; // door panel swing duration
const AUTO_START_DELAY = 800; // ms before auto-trigger fires (lets WP page paint)

export default function WPOverlay() {
  const [phase, setPhase]       = useState<WPPhase>("idle");
  const { playDoorOpen }        = useDrumiSound();
  const enteringRef             = useRef(false);
  const timers                  = useRef<number[]>([]);

  // ── Trigger the door open sequence ────────────────────────────────────────
  const handleEnter = useCallback(() => {
    if (phase !== "idle" || enteringRef.current) return;
    enteringRef.current = true;
    playDoorOpen();

    timers.current.push(
      window.setTimeout(() => setPhase("opening"), DOOR_SWING_MS)
    );
    timers.current.push(
      window.setTimeout(() => setPhase("entering"), DOOR_SWING_MS + DOOR_OPEN_MS)
    );
  }, [phase, playDoorOpen]);

  // ── Animation complete — signal parent WP page ────────────────────────────
  const handleArrived = useCallback(() => {
    setPhase("done");

    // Tell the WP plugin overlay wrapper to fade out
    try {
      window.parent.postMessage({ type: "door-animation-done" }, "*");
    } catch {
      window.dispatchEvent(new CustomEvent("door-animation-done"));
    }

    // Widget mode: navigate to the target page after animation
    if (REDIRECT_URL) {
      setTimeout(() => {
        try { window.parent.location.href = REDIRECT_URL; }
        catch { window.location.href = REDIRECT_URL; }
      }, 300);
    }
  }, []);

  // ── Auto-start (site intro mode) ──────────────────────────────────────────
  useEffect(() => {
    if (TRIGGER_MODE !== "auto") return;
    const id = window.setTimeout(handleEnter, AUTO_START_DELAY);
    timers.current.push(id);
    return () => timers.current.forEach(clearTimeout);
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  // Cleanup on unmount
  useEffect(() => () => timers.current.forEach(clearTimeout), []);

  return (
    <WPDoorOverlay
      phase={phase}
      onEnter={handleEnter}
      onArrived={handleArrived}
      buttonText={BUTTON_TEXT}
      triggerMode={TRIGGER_MODE}
    />
  );
}
