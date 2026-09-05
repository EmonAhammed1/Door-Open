# Door Open Intro — WordPress Plugin

A WordPress plugin that plays the **DRUMI door-opening animation** as a full-screen overlay when visitors land on your site. The animated door opens, zooms toward the camera, then fades away to reveal the WordPress page behind it.

---

## 📁 Folder Structure

```
door-open-intro/
├── door-open-intro.php        ← Main plugin file
├── door-overlay/
│   └── overlay.html           ← Self-contained animation (all assets inlined)
└── README.md
```

---

## 🚀 Installation

### Method 1 — Upload via WordPress Admin
1. Zip the entire `door-open-intro/` folder
2. Go to **WP Admin → Plugins → Add New → Upload Plugin**
3. Upload the zip and activate

### Method 2 — FTP / File Manager
1. Copy the `door-open-intro/` folder to:
   ```
   /wp-content/plugins/door-open-intro/
   ```
2. Go to **WP Admin → Plugins** and activate **Door Open Intro**

---

## ⚙️ How It Works

1. When a visitor opens your WordPress site, a full-screen overlay appears
2. The DRUMI door animation auto-plays:
   - Doors open (3D rotation) ← **1.4 seconds**
   - Camera walks through (zoom) ← **1.3 seconds**
3. The overlay fades out → your WordPress homepage is revealed beneath it
4. Uses `sessionStorage` so the animation only plays **once per browser session**
   (the visitor won't see it again until they close and reopen the browser)

---

## 🔄 Updating the Animation

If you update the door animation in the React project:

```bash
# In the project root:
npm run build:overlay

# Then copy the new file:
# dist-overlay/overlay.html → wp-content/plugins/door-open-intro/door-overlay/overlay.html
```

---

## 🛠️ Plugin Settings

Go to **WP Admin → Settings → Door Open Intro** to:
- Check that `overlay.html` is properly installed
- Preview the overlay directly
- (Future) Configure timing and skip behaviour

---

## 🔗 Troubleshooting

| Problem | Fix |
|---|---|
| Overlay doesn't appear | Check `overlay.html` exists in `door-overlay/` folder |
| Animation never ends | Check browser console for iframe errors; ensure no CSP blocking |
| Shows every page load | Clear `sessionStorage` in browser DevTools |
| Overlay blocks content | The safety timeout is 12 seconds max |
