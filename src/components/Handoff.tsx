import { useEffect, useState, type ReactNode } from "react";
import ReactMarkdown from "react-markdown";
import remarkGfm from "remark-gfm";
import sharedCss from "../elementor-kit/shared.css?raw";
import introHtml from "../elementor-kit/intro.html?raw";
import introCss from "../elementor-kit/intro.css?raw";
import homeHtml from "../elementor-kit/home.html?raw";
import homeCss from "../elementor-kit/home.css?raw";
import kitJs from "../elementor-kit/ancestors-room.js?raw";
import guideMd from "../elementor-kit/ELEMENTOR-GUIDE.md?raw";
import mobileMd from "../elementor-kit/MOBILE-NOTES.md?raw";
import { CloseIcon, Ornament } from "./Brand";
import { cn } from "../utils/cn";

interface CodeFile {
  name: string;
  lang: string;
  content: string;
  note?: string;
}
interface Tab {
  id: string;
  letter: string;
  title: string;
  intro: string;
  files?: CodeFile[];
  markdown?: string;
}

const TABS: Tab[] = [
  {
    id: "intro",
    letter: "A",
    title: "Intro Page — HTML + CSS",
    intro:
      "The Threshold. One HTML widget (intro.html) + the shared tokens (shared.css) + the page styles including the 3D door transition (intro.css).",
    files: [
      { name: "intro.html", lang: "html", content: introHtml, note: "Paste into an Elementor HTML widget. Replace the two image URLs." },
      { name: "shared.css", lang: "css", content: sharedCss, note: "Site-wide: tokens, fonts, buttons, nav, parchment, menu, atmosphere." },
      { name: "intro.css", lang: "css", content: introCss, note: "Threshold layout + door panels + walk-through classes." },
    ],
  },
  {
    id: "home",
    letter: "B",
    title: "Homepage — HTML + CSS",
    intro:
      "Inside the Room. Hero with pinned hotspots, one shared modal, mobile chip strip, optional cards section and footer.",
    files: [
      { name: "home.html", lang: "html", content: homeHtml, note: "Blocks 1–2 are required; 3–5 optional / can be native Elementor sections." },
      { name: "home.css", lang: "css", content: homeCss, note: "Hero, hotspots, chips, modal, cards, footer + all breakpoints." },
    ],
  },
  {
    id: "js",
    letter: "C",
    title: "Shared JavaScript",
    intro:
      "One dependency-free file: cover geometry, the door-opening + camera walk-through, hotspots + modal, mobile chips, menu overlay, and the synthesized ambient sound toggle.",
    files: [{ name: "ancestors-room.js", lang: "js", content: kitJs, note: "Load once in the footer (Elementor Pro → Custom Code → </body>)." }],
  },
  {
    id: "guide",
    letter: "D",
    title: "Elementor Implementation Guide",
    intro: "Step-by-step: which widgets, how to structure containers, settings, links, performance and a QA checklist.",
    markdown: guideMd,
  },
  {
    id: "mobile",
    letter: "E",
    title: "Mobile Responsiveness Notes",
    intro: "Breakpoints, how the artwork crops, and exactly how the table hotspots adapt on small screens.",
    markdown: mobileMd,
  },
];

function CopyButton({ text }: { text: string }) {
  const [done, setDone] = useState(false);
  return (
    <button
      type="button"
      onClick={async () => {
        try {
          await navigator.clipboard.writeText(text);
          setDone(true);
          setTimeout(() => setDone(false), 1600);
        } catch {
          /* clipboard blocked */
        }
      }}
      className="display border border-gold/40 px-3 py-1.5 text-[0.58rem] uppercase tracking-[0.22em] text-gold transition hover:border-gold-light hover:text-gold-light"
    >
      {done ? "Copied ✓" : "Copy"}
    </button>
  );
}

function CodeBlock({ file }: { file: CodeFile }) {
  const lines = file.content.split("\n").length;
  return (
    <section className="mb-10 border border-gold/20 bg-[#07050380]">
      <header className="flex flex-wrap items-center justify-between gap-3 border-b border-gold/20 px-4 py-3">
        <div className="flex items-center gap-3">
          <span className="font-mono text-[12px] text-gold-light">{file.name}</span>
          <span className="display text-[0.55rem] uppercase tracking-[0.2em] text-gold/60">
            {file.lang} · {lines} lines
          </span>
        </div>
        <div className="flex items-center gap-3">
          {file.note && <span className="body hidden text-[0.95rem] text-cream/60 italic md:inline">{file.note}</span>}
          <CopyButton text={file.content} />
        </div>
      </header>
      <pre className="docs-pre max-h-[520px] overflow-auto px-4 py-4">{file.content}</pre>
    </section>
  );
}

function Shell({ children, onClose }: { children: ReactNode; onClose: () => void }) {
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => e.key === "Escape" && onClose();
    window.addEventListener("keydown", onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      window.removeEventListener("keydown", onKey);
      document.body.style.overflow = prev;
    };
  }, [onClose]);
  return (
    <div className="fixed inset-0 z-[100] flex flex-col bg-ink-2 text-cream" role="dialog" aria-modal="true" aria-label="Elementor handoff kit">
      <div className="grain" />
      {children}
    </div>
  );
}

/**
 * Developer handoff: the A–E deliverables, viewable + copyable inside the demo.
 */
export function Handoff({ onClose }: { onClose: () => void }) {
  const [active, setActive] = useState(TABS[0].id);
  const tab = TABS.find((t) => t.id === active) ?? TABS[0];

  return (
    <Shell onClose={onClose}>
      <header className="relative flex items-center justify-between border-b border-gold/20 px-5 py-4 sm:px-8">
        <div className="flex items-center gap-4">
          <Ornament className="text-gold" size={18} />
          <div>
            <p className="display text-[0.8rem] tracking-[0.2em] text-cream uppercase">The Ancestors’ Room — Elementor Handoff Kit</p>
            <p className="body text-[0.95rem] text-cream/60 italic">WordPress + Elementor · vanilla HTML / CSS / JS · namespaced <code className="font-mono text-gold-light not-italic">tar-</code></p>
          </div>
        </div>
        <button type="button" onClick={onClose} className="nav-link flex items-center gap-2" aria-label="Close handoff kit">
          Back to demo <CloseIcon size={18} />
        </button>
      </header>

      <div className="relative flex min-h-0 flex-1 flex-col md:flex-row">
        {/* Tabs */}
        <nav className="no-scrollbar flex shrink-0 gap-1 overflow-x-auto border-b border-gold/20 px-3 py-3 md:w-72 md:flex-col md:overflow-y-auto md:border-r md:border-b-0 md:px-4 md:py-6" aria-label="Deliverables">
          {TABS.map((t) => (
            <button
              key={t.id}
              type="button"
              onClick={() => setActive(t.id)}
              className={cn(
                "flex shrink-0 items-start gap-3 border px-3 py-2.5 text-left transition md:w-full md:px-4 md:py-3.5",
                t.id === active
                  ? "border-gold/60 bg-gold/10 text-cream"
                  : "border-transparent text-cream/60 hover:border-gold/30 hover:text-cream",
              )}
            >
              <span className="display mt-0.5 text-[0.9rem] text-gold">{t.letter}</span>
              <span>
                <span className="display block text-[0.68rem] tracking-[0.12em] uppercase">{t.title}</span>
                <span className="body mt-1 hidden text-[0.92rem] leading-snug text-cream/55 md:block">{t.intro}</span>
              </span>
            </button>
          ))}
          <div className="mt-auto hidden pt-6 md:block">
            <p className="display text-[0.55rem] tracking-[0.25em] text-gold/60 uppercase">Files in this kit</p>
            <ul className="mt-2 space-y-1 font-mono text-[11px] text-cream/50">
              {["shared.css", "intro.html", "intro.css", "home.html", "home.css", "ancestors-room.js", "ELEMENTOR-GUIDE.md", "MOBILE-NOTES.md"].map((f) => (
                <li key={f}>src/elementor-kit/{f}</li>
              ))}
            </ul>
          </div>
        </nav>

        {/* Content */}
        <div className="min-h-0 flex-1 overflow-y-auto px-5 py-6 sm:px-8 md:px-10">
          <div className="mx-auto max-w-5xl">
            <p className="display text-[0.6rem] tracking-[0.3em] text-gold uppercase">Section {tab.letter}</p>
            <h2 className="display mt-2 text-[1.5rem] text-cream sm:text-[1.8rem]">{tab.title}</h2>
            <p className="body mt-3 max-w-3xl text-[1.12rem] leading-relaxed text-cream-2">{tab.intro}</p>
            <div className="hairline my-7" />
            {tab.files?.map((f) => (
              <CodeBlock key={f.name} file={f} />
            ))}
            {tab.markdown && (
              <div className="docs-md pb-16">
                <ReactMarkdown remarkPlugins={[remarkGfm]}>{tab.markdown}</ReactMarkdown>
              </div>
            )}
          </div>
        </div>
      </div>
    </Shell>
  );
}
