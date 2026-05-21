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

Customisable Table of Contents with layouts, live preview, active tracking, reading time, color presets, sticky headers, and per-post controls.

== Description ==

TableWise automatically generates a polished, accessible Table of Contents for WordPress posts and pages. It gives site owners a complete TOC design system: multiple frontend layouts, live dashboard preview, layout-aware color presets, typography controls, reading progress, sticky behavior, and per-post overrides.

The plugin is built for real publishing workflows. You can keep the original clean card design, switch to a dark editorial manuscript layout, use a soft editorial card, or choose a bold brutalist TOC. Each layout includes its own header, body, child-heading treatment, progress styling, and Hide/Show button style.

= Layouts =

* Manuscript: dark editorial layout with chapter-style timeline markers, amber progress, right-aligned reading time, and depth-aware child heading treatment.
* Soft Editorial: clean editorial card with guided sections, completed-state checkmarks, dark/soft-dark typography, and nested child rows.
* Brutalist: bold typographic layout with dark body, darker header, offset border extension, squared controls, high-contrast active rows, and nested child styling.
* Minimalist: the original clean light card layout for users who prefer the classic TableWise look.

= Dashboard And Live Preview =

* Redesigned settings dashboard with Visibility, Headings, Layouts, Display, Colours, Typography, and Advanced tabs.
* Wider live preview that uses the same frontend stylesheet as the public TOC.
* Desktop/mobile preview toggle.
* Sticky dashboard header for easier editing.
* Saved Active badges for the current layout and color preset.
* Shortcut links between Layouts and Colours.
* Custom TableWise admin footer on the settings screen.

= Display And Behaviour =

* Auto-generates a TOC from selected heading levels, from H2 through H6.
* Supports public post types, minimum heading count, excluded post IDs, and custom anchor prefixes.
* Preserves existing heading IDs when possible and generates anchors for headings that need them.
* Configurable placement: before first heading, after first paragraph, or manual via shortcode.
* Default open or closed state, with per-post override support.
* Smooth scroll with configurable offset for sticky site headers.
* Active-section highlighting using the IntersectionObserver API.
* Animated expand/collapse with accessible ARIA attributes.
* Optional hierarchical section numbering.
* Optional back-to-top button.

= Reading Experience =

* Estimated reading time displayed in the TOC header.
* Configurable words-per-minute rate.
* Scroll reading progress indicator that matches the active layout.
* Read/done states for sections the reader has passed.
* Sticky TOC header with configurable top offset for fixed site navigation.
* Layout-specific Hide/Show controls.

= Design Controls =

* Six color presets: Default, Light, Dark, Ocean, Forest, and Rose.
* Default preset restores each layout's native colors instead of forcing every layout into one shared palette.
* Layout-aware Dark preset so dark mode fits each layout's design.
* Seventeen color controls covering card, border, header, label, reading time, progress, toggle button, links, active state, numbers, and back-to-top button.
* Contrast normalization on save and in preview to reduce text/background clashes.
* Typography controls for font family, link text, child text, label text, reading time, numbers, label letter spacing, label transform, and border radius.
* Font options include system fonts plus Inter, DM Sans, Lato, Nunito, Open Sans, Poppins, Raleway, Roboto, Source Sans 3, Work Sans, Playfair Display, Merriweather, DM Mono, Fira Mono, and JetBrains Mono.
* Custom CSS field for full theme-specific override control.

= Per-Post Control =

* Classic Editor meta box with key overrides.
* Block Editor sidebar panel via registerPlugin.
* Quick Edit support from the posts list.
* Per-post controls for disabling the TOC, title, initial state, placement, numbers, sticky header, and reading time.

= Developer-Friendly =

* No jQuery required on the frontend.
* CSS custom properties for easier theme integration.
* Theme hardening for TOC links, lists, buttons, labels, numbers, and layout surfaces.
* `[wptw_toc]` shortcode for manual placement.
* Clean, namespaced code using the `wptw_` prefix.
* Uninstall hook clears plugin data.

== Installation ==

1. Upload the `tablewise` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings > TableWise** to configure global behavior.
4. Optionally override settings per post through the editor sidebar/meta box or Quick Edit.

== Shortcode ==

Use `[wptw_toc]` anywhere in your content when Position is set to "Manual - shortcode only". All layout, color, typography, display, and per-post settings still apply.

== Frequently Asked Questions ==

= Will the TOC always appear? =

No. The TOC only appears if the post type is enabled, the post meets the minimum heading count, the post ID is not excluded, and TOC is not disabled for that specific post.

= Can I switch between different TOC designs? =

Yes. Go to **Settings > TableWise > Layouts** and choose Manuscript, Soft Editorial, Brutalist, or Minimalist. The Active badge shows the saved frontend layout.

= Can I preview changes before saving? =

Yes. The dashboard includes a live preview for layout, color, typography, display, progress, and visibility settings.

= What does the Default color preset do? =

Default restores the active layout's native color scheme. It is different from Light, which is a shared light preset.

= Can every layout use the color presets? =

Yes. Presets apply through the same color controls, with layout-aware handling where a layout needs its own dark or native color direction.

= Can I customize the typography? =

Yes. TableWise includes controls for font family, link text, child text, label text, reading time, numbers, label letter spacing, label transform, and border radius.

= How do I make the TOC hidden by default? =

Go to **Settings > TableWise > Display** and set "Default TOC state" to "Closed". You can also override this per post.

= How does the sticky header work? =

When enabled, the TOC header sticks while the reader scrolls. Use "Sticky top offset" to clear fixed site headers or admin bars.

= Can I change the TOC title per post? =

Yes. Use the TableWise sidebar panel in the Block Editor, the Classic Editor meta box, or Quick Edit controls.

= Does TableWise require jQuery? =

No. The frontend behavior is written without jQuery.

= What CSS selector do I use for custom styles? =

Use `.wptw-toc` as your root selector in the Custom CSS field. Internal elements use the `.wptw-toc__*` naming pattern.

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
* Expanded README and WordPress readme documentation to cover the full layout, preview, color, typography, reader, and per-post feature set

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
* Improved anchor handling - preserves existing heading IDs
* IntersectionObserver used for both active highlighting and sticky detection
* All CSS refactored to custom properties for easy theming
* Renamed shortcode to `[wptw_toc]`
* Author: Cr8v Stacks (https://cr8vstacks.com)

= 1.0.0 =
* Initial release
