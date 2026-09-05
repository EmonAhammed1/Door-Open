import { useState } from "react";
import innerSanctuaryImg from "../../assets/drumi/inner-sanctuary.jpg";
import { DrumiFounderSection } from "./DrumiFounderSection";
import { DrumiVisionAndCards } from "./DrumiVisionAndCards";
import { DrumiFooterBar } from "./DrumiFooterBar";

interface DrumiInnerRoomProps {
  soundOn: boolean;
  onToggleSound: () => void;
  onReturnToThreshold: () => void;
  onOpenFounderStory: () => void;
  onOpenJournal: () => void;
  onOpenBlog: () => void;
  onFooterItemClick: (label: string, quote: string) => void;
  onPlayChime: (freq?: number) => void;
}

interface Hotspot {
  id: string;
  x: number; // percentage
  y: number; // percentage
  label: string;
  kicker: string;
  blurb: string;
}

const HOTSPOTS: Hotspot[] = [
  {
    id: "lake-reflection",
    x: 50,
    y: 42,
    label: "Still Waters",
    kicker: "Lake of Reflection",
    blurb: "A mirror of the unconscious. When the surface of the mind calms, the deepest wisdom is reflected back without distortion.",
  },
  {
    id: "terrace-altar",
    x: 18,
    y: 72,
    label: "Morning Altar",
    kicker: "Grounding & Awakening",
    blurb: "A sacred space with olive trees and glowing lanterns to sit at daybreak, capturing the memories of dreams.",
  },
  {
    id: "meditation-corner",
    x: 82,
    y: 74,
    label: "Sanctuary Alcove",
    kicker: "The Evening Threshold",
    blurb: "Linen cushions and stone balustrades. Where the transition from waking to dreaming begins each evening.",
  },
  {
    id: "tea-table",
    x: 68,
    y: 84,
    label: "Quiet Reflection",
    kicker: "Herbs & Journaling",
    blurb: "A warm cup of herbal tea and open journal, resting in the golden sunset warmth of the terrace.",
  },
];

export function DrumiInnerRoom({
  soundOn,
  onToggleSound,
  onReturnToThreshold,
  onOpenFounderStory,
  onOpenJournal,
  onOpenBlog,
  onFooterItemClick,
  onPlayChime,
}: DrumiInnerRoomProps) {
  const [activeHotspot, setActiveHotspot] = useState<Hotspot | null>(null);

  const handleHotspotClick = (h: Hotspot) => {
    onPlayChime(639);
    setActiveHotspot((prev) => (prev?.id === h.id ? null : h));
  };

  const scrollToFounder = () => {
    const el = document.getElementById("founder-section");
    if (el) {
      el.scrollIntoView({ behavior: "smooth" });
    }
  };

  return (
    <div className="w-full min-h-screen bg-[#ede6df] animate-fadeIn">
      {/* 1. Full-Viewport Sanctuary Terrace Hero (inner-sanctuary.jpg) */}
      <section className="relative w-full h-screen min-h-[640px] overflow-hidden select-none">
        {/* Luxury Stone Terrace with Pillars, Lanterns & Lake Background */}
        <div
          className="absolute inset-0 bg-cover bg-center transition-transform duration-1000 scale-100"
          style={{
            backgroundImage: `url(${innerSanctuaryImg})`,
            backgroundPosition: "center center",
          }}
        >
          {/* Subtle warm golden ambient lighting */}
          <div className="absolute inset-0 bg-gradient-to-b from-black/25 via-transparent to-[#2b1f18]/65" />
        </div>

        {/* Top Navigation Bar with generous spacing (No overlapping buttons) */}
        <header className="absolute top-0 left-0 right-0 z-30 px-6 sm:px-12 pt-6 sm:pt-8 flex items-center justify-between">
          {/* Return to Entrance Button (Clean luxury pill button) */}
          <button
            type="button"
            onClick={onReturnToThreshold}
            className="flex items-center gap-2.5 px-5 py-2.5 rounded-full border border-white/45 bg-black/35 hover:bg-black/55 text-white text-[11px] uppercase tracking-[0.22em] font-['Cinzel',serif] backdrop-blur-md transition-all shadow-lg group cursor-pointer"
          >
            <span className="transition-transform group-hover:-translate-x-1 font-sans">←</span>
            <span>Return to Entrance</span>
          </button>

          {/* Centered DRUMI Brand */}
          <div className="flex flex-col items-center text-center text-white drop-shadow-md">
            <h1 className="font-['Cinzel',serif] text-xl sm:text-2xl tracking-[0.4em] uppercase font-normal pl-[0.4em]">
              D R U M I
            </h1>
            <span className="font-['Cormorant_Garamond',serif] italic text-xs sm:text-sm text-white/80">
              The Inner Sanctuary
            </span>
          </div>

          {/* Sound Toggle */}
          <button
            type="button"
            onClick={onToggleSound}
            className={`flex items-center gap-2 px-4 py-2 rounded-full border backdrop-blur-md text-[11px] uppercase tracking-[0.2em] font-['Cinzel',serif] transition-all shadow-lg cursor-pointer ${
              soundOn
                ? "bg-[#9a7470]/90 border-[#c4a9a6] text-white"
                : "bg-black/35 border-white/45 text-white hover:bg-black/55"
            }`}
          >
            <span className="relative flex h-2 w-2">
              {soundOn && (
                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
              )}
              <span
                className={`relative inline-flex rounded-full h-2 w-2 ${
                  soundOn ? "bg-white" : "bg-white/60"
                }`}
              ></span>
            </span>
            <span>{soundOn ? "SOUND ON" : "SOUND OFF"}</span>
          </button>
        </header>

        {/* Interactive Sanctuary Hotspots */}
        {HOTSPOTS.map((h) => {
          const isActive = activeHotspot?.id === h.id;
          return (
            <div
              key={h.id}
              className="absolute z-25 -translate-x-1/2 -translate-y-1/2"
              style={{ left: `${h.x}%`, top: `${h.y}%` }}
            >
              <button
                type="button"
                onClick={() => handleHotspotClick(h)}
                className={`group relative flex items-center justify-center w-9 h-9 rounded-full border backdrop-blur-md transition-all duration-300 cursor-pointer ${
                  isActive
                    ? "bg-white text-[#7d5653] border-white scale-110 shadow-[0_0_24px_rgba(255,255,255,0.9)]"
                    : "bg-black/45 text-white border-white/70 hover:bg-white/90 hover:text-[#7d5653] hover:scale-105"
                }`}
                title={h.label}
              >
                <span className="text-sm font-serif">✦</span>
                <span className="absolute inset-0 rounded-full border border-white/50 animate-ping pointer-events-none opacity-40" />
              </button>

              {/* Tooltip Card */}
              {isActive && (
                <div className="absolute bottom-12 left-1/2 -translate-x-1/2 w-64 p-4 rounded bg-[#fdfaf7]/95 border border-[#dfd0c4] text-[#342921] shadow-2xl backdrop-blur-md z-40 animate-fadeIn text-left">
                  <span className="block font-['Cinzel',serif] text-[10px] uppercase tracking-[0.2em] text-[#9a7470] font-semibold mb-1">
                    {h.kicker}
                  </span>
                  <h4 className="font-['Cinzel',serif] text-sm text-[#342921] font-medium mb-1.5">
                    {h.label}
                  </h4>
                  <p className="font-['Cormorant_Garamond',serif] text-xs text-[#635548] leading-relaxed">
                    {h.blurb}
                  </p>
                </div>
              )}
            </div>
          );
        })}

        {/* Center Bottom Welcome & Scroll Cue */}
        <div className="absolute bottom-8 left-0 right-0 z-20 flex flex-col items-center text-center text-white px-4">
          <span className="font-['Cinzel',serif] text-xs uppercase tracking-[0.3em] text-[#eedcd7] mb-2 font-medium">
            A Journey Back to Yourself
          </span>
          <h2 className="font-['Cinzel',serif] text-2xl sm:text-4xl font-light tracking-widest text-white drop-shadow-md mb-5">
            WELCOME TO DRUMI
          </h2>

          <button
            type="button"
            onClick={scrollToFounder}
            className="flex flex-col items-center gap-1.5 text-white/80 hover:text-white transition-colors cursor-pointer group"
          >
            <span className="font-['Cinzel',serif] text-[10px] tracking-[0.25em] uppercase font-medium">
              Discover Founder Story & Practices
            </span>
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="1.8"
              className="w-4 h-4 animate-bounce group-hover:translate-y-1 transition-transform"
            >
              <path d="M7 13l5 5 5-5M7 7l5 5 5-5" />
            </svg>
          </button>
        </div>
      </section>

      {/* 2. OUR FOUNDER Section (Full-Width) */}
      <DrumiFounderSection onOpenStory={onOpenFounderStory} />

      {/* 3. OUR VISION & CARDS Section (Full-Width) */}
      <DrumiVisionAndCards onOpenJournal={onOpenJournal} onOpenBlog={onOpenBlog} />

      {/* 4. SACRED FOOTER BAR (Full-Width) */}
      <DrumiFooterBar onItemClick={onFooterItemClick} />
    </div>
  );
}
