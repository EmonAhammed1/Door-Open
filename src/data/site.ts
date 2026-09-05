/**
 * Shared content model for The Ancestors' Room demo.
 * Everything a client might want to change lives here.
 */

export const BRAND = {
  name: "DRUMI",
  nameLines: ["DRUMI"],
  tagline: "A sanctuary for your dreams.",
  lintel: "IBÁ AṢẸ EGÚN",
};

export const THRESHOLD = {
  headline: ["DRUMI"],
  body: [
    "A sanctuary for your dreams.",
    "A journey back to yourself.",
  ],
  cta: "JOIN THE JOURNEY",
  subCta: "STEP INTO YOUR INNER WORLD",
  bottomLeft: ["Slow Down.", "Listen Within."],
  bottomRight: ["Trust The Message.", "Return To You."],
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
    id: "headphones",
    label: "Audio & Acoustics",
    href: "#audio",
    x: 0.32,
    y: 0.58,
    kicker: "Hi-Fi Studio · Pure Sound",
    blurb:
      "Crafted acoustic perfection. Precision-engineered wireless studio headphones tuned for immersive, resonant clarity in any quiet sanctuary.",
    cta: "Explore Sound",
  },
  {
    id: "smartphone",
    label: "Mobile Sanctuary",
    href: "#devices",
    x: 0.39,
    y: 0.59,
    kicker: "Flagship Technology",
    blurb:
      "Sleek titanium and pro camera systems designed to capture rituals, memories, and connections wherever your journey leads.",
    cta: "View Devices",
  },
  {
    id: "workstation",
    label: "Creative Workstation",
    href: "#workspace",
    x: 0.5,
    y: 0.62,
    kicker: "Focus · Flow · Creation",
    blurb:
      "The centerpiece of modern mindfulness. Ultra-slim display and silent silicon architecture built for deep focus and thoughtful creations.",
    cta: "Discover Workspace",
  },
  {
    id: "smartwatch",
    label: "Time & Rhythm",
    href: "#watch",
    x: 0.58,
    y: 0.6,
    flip: false,
    kicker: "Mindful Pacing",
    blurb:
      "Tracking heart rhythms, mindful breaths, and daily cadences to keep you grounded through every season of your life.",
    cta: "Explore Rhythm",
  },
  {
    id: "speaker",
    label: "Ambient Soundscape",
    href: "#ambient",
    x: 0.65,
    y: 0.61,
    flip: true,
    kicker: "Room Audio · 360° Sound",
    blurb:
      "Fill the chamber with warm acoustic frequencies, synthesized ambient tones, and meditative soundscapes.",
    cta: "Listen Now",
  },
  {
    id: "projector",
    label: "Visual Theatre",
    href: "#cinema",
    x: 0.73,
    y: 0.61,
    flip: true,
    kicker: "Cinema · Light · Story",
    blurb:
      "Projecting memories, ceremonies, and visual journals with laser clarity onto linen walls and natural textures.",
    cta: "View Collection",
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
export const DOOR_RECT = { left: 0.350, right: 0.650, top: 0.164, bottom: 0.869 };
/** Where the "IBÁ AṢẸ EGÚN" lintel plank sits on each image (fractions). */
export const LINTEL_THRESHOLD = { x: 0.5, y: 0.090, size: 0.0165 };
export const LINTEL_ROOM = { x: 0.5, y: 0.1, size: 0.011 };
