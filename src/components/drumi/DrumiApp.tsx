import { useState, useCallback, useRef, useEffect } from "react";
import { DrumiThreshold, type DrumiPhase } from "./DrumiThreshold";
import { DrumiInnerRoom } from "./DrumiInnerRoom";
import { DrumiModals, type DrumiModalType } from "./DrumiModals";
import { useDrumiSound } from "../../hooks/useDrumiSound";
import mockupOriginalImg from "../../assets/drumi/mockup-original.png";

interface DrumiAppProps {
  onSwitchToAncestors?: () => void;
}



export function DrumiApp({ onSwitchToAncestors }: DrumiAppProps) {
  const [phase, setPhase] = useState<DrumiPhase>("idle");
  const { soundOn, toggleSound, playChime, playDoorOpen } = useDrumiSound();
  const [modalType, setModalType] = useState<DrumiModalType>(null);
  const [wisdomData, setWisdomData] = useState<{ title: string; quote: string } | null>(null);
  const [showOriginalComparison, setShowOriginalComparison] = useState(false);
  const enteringRef = useRef(false);
  const timers = useRef<number[]>([]);

  const handleEnter = useCallback(() => {
    if (phase !== "idle") return;
    enteringRef.current = true;
    playDoorOpen();
    setPhase("opening");
  }, [phase, playDoorOpen]);

  const handleCloseDoors = useCallback(() => {
    playChime(440);
    enteringRef.current = false;
    setPhase("idle");
  }, [playChime]);

  const handleArrived = useCallback(() => {
    setPhase("inside");
  }, []);

  const handleReturnToThreshold = useCallback(() => {
    window.scrollTo({ top: 0, behavior: "smooth" });
    enteringRef.current = false;
    setPhase("idle");
  }, []);

  useEffect(() => () => timers.current.forEach(clearTimeout), []);

  // Keyboard shortcut: Enter key opens the doors
  useEffect(() => {
    if (phase !== "idle") return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Enter") handleEnter();
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [phase, handleEnter]);

  const handleOpenFounderStory = useCallback(() => {
    playChime(528);
    setModalType("founder");
  }, [playChime]);

  const handleOpenJournal = useCallback(() => {
    playChime(639);
    setModalType("journal");
  }, [playChime]);

  const handleOpenBlog = useCallback(() => {
    playChime(741);
    setModalType("blog");
  }, [playChime]);

  const handleFooterItemClick = useCallback(
    (label: string, quote: string) => {
      playChime(852);
      setWisdomData({ title: label, quote });
      setModalType("wisdom");
    },
    [playChime]
  );

  return (
    <div className="w-full min-h-screen bg-[#ede6df] text-[#342921] relative font-['Cormorant_Garamond',serif] overflow-x-hidden">
      {/* Top Floating Controls - shown on threshold */}
      {phase !== "inside" && (
        <div className="fixed top-5 left-6 z-50 flex items-center gap-2.5">
          <button
            type="button"
            onClick={() => setShowOriginalComparison((prev) => !prev)}
            className="px-3.5 py-1.5 rounded-full border border-[#c4a9a6] bg-[#fbf7f4]/90 backdrop-blur-md text-[#7d6957] text-[10px] uppercase tracking-[0.2em] font-['Cinzel',serif] shadow-sm hover:bg-white transition-all flex items-center gap-1.5 cursor-pointer"
            title="Toggle 1:1 Reference Mockup"
          >
            <span>{showOriginalComparison ? "Hide Mockup" : "Compare with Mockup"}</span>
          </button>

          {onSwitchToAncestors && (
            <button
              type="button"
              onClick={onSwitchToAncestors}
              className="px-3.5 py-1.5 rounded-full border border-[#d8c9be] bg-[#fbf7f4]/80 backdrop-blur-md text-[#8f7b6b] text-[10px] uppercase tracking-[0.16em] font-['Cinzel',serif] shadow-sm hover:bg-white transition-all cursor-pointer"
            >
              Ancestors Room
            </button>
          )}
        </div>
      )}

      {/* Discrete Bottom-Left Controls when inside sanctuary */}
      {phase === "inside" && (
        <div className="fixed bottom-4 left-6 z-40 flex items-center gap-2 opacity-60 hover:opacity-100 transition-opacity">
          <button
            type="button"
            onClick={() => setShowOriginalComparison((prev) => !prev)}
            className="px-3 py-1 rounded-full border border-white/30 bg-black/40 text-white/80 text-[9px] uppercase tracking-[0.16em] font-['Cinzel',serif] backdrop-blur-md hover:bg-black/60 transition-all cursor-pointer"
          >
            Mockup Ref
          </button>
          {onSwitchToAncestors && (
            <button
              type="button"
              onClick={onSwitchToAncestors}
              className="px-3 py-1 rounded-full border border-white/30 bg-black/40 text-white/80 text-[9px] uppercase tracking-[0.16em] font-['Cinzel',serif] backdrop-blur-md hover:bg-black/60 transition-all cursor-pointer"
            >
              Ancestors
            </button>
          )}
        </div>
      )}

      {/* Comparison Modal */}
      {showOriginalComparison && (
        <div
          className="fixed inset-0 z-55 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm"
          onClick={() => setShowOriginalComparison(false)}
        >
          <div
            className="relative max-w-[500px] max-h-[92vh] overflow-y-auto bg-white p-3 rounded shadow-2xl"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex justify-between items-center pb-2 mb-2 border-b border-gray-200">
              <span className="font-['Cinzel',serif] text-xs uppercase tracking-widest text-gray-700">
                Original Client Mockup Reference
              </span>
              <button
                type="button"
                onClick={() => setShowOriginalComparison(false)}
                className="text-gray-500 hover:text-black text-sm px-2 py-1"
              >
                ✕ Close
              </button>
            </div>
            <img src={mockupOriginalImg} alt="Original DRUMI Mockup" className="w-full h-auto" />
          </div>
        </div>
      )}

      {/* Phase 1 & 2: Full-Width Threshold / 3D Door Open */}
      {phase !== "inside" && (
        <DrumiThreshold
          phase={phase}
          soundOn={soundOn}
          onEnter={handleEnter}
          onArrived={handleArrived}
          onCloseDoors={handleCloseDoors}
          onToggleSound={toggleSound}
        />
      )}

      {/* Phase 3: The Inner Sanctuary Page (Opened after entering) */}
      {phase === "inside" && (
        <DrumiInnerRoom
          soundOn={soundOn}
          onToggleSound={toggleSound}
          onReturnToThreshold={handleReturnToThreshold}
          onOpenFounderStory={handleOpenFounderStory}
          onOpenJournal={handleOpenJournal}
          onOpenBlog={handleOpenBlog}
          onFooterItemClick={handleFooterItemClick}
          onPlayChime={playChime}
        />
      )}

      {/* Modals & Popups */}
      <DrumiModals
        modalType={modalType}
        wisdomData={wisdomData}
        onClose={() => {
          setModalType(null);
          setWisdomData(null);
        }}
      />
    </div>
  );
}
