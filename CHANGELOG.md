# Changelog

## 1.3.0

- Added selectable TOC layouts: Minimalist, Manuscript, Soft Editorial, and Brutalist.
- Renamed the original layout to Minimalist and made Manuscript the default layout.
- Added a dedicated Layouts tab in the dashboard.
- Redesigned the settings dashboard with a wider live preview.
- Added live preview updates for layout, colour, typography, display, progress, and visibility controls.
- Added Active and Default badges for layout choices and colour presets.
- Colour-coded Active and Default badges so status is visible at a glance.
- Restored the original TOC as the Minimalist layout option, then refined it so it does not feel visually flat.
- Reworked new layouts so H2 entries render as primary sections and H3-H6 entries render as child links.
- Tightened layout and colour preset compatibility so header and body backgrounds stay visually distinct.
- Updated Brutalist and Editorial header colour handling to respect preset background rules.
- Reworked admin live preview overrides so preview layout styling tracks the frontend more closely.
- Replaced the duplicated admin preview layout stylesheet with the same TOC stylesheet used on the frontend.
- Disabled stale preview-only layout rules that were preventing non-Minimalist previews from matching frontend output.
- Disabled old global admin layout selectors so preview headers and bodies are styled only by the shared frontend stylesheet.
- Synced admin preview read/active state handling with the frontend state classes for layout-specific body styling.
- Consolidated Manuscript header and body styling into a final shared stylesheet block used by frontend and admin preview.
- Reworked Manuscript to more closely follow the sample mockup: mono eyebrow header, dark editorial body, serif titles, subdued read states, and amber progress.
- Fixed Manuscript timeline alignment so the vertical line runs through the center of the circle nodes.
- Hardened Manuscript timeline rules against older duplicated CSS so the node column, list spacing, and pseudo-elements cannot drift from the mockup structure.
- Verified the edited plugin copy is the `wp-content/plugins/tablewise` installation and checked the frontend/admin Manuscript markup selectors for mismatches.
- Improved colour preset rules so header and body backgrounds stay distinct and secondary text has stronger contrast.
- Reworked Manuscript colours with a derived ink/paper palette so dark presets no longer create dark-on-dark text.
- Matched Manuscript background and text treatment to the mockup's fixed ink surface, amber accent, and white-opacity secondary colours.
- Added colour normalization on save and in the admin live preview to prevent header/body, text/background, and toggle colour clashes.
- Added a Default colour preset that restores each active layout's native colour direction instead of treating Light as the default.
- Fixed the Default colour preset so it stays applied when switching layouts and immediately reapplies the new layout's native colours.
- Removed separate Default badges from layout and colour preset cards so the UI only shows Active state.
- Removed the accidental Manuscript colour preset; Manuscript now belongs to layout defaults, not the preset list.
- Updated the plugin's real default colour settings to match the active default layout's native Manuscript palette.
- Migrated the previous light-based default colour state in the dashboard to the layout-native Default preset on load.
- Restored layout-native Default palettes for Soft Editorial and Brutalist instead of forcing the shared light palette onto every layout.
- Changed the Reset colour button to apply the active layout's Default palette so it no longer turns every layout into Manuscript colours.
- Rebuilt the layout-native Default palettes around the evolved designs: Manuscript dark ink/orange, Soft Editorial white with dark/soft-dark UI, Brutalist dark body with darker header and light active state, and Minimalist original clean light card.
- Removed the dashboard's old light-default auto-migration so saved colours are not unexpectedly converted on page load.
- Adjusted Brutalist active-row text contrast for its native light active state.
- Made Default, Light, and Dark preset application layout-aware so Dark no longer reuses Manuscript colours across every layout.
- Expanded layout-aware colour handling to Ocean, Forest, and Rose presets so colour themes no longer leak one layout's assumptions into another.
- Changed colour Reset behavior to reapply the user's active preset for the active layout instead of forcing the Default palette.
- Updated per-colour reset buttons to restore the active preset value for that colour when a preset is selected.
- Reworked Brutalist native/dark colours to use black surfaces, lighter-black active rows, white active text, and softer white child text instead of orange UI accents.
- Restored the legacy preset colour values for Light, Dark, Ocean, Forest, and Rose so presets update background fields the way they did in the working plugin copy.
- Simplified colour preset badges so Active follows the selected preset instead of disappearing when contrast normalization changes preview-only colours.
- Made the dashboard initialize with the active layout's Default colour preset applied and marked active.
- Limited layout-aware preset overrides to Default and layout-specific Dark variants, preventing Ocean, Forest, and Rose backgrounds from being hardcoded per layout.
- Fixed Brutalist offset borders in the live preview and frontend so Default/Dark states use the layout border colour instead of disappearing against the canvas.
- Fixed Brutalist reading-time contrast by using the configured reading-time colour instead of deriving text from the body background.
- Stabilized colour preset Active badges so Reset, layout switching, and temporary custom edits keep the last selected preset active instead of falling back through colour matching.
- Fixed Manuscript sticky TOC header support by allowing the sticky clone to use the Manuscript eyebrow header when no generic TOC header/toggle exists.
- Kept saved Active badges fixed for layout and colour choices until the user saves a new active state.
- Strengthened frontend border rendering for light colour presets so layout frames stay visible on light backgrounds.
- Restyled Hide/Show controls per layout, including pill controls for Editorial/Manuscript and a squared offset button for Brutalist.
- Added the Manuscript Hide/Show control back into the Manuscript header so toggle and sticky behavior use the same controls as other layouts.
- Added depth-aware child TOC classes and styling for H3-H6 inside Manuscript, Soft Editorial, and Brutalist layouts.
- Expanded Minimalist child-depth styling so H5 and H6 retain distinct indentation inside the original clean card layout.
- Made Light a true light preset and made Manuscript respond to preset background colours while keeping readable text/secondary states.
- Forced low-contrast active/progress accents back to a readable orange-on-dark or ink-on-light fallback.
- Added Manuscript reading time to the far right of the Contents header.
- Refined the Manuscript header with a subtle darker header plane and right-aligned reading-time pill.
- Removed unused legacy admin preview CSS selectors that no longer apply to the live preview.
- Simplified Manuscript header controls to match the sample mockup's single-eyebrow header treatment.
- Rebuilt Manuscript markup to match the sample structure instead of forcing the generic TOC header/list wrappers to imitate it.
- Fixed a PHP 7.4 compatibility issue that could stop the admin live preview script from loading.
- Matched layout-specific reading progress indicators across frontend, sticky header, and admin preview.
- Improved progress indicator contrast for dark headers and dark layout backgrounds.
- Removed the Minimalist left-accent active-row treatment.
- Improved Soft Editorial completed-state numbering so only read items show checkmarks.
- Fixed duplicate Manuscript markers.
- Refined Brutalist spacing, active state, and offset border treatment.
- Hardened TOC text, link, list, and button styles against theme defaults.
- Improved toggle button contrast across presets and layouts.
- Added sticky settings-page header and custom admin footer.
- Removed WordPress footer attribution from the TableWise settings screen.
- Added shortcut buttons between Layouts and Colours tabs.

## 1.2.0

- Improved sticky header logic for better viewport tracking.
- Added Per-Post Settings via Gutenberg Sidebar.
- Enhanced Quick Edit integration with expanded controls.
- Refined reading time estimation with configurable WPM.
- Fixed a TOC nesting bug where the table could latch on non-article paragraphs.
- Added general performance and accessibility improvements.

## 1.1.0

- Complete plugin rewrite.
- Added sticky TOC header with configurable top offset and stuck-shadow feedback.
- Added per-post meta box for Classic Editor.
- Added Gutenberg sidebar panel.
- Added Quick Edit column with inline TOC controls.
- Added 5 colour presets: Light, Dark, Ocean, Forest, Rose.
- Added `color_active_bg`, `color_back_top_bg`, and `color_back_top_fg` colour controls.
- Added configurable words-per-minute for reading time.
- Added back-to-top floating button with smooth scroll back to TOC.
- Added uninstall cleanup hook.
- Improved anchor handling to preserve existing heading IDs.
- Added IntersectionObserver for active highlighting and sticky detection.
- Refactored CSS to custom properties for easier theming.
- Renamed shortcode to `[wptw_toc]`.

## 1.0.0

- Initial release.
