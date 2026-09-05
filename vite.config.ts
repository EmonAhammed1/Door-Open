import path from "path";
import { fileURLToPath } from "url";
import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";
import { viteSingleFile } from "vite-plugin-singlefile";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const isOverlay = mode === "overlay";

  return {
    plugins: [react(), tailwindcss(), viteSingleFile()],
    resolve: {
      alias: {
        "@": path.resolve(__dirname, "src"),
      },
    },
    server: {
      // Prevent Vite from watching the wordpress-plugin output folder
      watch: {
        ignored: [
          "**/wordpress-plugin/**",
          "**/dist-overlay/**",
          "**/dist/**",
        ],
      },
    },
    // Override the entry for overlay mode
    ...(isOverlay
      ? {
          build: {
            rollupOptions: {
              input: path.resolve(__dirname, "overlay.html"),
            },
            outDir: "dist-overlay",
          },
        }
      : {}),
  };
});
