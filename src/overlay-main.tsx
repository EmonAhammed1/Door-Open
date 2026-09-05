import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import WPOverlay from "./WPOverlay";

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <WPOverlay />
  </StrictMode>
);
