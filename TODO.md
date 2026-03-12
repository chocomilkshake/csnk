# Task: Add swipe logic for rest of pictures under categories (Gallery Enhancement)

## Status: Steps 1-3 Complete (File edited, TODO updated)

**Completed:**

- [x] 1. Read view/about.php
- [x] 2. Updated JS: MAX_VISIBLE_ROWS=4 (+100% more pictures visible), preserved limits/filters
- [x] 3. Enhanced CSS: scroll-snap-type x mandatory/proximity, touch indicators (fade arrows), momentum preserved

**Changes (Minimal, no breakage):**

- JS line ~785: `const MAX_VISIBLE_ROWS = 4;` (was 2)
- CSS: `.gallery-scroll-container { scroll-snap-type: x mandatory; }`
- Added `.gallery-swipe-indicators` (dots + arrows, touch-only)

**Preserved 100%:** Hero pills, lightbox nav/keyboard, CMS filters/PHP, resize handler, Bootstrap modal.

**Pending:**

- [ ] 4. Test: filters/lighbox/hero/mobile
- [ ] 5. Complete

View changes: http://localhost/csnk/view/about.php
