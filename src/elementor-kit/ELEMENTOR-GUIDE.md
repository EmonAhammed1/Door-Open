# D. Elementor Implementation Guide — The Ancestors’ Room

This kit is **pure HTML / CSS / JS** namespaced with `tar-`, so it drops into any Elementor site (free or Pro) without fighting the theme. Two ways to structure it — pick one:

| Option | What it is | Best for |
|---|---|---|
| **A · Single page (recommended)** | The Threshold is a full-screen overlay *on the homepage itself*. The doors open and the same page is revealed underneath — no reload, seamless. | The client demo. Zero flash, hotspots pinned, one URL. |
| **B · Two pages** | `/` = Intro page, `/home/` = Homepage. “Enter” plays the door animation then navigates. The homepage runs a short “settle” animation on load. | Sites where the intro must be its own URL / skippable by menu. |

The JS detects which one you built: if `#tar-home` exists on the same page as `#tar-threshold` it reveals it; otherwise it navigates to `data-home-url`.

---

## 0. Before you start (10 minutes)

1. **Upload the two artworks** to Media Library: `threshold-doors.jpg` (closed doors) and `room-interior.jpg` (inside the room). Recommended export: 2400 px wide, JPG quality 80, ≤ 450 KB each. Note their pixel sizes.
2. **Fonts.** `shared.css` `@import`s Cinzel + Cormorant Garamond from Google Fonts. If the client prefers self-hosting (GDPR), upload both to *Elementor → Custom Fonts* and delete the `@import` line — the variables `--tar-display` / `--tar-body` will pick up whatever you name them.
3. **Global colours** (optional, for native widgets you add later): *Site Settings → Global Colors*: Ink `#0a0806`, Ink 2 `#120e0a`, Gold `#c9a66b`, Gold Light `#e8d5a3`, Cream `#f1e6cf`, Ember `#e08a3c`.
4. **Global fonts:** Primary/Secondary = Cinzel · Text/Accent = Cormorant Garamond.

---

## 1. Load the shared CSS + JS (site-wide, once)

**CSS** — *Elementor → Site Settings → Custom CSS* → paste `shared.css`, then `intro.css`, then `home.css` (order matters; total ≈ 24 KB).  
Alternative: child theme `functions.php`:

```php
add_action('wp_enqueue_scripts', function () {
  $u = get_stylesheet_directory_uri();
  wp_enqueue_style('tar-shared', "$u/css/shared.css", [], '1.0');
  wp_enqueue_style('tar-intro',  "$u/css/intro.css",  ['tar-shared'], '1.0');
  wp_enqueue_style('tar-home',   "$u/css/home.css",   ['tar-shared'], '1.0');
  wp_enqueue_script('tar', "$u/js/ancestors-room.js", [], '1.0', true);
});
```

**JS** — *Elementor Pro → Custom Code → Add New* → Location **`</body>` – End**, Priority 10, paste `ancestors-room.js` inside `<script>…</script>`, publish → Display Conditions: **Entire Site**.  
No Pro? Use the snippet above, or the free “WPCode” plugin (Footer location).

**Body class** — Page Settings → Advanced → *CSS Classes*: `tar-body` (sets the ink background so nothing white ever flashes).

---

## 2. Build the Homepage (Option A: doors included)

1. **Pages → Add New → “Home”** → Edit with Elementor.
2. Page Settings (⚙️): **Page Layout = Elementor Canvas** (blank – no theme header/footer). Set as *Front Page* in Settings → Reading later.
3. Add **one Container**: Width = Full Width, Content Width = Full Width, Min Height = 0, Padding = 0 on all sides, Overflow = Visible.
4. Drop an **HTML widget** into it. Paste, in this order, from `home.html`: block **1 HERO**, block **2 MODAL**. Replace `https://YOURSITE.com/wp-content/uploads/room-interior.jpg` with the real File URL. Set `data-img-w` / `data-img-h` to the real pixel size.
5. Drop a **second HTML widget** *before* it (drag it to the top of the same container) and paste **the entire `intro.html`** (threshold + `#tar-menu`). Replace both image URLs. Because `.tar-threshold` is `position: fixed; z-index: 800`, it covers the page until the visitor enters — its place in the DOM doesn’t matter visually, but keeping it first means it paints first.
   - ⚠️ Make sure no *ancestor* of the HTML widget has a CSS `transform` or `filter` (Elementor motion effects / sticky), or `position: fixed` will be trapped inside it. Keep the container plain.
6. Below the hero, either paste blocks **4 (Six Doors cards)** and **5 (Footer)** into more HTML widgets, **or** rebuild them with native widgets (see §5) — the CSS classes work on native elements too.
7. **Preview.** You should see the doors, the seam flicker, hover the button, press *Enter the Room*. Doors swing, you walk through, hotspots fade in.

### Two-page variant (Option B)
- Intro page: Canvas layout, one Container, one HTML widget with `intro.html`. Set `data-home-url="/home/"`.
- Homepage: as above but **without** step 5. On direct load, `body.tar-arrived` is added by the JS and the `.tar-rise / .tar-fade` elements animate in; `.tar-home__bg` settles from scale 1.06 → 1 so the arrival still feels like stepping in.
- Add the `#tar-menu` block from `intro.html` to the homepage too (once).

---

## 3. Wire the links

Every destination is a plain `href`. Search-and-replace in the HTML widgets:

| Placeholder | Replace with |
|---|---|
| `/the-rooms/`, `/shop/`, `/services/`, `/journal/`, `/about/`, `/connect/`, `/apothecary/` | your real page slugs |
| `/?s=` `/my-account/` `/cart/` | WooCommerce search / account / cart URLs |
| `data-href` on each `.tar-hotspot` | the page that hotspot opens (the modal CTA uses it) |

Prefer a **native Elementor Nav Menu** in the top bar? Delete `<nav class="tar-home__nav">…</nav>` from the HTML, add a Nav Menu widget in a sibling container positioned absolutely, and give its menu items the CSS class `tar-nav-link` (Item Typography: Cinzel, 10–11 px, uppercase, letter-spacing 0.22 em, colour Cream 82 %, hover Gold Light).

---

## 4. Editing content (what the client can change herself)

| What | Where |
|---|---|
| Headlines, body copy, button labels | Plain text inside the HTML widget — no code knowledge needed |
| Hotspot position | `data-x` / `data-y` = % across / down the *image*. 50/50 is dead centre of the artwork |
| Hotspot copy | `data-title`, `data-kicker`, `data-text`, `data-cta`, `data-href` |
| Parchment / banner words | The six `<span class="tar-parchment__word">` entries |
| Carved plank text | `.tar-lintel` content (`IBÁ AṢẸ EGÚN`) and `data-y` to nudge it up/down |
| Door rectangle (only if you swap the door artwork) | `data-door-left/right/top/bottom` = % of the image where the two leaves sit. Open the image in Preview/Photoshop, read the pixel coordinates of the leaf edges, divide by width/height |
| Always show the doors vs. once per session | `data-skip-key="tar_entered"` (once per session) or `""` (always) |

---

## 5. Rebuilding below-the-fold sections natively (optional)

The hero and threshold should stay as HTML widgets (they need the geometry JS). Everything else can be native so the client edits it visually:

- **Six Doors** → Container (background Ink, padding 120/40) → Heading widget (`H2`, Cinzel, 40 px, Cream) → Icon Box × 6 in a 3-column grid. Give each Icon Box the CSS class `tar-card` — the hover glow, borders and typography come from `home.css`.
- **About + Connect** → 2-column Container; left Text Editor, right Form widget (Elementor Pro) with class `tar-form`. Use the `.tar-btn` class on the submit button for the gold hover fill.
- **Footer** → Theme Builder → Footer → 3 columns; assign classes `tar-footer`, `tar-footer__legal`, `tar-footer__social` to the containers, or just paste block 5.

---

## 6. Recommended settings & performance

- **Images:** WebP or JPG ≤ 450 KB; disable Elementor lazy-load for the two hero images (they must be present the moment the doors open). The JS preloads the room image while the visitor reads the door copy.
- **Caching:** Any cache/minify plugin is fine. If you minify JS, keep `ancestors-room.js` **deferred to the footer**, not `async` in the head.
- **Sound:** Fully synthesized with the Web Audio API — nothing to host, no licensing. It only starts after a click (browser policy). If the client later wants a real recording, replace `Sound.start()` with an `<audio loop>` element — the toggle wiring (`[data-tar-sound]`, `body.tar-sound-on`) stays the same.
- **Accessibility:** Everything is keyboard reachable (Enter opens the doors, Esc closes menu/modal, ← → cycles hotspots). `prefers-reduced-motion` shortens the door animation to a fade. Contrast of cream on ink passes AA.
- **Elementor Experiments:** Flexbox Containers ON (default in 3.16+). Disable “Improved CSS Loading” only if you notice a flash of unstyled hotspots.
- **SEO:** The threshold overlay does not hide content from crawlers — the homepage markup is in the DOM from first paint.

---

## 7. QA checklist before the client call

- [ ] Doors align with the artwork at 1920×1080, 1440×900, 1366×768 and 768×1024 (resize the browser; the leaves must sit inside the painted frame — adjust `data-door-*` if not).
- [ ] “Enter the Room” → doors swing inward → golden bloom → hotspots fade in, no white flash.
- [ ] Each hotspot hover glows, click opens the modal, CTA goes to the right page.
- [ ] Mobile (390 px): hotspots hidden, chip strip visible and scrollable, tap opens modal.
- [ ] Sound toggle animates the bars, plays crackle, remembers state across pages in the session.
- [ ] Menu opens from the threshold and from the mobile burger; Esc closes it.
- [ ] Reload → doors are skipped (session) if `data-skip-key` is set; open in a private window → doors show again.
