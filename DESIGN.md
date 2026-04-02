# Design System Strategy: The Living Heritage

## 1. Overview & Creative North Star
**Creative North Star: "The Digital Heirloom"**

This design system moves away from the clinical, rigid structures of traditional genealogy software. Instead of a "database," we are building a "nurtured garden." The system is anchored in **Organic Editorialism**—a style that balances high-end sophisticated layouts with the warmth of a family home. 

We break the "template" look by utilizing intentional asymmetry, where family cards might overlap subtle background shapes, and typography scales are pushed to extremes to create a sense of importance and story. The interface should feel like a premium digital scrapbook: tactile, layered, and deeply personal.

---

## 2. Colors & Surface Philosophy
The palette is a sophisticated take on "family-friendly." We avoid primary-school brights in favor of desaturated, "earth-and-sky" tones that suggest longevity and trust.

### The "No-Line" Rule
To achieve a premium, modern feel, **1px solid borders are strictly prohibited for sectioning.** Boundaries must be defined through:
*   **Background Shifts:** Transitioning from `surface` (#fbf6ec) to `surface-container-low` (#f5f0e5).
*   **Tonal Transitions:** Using soft color blocks to define content zones.

### Surface Hierarchy & Nesting
Think of the UI as physical layers of fine paper. 
*   **Base:** `background` (#fbf6ec).
*   **Nesting:** Place a `surface-container-highest` (#e2dcd0) card inside a `surface-container-low` (#f5f0e5) section. This "recessed" or "elevated" effect creates depth without clutter.

### The "Glass & Gradient" Rule
To add "soul" to the digital experience:
*   **Signature Textures:** Use a subtle linear gradient from `primary` (#446349) to `primary-container` (#d1f4d2) for hero actions. This mimics the dappled light of a forest canopy.
*   **Glassmorphism:** Use `surface-container-lowest` (#ffffff) at 70% opacity with a `20px backdrop-blur` for floating navigation bars or modal overlays.

---

## 3. Typography
We use a dual-font strategy to balance authority with accessibility.

*   **Display & Headlines (Plus Jakarta Sans):** A modern sans-serif with a high x-height. It feels "designed" and premium. Use `display-lg` (3.5rem) for major family names to create a sense of heritage.
*   **Body & Labels (Be Vietnam Pro):** Chosen for its exceptional legibility and friendly, open apertures. It keeps the "Pohon Keluarga" (Family Tree) data-heavy sections feeling light and airy.
*   **The Editorial Tilt:** Use `headline-sm` in italics or with increased letter spacing for "Date of Birth" or "Ancestry Notes" to give the UI a curated, biographical feel.

---

## 4. Elevation & Depth
In this system, depth is a feeling, not a shadow.

*   **Tonal Layering:** Avoid drop shadows for standard cards. Use the difference between `surface-container` tiers to imply hierarchy. A `surface-container-lowest` card on a `surface-container` background provides a soft, natural "lift."
*   **Ambient Shadows:** For high-priority floating elements (like an "Add Relative" FAB), use a tinted shadow: `color: rgba(48, 47, 40, 0.06)` (a 6% opacity tint of `on-surface`) with a `32px blur` and `16px Y-offset`.
*   **The "Ghost Border" Fallback:** If a container sits on a background of the same color, use a 1px border of `outline-variant` (#b1ada4) at **15% opacity**. It should be felt, not seen.

---

## 5. Components

### Buttons & Interaction
*   **Primary:** High roundness (`rounded-full`). Use the signature Primary-to-PrimaryContainer gradient.
*   **Secondary/Tertiary:** Use `secondary-container` (#b7d3ff) with `on-secondary-container` (#2a496e) text. No borders.
*   **States:** On hover, a button shouldn't just darken; it should "grow" slightly (scale 1.02) to feel responsive and alive.

### Family Cards & Lists
*   **Rule:** Forbid divider lines. 
*   **Implementation:** Separate family members using `spacing-6` (2rem) of vertical white space or by alternating background tones between `surface-container-low` and `surface-container-high`.
*   **Shape:** Use `rounded-xl` (3rem) for the top-left and bottom-right corners, and `rounded-md` (1.5rem) for the others to create a custom "leaf" or "organic" shape.

### Input Fields
*   **Styling:** Use `surface-container-highest` for the input track. 
*   **Focus:** Instead of a thick border, the background should shift to `primary-fixed-dim` (#c3e6c5) on focus.

### Additional Signature Components
*   **The "Legacy Node":** A custom avatar component for the family tree using a `rounded-lg` (2rem) container with a thick `surface` (#fbf6ec) offset stroke to make the photo "pop" against the organic background shapes.
*   **Organic Backdrops:** Use SVG blobs in `tertiary-container` (#f9d377) at 20% opacity, placed asymmetrically behind content groups to break the grid.

---

## 6. Do’s and Don’ts

### Do
*   **Do** use extreme roundness (16px+) to reinforce the "friendly" vibe.
*   **Do** leverage white space. If a section feels crowded, double the spacing using the `spacing-12` or `spacing-16` tokens.
*   **Do** use "Plus Jakarta Sans" for numbers (dates, ages) to keep them feeling elegant.

### Don’t
*   **Don’t** use pure black (#000000) for text. Always use `on-surface` (#302f28) to maintain the "warm" atmosphere.
*   **Don’t** use sharp corners. Even "small" components like checkboxes must use at least `rounded-sm` (0.5rem).
*   **Don’t** use standard 1px dividers. They create "visual noise" that breaks the premium, editorial flow of the family story.