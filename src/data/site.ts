/**
 * Shared content model for The Ancestors' Room demo.
 * Everything a client might want to change lives here.
 */

export const BRAND = {
  name: "The Ancestors’ Room",
  nameLines: ["THE", "ANCESTORS’", "ROOM"],
  tagline: "Rooted. Grounded. Guided.",
  lintel: "IBÁ AṢẸ EGÚN",
};

export const THRESHOLD = {
  headline: ["There is a Room", "Beyond This Door."],
  body: [
    "A place where memory is kept.",
    "Where tradition is carried forward.",
    "Where the living remember",
    "the ones who came before.",
  ],
  cta: "Enter the Room",
  bottomLeft: ["Same Roots.", "Different Generations.", "Always Home."],
  bottomRight: ["People. Places. Practices.", "You Belong Here."],
};

export const PARCHMENT_WORDS = ["Honor", "Learn", "Heal", "Practice", "Remember", "Return"];

export const HOME = {
  eyebrow: "Welcome to",
  title: "The Ancestors’ Room",
  subtitle: ["A space for remembrance,", "ritual, and return."],
  bottomLeft: ["People.", "Places.", "Practices.", "You belong here."],
  quote: ["“A place to remember", "who you are.”"],
};

export const NAV = [
  { label: "Home", href: "#home", active: true },
  { label: "The Rooms", href: "#the-rooms" },
  { label: "Shop", href: "#shop" },
  { label: "Services", href: "#services" },
  { label: "Journal", href: "#journal" },
  { label: "About", href: "#about" },
  { label: "Connect", href: "#connect" },
];

export interface Hotspot {
  id: string;
  label: string;
  href: string;
  /** Position as fraction of the room image (0–1), so it stays pinned to the object under any crop. */
  x: number;
  y: number;
  /** Put the label on the left side of the "+" (for hotspots near the right edge). */
  flip?: boolean;
  kicker: string;
  blurb: string;
  cta: string;
}

export const HOTSPOTS: Hotspot[] = [
  {
    id: "apothecary",
    label: "The Apothecary",
    href: "#apothecary",
    x: 0.27,
    y: 0.6,
    kicker: "Herbs · Oils · Remedies",
    blurb:
      "Prepared by hand, in the old way. Teas, tinctures, anointing oils and baths rooted in the traditions our elders carried across oceans and generations.",
    cta: "Enter the Apothecary",
  },
  {
    id: "shop",
    label: "Shop",
    href: "#shop",
    x: 0.17,
    y: 0.76,
    kicker: "Candles · Cloths · Altar Tools",
    blurb:
      "Everything needed to tend an ancestral space — hand-poured candles, white cloths, vessels, cowries, incense, and offerings chosen with care.",
    cta: "Browse the Shop",
  },
  {
    id: "the-rooms",
    label: "The Rooms",
    href: "#the-rooms",
    x: 0.5,
    y: 0.71,
    kicker: "Altar · Kitchen · Garden · Library",
    blurb:
      "Rooms within the room. Each holds a practice, a teaching, and a way of returning. Step through them at your own pace.",
    cta: "Walk the Rooms",
  },
  {
    id: "services",
    label: "Services",
    href: "#services",
    x: 0.72,
    y: 0.6,
    flip: false,
    kicker: "Readings · Guidance · Ceremony",
    blurb:
      "One-to-one readings, ancestral veneration guidance, altar consultations and ceremony support — in the room, or wherever you are.",
    cta: "View Services",
  },
  {
    id: "journal",
    label: "Journal",
    href: "#journal",
    x: 0.84,
    y: 0.7,
    flip: true,
    kicker: "Stories · Teachings · Reflections",
    blurb:
      "What the room remembers. Essays on lineage, ritual, grief, joy and return — written for those learning to listen to the ones who came before.",
    cta: "Read the Journal",
  },
  {
    id: "connect",
    label: "Connect",
    href: "#connect",
    x: 0.64,
    y: 0.8,
    flip: false,
    kicker: "Ask · Visit · Join the Circle",
    blurb:
      "Ask a question, book a visit, or join the circle for gatherings and new-moon letters. The door stays open for you.",
    cta: "Connect With Us",
  },
];

export const FOOTER = {
  legal: [
    { label: "Privacy Policy", href: "#privacy" },
    { label: "Terms of Service", href: "#terms" },
    { label: "Shipping & Returns", href: "#shipping" },
  ],
  followLabel: "Follow the Journey",
  socials: ["instagram", "facebook", "youtube", "tiktok", "pinterest"] as const,
};

/** Fractions of the threshold image occupied by the two door leaves (measured from the artwork). */
export const DOOR_RECT = { left: 0.372, right: 0.628, top: 0.155, bottom: 0.865 };
/** Where the "IBÁ AṢẸ EGÚN" lintel plank sits on each image (fractions). */
export const LINTEL_THRESHOLD = { x: 0.5, y: 0.118, size: 0.0165 };
export const LINTEL_ROOM = { x: 0.5, y: 0.1, size: 0.011 };
