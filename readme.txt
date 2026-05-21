=== TableWise ===
Contributors: cr8vstacks
Tags: table of contents, toc, navigation, reading time, sticky
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Plugin URI: https://github.com/Cr8v-Stacks/Tablewise
Support: cr8vstacks@gmail.com

Customisable Table of Contents for posts. Features sticky header, active tracking, reading time, color presets, and per-post control.

== Description ==

TableWise automatically generates a beautiful, accessible Table of Contents for your WordPress posts and pages. Every aspect is fully controllable — no coding required.

**Key Features**

= Display & Behaviour =
* Auto-generates TOC from H2–H6 headings (configurable per site)
* Configurable placement: before first heading, after first paragraph, or manual via shortcode
* Default open or closed state — overridable per post
* Smooth scroll with configurable offset for sticky site headers
* Active-section highlighting using the performant IntersectionObserver API
* Animated expand/collapse with proper ARIA attributes (accessible)
* Optional hierarchical section numbering (1. / 1.1. / 2.)

= Sticky Header =
* TOC header (title + reading time + toggle) sticks to the viewport as the page scrolls
* Configurable top offset to clear your site's fixed navigation
* Adds a subtle drop shadow when actively stuck — subtle and clean

= Reading Time =
* Estimated reading time displayed in the TOC header (e.g. "6 min read")
* Configurable words-per-minute rate to match your audience
* Can be shown/hidden globally or per post

= Colour Control =
* 13 individually-adjustable colour settings
* 6 built-in colour presets: Default, Light, Dark, Ocean, Forest, Rose
* One-click preset application in the settings panel

= Per-Post Control =
* Classic Editor: sidebar meta box with all key overrides
* Block Editor (Gutenberg): dedicated sidebar panel via registerPlugin
* Quick Edit: TOC column in post list with inline disable/state/position/numbers controls
* Per-post overrides: disable TOC, initial state, position, title, numbers, sticky header, reading time

= Developer-Friendly =
* Zero external dependencies — no jQuery on the frontend, no CDN fonts
* CSS custom properties for easy theme integration
* Custom CSS field for full override control
* `[wptw_toc]` shortcode for manual placement
* Clean, namespaced code (wptw_ prefix throughout)
* Uninstall hook clears all plugin data

== Installation ==

1. Upload the `tablewise` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to **Settings → TableWise** to configure
4. Optionally override settings per post via the post editor sidebar or Quick Edit

== Shortcode ==

Use `[wptw_toc]` anywhere in your content when Position is set to "Manual — shortcode only". All settings come from the Settings panel. Per-post meta box overrides apply as normal.

== Frequently Asked Questions ==

= Will the TOC always appear? =
No. The TOC only appears if the post type is enabled in Settings, the post meets the minimum heading count, the post ID is not in the exclude list, and TOC is not disabled for that specific post.

= How do I make the TOC hidden by default? =
Go to **Settings → TableWise → Display** and set "Default TOC state" to "Closed". You can also override this per post from the editor or Quick Edit.

= How does the sticky header work? =
When enabled, the TOC header (containing the title, reading time, and toggle button) uses CSS `position: sticky` to remain visible as the user scrolls through the list. The list itself scrolls normally. Set the "Sticky top offset" to the height of your site's fixed header so they don't overlap.

= Can I change the TOC title per post? =
Yes. Open any post in the editor and use the TableWise sidebar panel (Gutenberg) or meta box (Classic Editor) to set a custom title for that post.

= What CSS selector do I use for custom styles? =
Use `.wptw-toc` as your root selector in the Custom CSS field. All internal elements are prefixed `.wptw-toc__*`.

== Changelog ==

= 1.3.0 =
* Added selectable TOC layouts: Minimalist, Manuscript, Soft Editorial, and Brutalist
* Made Manuscript the default layout
* Added saved Active badges for layouts and colour presets
* Redesigned the settings dashboard with a wider live preview and dedicated Layouts tab
* Added live preview updates for layout, colour, typography, display, progress, and visibility controls
* Restored the original TOC as the Minimalist layout option
* Reworked layouts to render H2 entries as primary sections and H3-H6 entries as depth-aware child links
* Tightened layout and colour preset compatibility so header and body backgrounds stay visually distinct
* Updated Brutalist and Editorial header colour handling to respect preset background rules
* Reworked admin live preview overrides so preview layout styling tracks the frontend more closely
* Replaced the duplicated admin preview layout stylesheet with the same TOC stylesheet used on the frontend
* Disabled stale preview-only layout rules that were preventing non-Minimalist previews from matching frontend output
* Disabled old global admin layout selectors so preview headers and bodies are styled only by the shared frontend stylesheet
* Synced admin preview read/active state handling with the frontend state classes for layout-specific body styling
* Consolidated Manuscript header and body styling into a final shared stylesheet block used by frontend and admin preview
* Reworked Manuscript to more closely follow the sample mockup: mono eyebrow header, dark editorial body, serif titles, subdued read states, and amber progress
* Fixed Manuscript timeline alignment so the vertical line runs through the center of the circle nodes
* Simplified Manuscript header controls to match the sample mockup's single-eyebrow header treatment
* Rebuilt Manuscript markup to match the sample structure instead of forcing the generic TOC header/list wrappers to imitate it
* Fixed a PHP 7.4 compatibility issue that could stop the admin live preview script from loading
* Matched layout-specific reading progress indicators across frontend, sticky header, and admin preview
* Improved progress indicator contrast for dark headers and dark layout backgrounds
* Improved Soft Editorial completed-state numbering so only read items show checkmarks
* Fixed duplicate Manuscript markers and refined Brutalist spacing, active state, and offset border treatment
* Removed the Minimalist left-accent active-row treatment
* Hardened TOC text, link, list, and button styles against theme defaults
* Restored legacy colour preset behavior while preserving layout-native Default and Dark variants
* Fixed Sticky TOC support for Manuscript and confirmed Sticky top offset applies to all layouts
* Restyled Hide/Show buttons per layout
* Strengthened light-preset borders and layout frame contrast
* Added sticky settings-page header, admin footer, and cross-links between Layouts and Colours
* Removed WordPress footer attribution from the TableWise settings screen

= 1.2.0 =
* Improved sticky header logic for better viewport tracking
* Added Per-Post Settings via Gutenberg Sidebar (registerPlugin)
* Enhanced Quick Edit integration with expanded controls
* Refined reading time estimation with configurable WPM
* Fixed a TOC nesting bug where the table could latch on non-article paragraphs
* General performance and accessibility improvements

= 1.1.0 =
* Complete plugin rewrite
* Added sticky TOC header with configurable top offset and stuck-shadow feedback
* Added per-post meta box (Classic Editor) with disable, state, position, title, numbers, sticky, reading-time overrides
* Added Gutenberg sidebar panel via registerPlugin API
* Added Quick Edit column with inline TOC controls
* Added 5 colour presets (Light, Dark, Ocean, Forest, Rose)
* Added `color_active_bg`, `color_back_top_bg`, `color_back_top_fg` colour controls
* Added configurable words-per-minute for reading time
* Added back-to-top floating button with smooth scroll back to TOC
* Added uninstall cleanup hook
* Improved anchor handling — preserves existing heading IDs
* IntersectionObserver used for both active highlighting and sticky detection
* All CSS refactored to custom properties for easy theming
* Renamed shortcode to `[wptw_toc]`
* Author: Cr8v Stacks (https://cr8vstacks.com)

= 1.0.0 =
* Initial release
