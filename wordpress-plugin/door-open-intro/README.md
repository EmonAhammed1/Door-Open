# Door Open Intro

A full-screen DRUMI threshold for WordPress. Visitors arrive at the closed marble door. When they press the
button, the door's engraving lights up from the centre, the doors swing open and the camera walks through
into your page, which has already loaded behind the door the whole time.

## Install

1. WP Admin → Plugins → Add New → Upload Plugin → choose `door-open-intro.zip` → Install → Activate.
   Upgrading from 3.x: WordPress offers **Replace current with uploaded**; choose it.
2. Settings → Door Open Intro → **Page behind the door**: choose the page visitors walk into.
3. Click **Preview on your site ↗** to see it.

## Settings

| Setting | What it does |
| --- | --- |
| Page behind the door | Your homepage. This is the same setting as Settings → Reading → "Your homepage displays", so changing it in either place changes both. |
| Door intro | Turns the door on or off. |
| Where | Homepage only, or every page (the door then opens onto whichever page the visitor arrived at). |
| Repeat visits | "Same tab": once per tab (a new tab shows it again). "Across all tabs": once until the visitor closes their browser, in every tab (a session cookie, `doi_seen`). |
| Phones | Show the door on phones too. |
| Skip | A small "Skip intro" link. Esc always skips. |
| Text on the door | The name, tagline, button and the line under the button. |

`?doi_preview=1` on any page shows the door even if it's switched off or was already seen this visit.

## How it behaves on your site

- The page underneath loads normally; the door covers it from the first paint, so nothing flashes.
- While the door is up, the page can't be scrolled or tabbed into; afterwards everything is handed back exactly
  as the theme had it, with no shift when the scrollbar returns.
- It never appears in wp-admin, the Customizer, post previews, or page-builder editors (Elementor, Divi,
  Beaver Builder, Oxygen, WPBakery, Brizy, Bricks).
- With "reduce motion" turned on in the visitor's system settings, the door simply fades away.

### Hooks for your theme

The intro fires three browser events on `window`:

- `doi:open` — the visitor pressed the button.
- `doi:revealing` — the light is clearing and the page is becoming visible (good for fading in hero text).
- `doi:revealed` — the door is gone and the page is fully interactive.

```js
window.addEventListener('doi:revealing', () => document.documentElement.classList.add('hero-in'));
```

## Files

```
door-open-intro/
├── door-open-intro.php     plugin bootstrap and front-end output
├── includes/admin.php      Settings → Door Open Intro
├── assets/intro.js         the door (3D scene, timeline, hand-off to the page)
├── assets/intro.css
├── assets/door/            the door photographs, unlit / lit, for wide screens (desktop-*) and portrait screens (phone-*)
└── uninstall.php           removes the plugin's settings when deleted
```

The door geometry in `assets/intro.js` (arch, hinge lines, emblem) is measured from the two photographs in
`assets/door/`; replacing them with different artwork means re-measuring it.
