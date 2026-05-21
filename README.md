# TableWise

TableWise is a customizable WordPress table of contents plugin for posts and pages. It supports multiple TOC layouts, live settings preview, sticky headers, active section tracking, reading time, color presets, and per-post controls.

## Repository

- GitHub: https://github.com/Cr8v-Stacks/Tablewise
- Support: cr8vstacks@gmail.com

## Features

- Auto-generates a table of contents from configurable H2-H6 headings.
- Layouts: Minimalist, Manuscript, Soft Editorial, and Brutalist.
- Depth-aware child heading styles for H3-H6 across all layouts.
- Layout-aware Default and Dark color presets, plus Light, Ocean, Forest, and Rose presets.
- Live admin preview for layout, color, typography, display, progress, and visibility controls.
- Sticky TOC header with configurable top offset.
- Reading time estimate and reading progress indicators.
- Per-post overrides for Classic Editor, Block Editor, and Quick Edit.
- `[wptw_toc]` shortcode for manual placement.

## Installation

1. Upload the `tablewise` folder to `/wp-content/plugins/`.
2. Activate TableWise in WordPress.
3. Go to **Settings -> TableWise** to configure global behavior.
4. Optionally override settings per post.

## Development Notes

- Main plugin file: `tablewise.php`
- Settings: `includes/settings-page.php`
- Frontend rendering and styles: `includes/frontend.php`
- Defaults: `includes/defaults.php`
- Helpers and color presets: `includes/helpers.php`

## License

GPL-2.0-or-later.
