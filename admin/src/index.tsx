import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { ApplicantsPage } from "./screens/ApplicantsPage";

createRoot(document.getElementById("app") as HTMLElement).render(
  <StrictMode>
    <ApplicantsPage />
  </StrictMode>,
);
