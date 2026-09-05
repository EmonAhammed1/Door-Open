# E. Mobile Responsiveness Notes

The whole experience is built mobile-first around one rule: **the artwork is always `cover`-fitted and centred, and every pinned element is computed from that same geometry**, so nothing floats off its object when the crop changes.

## Breakpoints used

| Range | Name | What changes |
|---|---|---|
| ≥ 1280 px | Desktop XL | Everything visible: parchment on left wall (intro), hanging banner on right wall (home), full nav, six hotspots. |
| 1024 – 1279 px | Desktop | Banner hidden (the open door occupies the right edge at this ratio). Nav still inline. |
| 768 – 1023 px | Tablet | Text nav collapses into the burger / full-screen menu. Hotspots **still shown** (landscape tablets have room). Parchment hidden. |
| < 768 px | Mobile | Hotspots hidden → replaced by the **chip strip**. Quote + scroll cue hidden. Corner copy shrinks. “ENTER” text link hidden on the threshold (SOUND + MENU remain; the big button does the entering). |

## Hero artwork behaviour

- **Threshold (doors):** landscape artwork, doors in the centre 26 % of the width. On a 390 × 844 phone the cover crop shows almost exactly the two door leaves edge-to-edge — the composition survives naturally. The 3D panels are re-measured on `resize`/`orientationchange`, so rotating the phone mid-animation is safe.
- **Room:** on phones the crop centres on the altar and the middle of the table; the objects that carry the left/right hotspots (books, jug) fall outside the frame. That is *why* hotspots are replaced rather than squeezed.
- `height: 100svh` (with `100vh` fallback) keeps the hero from jumping when the iOS address bar collapses. `min-height: 620px` protects the layout on short landscape phones.

## How the table hotspots adapt

1. **≥ 768 px** — `+` markers positioned in pixels from image fractions (`data-x/data-y`). Labels sit right of the dot; markers near the right edge use `tar-hotspot--flip` so labels never overflow. Hit area is 42 px (≥ 44 px incl. label) — fine for touch on tablets.
2. **< 768 px** — `.tar-hotspots` is `display:none`. The JS builds one `.tar-chip` per hotspot (same label, same modal, same link) in a horizontally scrolling, snap-aligned strip pinned 120 px above the bottom of the hero. The first three chips are visible without scrolling; the partial fourth chip signals scrollability.
3. **Modal** — becomes 92 vw wide with reduced padding; the prev/next footer stays so users can swipe through all six “doors” without going back to the table.
4. **Secondary path** — the “Six Doors” cards section directly under the hero repeats all six destinations as full-width stacked cards, so even a visitor who ignores the chips gets every link with real thumb-sized targets.

## Typography scaling

All display sizes use `clamp()`:
- Threshold H1 `clamp(1.55rem, 3.3vw, 2.7rem)`, body `clamp(1.1rem, 1.55vw, 1.5rem)`.
- Home H1 `clamp(1.9rem, 4.4vw, 3.7rem)`.
- Letter-spaced small caps never go below 0.52 rem (≈ 8.3 px) and only for decorative labels; anything a user must read stays ≥ 0.6 rem with generous line-height.

## Touch & motion

- Hover states have tap equivalents (`:active` on chips, `.is-active` on the open hotspot).
- Door transition on mobile uses the same code; it is GPU-composited (`transform`/`opacity`/`clip-path` only) and tested smooth on mid-range Android. `prefers-reduced-motion` shortens it to ~1.2 s and removes flicker/embers/sway.
- Background embers are `pointer-events:none` and limited to ≤ 18 nodes.
- Sound starts only after a tap; iOS requires the AudioContext to be created inside the gesture — the toggle does exactly that.

## Things to check on real devices

- iPhone Safari: `backdrop-filter` on the button and chips is supported (14+); on older devices they simply render solid dark — acceptable.
- Very tall phones (20:9): the door leaves reach ~92 % of the height; the centred copy still sits on the doors with the dark scrim behind it.
- Landscape phones (< 500 px tall): hero uses `min-height: 620px`, so the page scrolls — intended; the threshold overlay is `position: fixed` and still fills the viewport.
- If the client swaps in a **portrait** room image for mobile later, add it as a second `.tar-home__bg` shown via media query and update `data-img-w/h` per breakpoint (the geometry function is already per-element).
