import { useState, useCallback } from "react";
import { DrumiDoorStage } from "./DrumiDoorStage";
import { DrumiFounderSection } from "./DrumiFounderSection";
import { DrumiVisionAndCards } from "./DrumiVisionAndCards";
import { DrumiFooterBar } from "./DrumiFooterBar";
import { DrumiModals, type DrumiModalType } from "./DrumiModals";
import { useDrumiSound } from "../../hooks/useDrumiSound";
import mockupOriginalImg from "../../assets/drumi/mockup-original.png";

interface DrumiAppProps {
  onSwitchToAncestors?: () => void;
}

export function DrumiApp({ onSwitchToAncestors }: DrumiAppProps) {
  const { soundOn, toggleSound, playChime, playDoorOpen } = useDrumiSound();
  const [modalType, setModalType] = useState<DrumiModalType>(null);
  const [wisdomData, setWisdomData] = useState<{ title: string; quote: string } | null>(null);
  const [showOriginalComparison, setShowOriginalComparison] = useState(false);

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

  const scrollToFounder = () => {
    const el = document.getElementById("founder-section");
    if (el) {
      el.scrollIntoView({ behavior: "smooth" });
    }
  };

  return (
    <div className="min-h-screen w-full bg-[#ede6df] text-[#342921] selection:bg-[#c9a66b]/30 relative font-['Cormorant_Garamond',serif]">
      {/* Top Floating Controls */}
      <div className="fixed top-4 left-5 z-40 flex items-center gap-2">
        <button
          type="button"
          onClick={() => setShowOriginalComparison((prev) => !prev)}
          className="px-3 py-1.5 rounded-full border border-[#c4a9a6] bg-[#fbf7f4]/90 backdrop-blur-md text-[#7d6957] text-[10px] uppercase tracking-[0.2em] font-['Cinzel',serif] shadow-sm hover:bg-white transition-all flex items-center gap-1.5"
          title="Toggle 1:1 Reference Mockup Overlay"
        >
          <span>{showOriginalComparison ? "Hide Mockup" : "Compare with Mockup"}</span>
        </button>

        {onSwitchToAncestors && (
          <button
            type="button"
            onClick={onSwitchToAncestors}
            className="px-3 py-1.5 rounded-full border border-[#d8c9be] bg-[#fbf7f4]/80 backdrop-blur-md text-[#8f7b6b] text-[10px] uppercase tracking-[0.16em] font-['Cinzel',serif] shadow-sm hover:bg-white transition-all"
            title="Switch to Ancestors Room demo"
          >
            Switch to Ancestors Room
          </button>
        )}
      </div>

      {/* Side-by-side or Modal Comparison with Original Mockup */}
      {showOriginalComparison && (
        <div className="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm">
          <div className="relative max-w-[520px] max-h-[92vh] overflow-y-auto bg-white p-3 rounded shadow-2xl">
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

      {/* Main Page Layout matching mockup */}
      <main className="w-full max-w-[960px] mx-auto bg-[#ede6df] shadow-[0_0_50px_rgba(0,0,0,0.06)]">
        {/* Section 1: Hero with 3D Double Doors */}
        <DrumiDoorStage
          soundOn={soundOn}
          onToggleSound={toggleSound}
          onPlayDoorOpen={playDoorOpen}
          onPlayChime={playChime}
          onScrollToFounder={scrollToFounder}
        />

        {/* Section 2: Our Founder */}
        <DrumiFounderSection onOpenStory={handleOpenFounderStory} />

        {/* Section 3 & 4: Our Vision & Two Luxury Cards */}
        <DrumiVisionAndCards onOpenJournal={handleOpenJournal} onOpenBlog={handleOpenBlog} />

        {/* Section 5: Luxury Bottom Bar */}
        <DrumiFooterBar onItemClick={handleFooterItemClick} />
      </main>

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
