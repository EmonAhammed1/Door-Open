import { useCallback, useEffect, useRef, useState } from "react";
import { DoorStage, type DoorPhase } from "./components/DoorStage";
import { Threshold } from "./components/Threshold";
import { Room } from "./components/Room";
import { MenuOverlay } from "./components/MenuOverlay";
import { Handoff } from "./components/Handoff";
import { useAmbientSound } from "./hooks/useAmbientSound";
import { DrumiApp } from "./components/drumi/DrumiApp";

/** Timings (ms) — keep in sync with the CSS door transitions. */
const TEXT_FADE_MS = 250;
const DOOR_OPEN_MS = 1500;

export default function App() {
  const [viewMode, setViewMode] = useState<"drumi" | "ancestors">(
    typeof window !== "undefined" && window.location.hash.includes("ancestors")
      ? "ancestors"
      : "drumi"
  );

  const startInside = typeof window !== "undefined" && window.location.hash === "#room";
  const [phase, setPhase] = useState<DoorPhase>(startInside ? "done" : "idle");
  const [leaving, setLeaving] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);
  const [docsOpen, setDocsOpen] = useState(
    typeof window !== "undefined" && window.location.hash === "#handoff",
  );
  const sound = useAmbientSound();
  const timers = useRef<number[]>([]);

  const entering = useRef(false);
  const enter = useCallback(() => {
    if (phase !== "idle" || entering.current) return;
    entering.current = true;
    setLeaving(true);
    sound.doorSwell();
    timers.current.push(window.setTimeout(() => setPhase("opening"), TEXT_FADE_MS));
    timers.current.push(window.setTimeout(() => setPhase("entering"), TEXT_FADE_MS + DOOR_OPEN_MS));
  }, [phase, sound]);

  const arrived = useCallback(() => setPhase("done"), []);

  const returnToThreshold = useCallback(() => {
    window.scrollTo({ top: 0 });
    entering.current = false;
    setLeaving(false);
    setPhase("idle");
  }, []);

  useEffect(() => () => timers.current.forEach(clearTimeout), []);

  useEffect(() => {
    if (viewMode === "drumi") {
      document.body.style.overflow = "auto";
      return;
    }
    document.body.style.overflow = phase === "done" ? "" : "hidden";
    return () => {
      document.body.style.overflow = "";
    };
  }, [phase, viewMode]);

  // Keyboard shortcut: Enter key on the threshold
  useEffect(() => {
    if (viewMode !== "ancestors" || phase !== "idle") return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Enter" && !menuOpen && !docsOpen) enter();
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [phase, enter, menuOpen, docsOpen, viewMode]);

  // If in DRUMI mode, show the stunning DRUMI experience
  if (viewMode === "drumi") {
    return (
      <DrumiApp
        onSwitchToAncestors={() => {
          window.location.hash = "ancestors";
          setViewMode("ancestors");
        }}
      />
    );
  }

  const inRoom = phase === "entering" || phase === "done";

  return (
    <>
      {/* Switch back to DRUMI floating button */}
      <button
        type="button"
        onClick={() => {
          window.location.hash = "";
          setViewMode("drumi");
        }}
        className="fixed left-4 top-4 z-[90] px-3.5 py-1.5 rounded-full border border-gold/40 bg-ink/80 text-gold-light text-xs uppercase tracking-[0.2em] font-cinzel backdrop-blur hover:bg-gold/20 transition-all"
      >
        ← View DRUMI Sanctuary
      </button>

      {inRoom && (
        <Room
          arrived={phase === "done"}
          soundOn={sound.on}
          onToggleSound={sound.toggle}
          onOpenMenu={() => setMenuOpen(true)}
          onReturnToThreshold={returnToThreshold}
        />
      )}

      {phase !== "done" && <DoorStage phase={phase} onArrived={arrived} />}
      {phase !== "done" && (
        <Threshold
          leaving={leaving}
          soundOn={sound.on}
          onEnter={enter}
          onToggleSound={sound.toggle}
          onOpenMenu={() => setMenuOpen(true)}
        />
      )}

      <MenuOverlay open={menuOpen} onClose={() => setMenuOpen(false)} onEnter={phase === "idle" ? enter : undefined} />

      {/* Developer kit toggle (discreet) */}
      <button
        type="button"
        onClick={() => setDocsOpen(true)}
        className="fixed right-0 top-[62%] z-[80] border border-r-0 border-gold/30 bg-ink/70 px-1.5 py-3 font-mono text-[10px] tracking-[0.15em] text-gold/60 opacity-50 backdrop-blur transition [writing-mode:vertical-rl] hover:border-gold/60 hover:text-gold-light hover:opacity-100"
        title="Open the Elementor handoff kit (HTML / CSS / JS / guide)"
      >
        {"</>"} DEV KIT
      </button>
      {docsOpen && <Handoff onClose={() => setDocsOpen(false)} />}
    </>
  );
}
