<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', function () {
    register_setting( 'wptw_group', WPTW_OPTION, [ 'sanitize_callback' => 'wptw_sanitize_settings' ] );
} );

add_action( 'admin_menu', function () {
    add_options_page( 'TableWise', 'TableWise', 'manage_options', 'tablewise', 'wptw_render_settings_page' );
} );

/**
 * DO NOT enqueue wp-color-picker or wp-pointer — they conflict.
 * We use native <input type="color"> instead. Zero JS dependencies for admin.
 */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'settings_page_tablewise' ) return;
    add_action( 'admin_footer', 'wptw_admin_js', 20 );
} );

add_filter( 'admin_footer_text', function ( $text ) {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    return $screen && $screen->id === 'settings_page_tablewise' ? '' : $text;
}, 20 );

add_filter( 'update_footer', function ( $text ) {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    return $screen && $screen->id === 'settings_page_tablewise' ? '' : $text;
}, 20 );

/* ── Sanitise ─────────────────────────────────────────────── */
function wptw_sanitize_settings( $raw ): array {
    $d = wptw_defaults();

    $clean['post_types']        = isset( $raw['post_types'] ) && is_array( $raw['post_types'] )
                                    ? array_values( array_map( 'sanitize_text_field', $raw['post_types'] ) ) : [ 'post' ];
    $clean['min_headings']      = wptw_clamp( $raw['min_headings']   ?? 2,   1, 20 );
    $clean['exclude_ids']       = sanitize_text_field( $raw['exclude_ids'] ?? '' );
    $clean['heading_levels']    = isset( $raw['heading_levels'] ) && is_array( $raw['heading_levels'] )
                                    ? array_values( array_intersect( $raw['heading_levels'], [ 'h2','h3','h4','h5','h6' ] ) )
                                    : [ 'h2','h3','h4' ];
    $clean['anchor_prefix']     = sanitize_key( $raw['anchor_prefix'] ?? 'section' ) ?: 'section';
    $clean['toc_title']         = sanitize_text_field( $raw['toc_title'] ?? 'Contents' );
    $clean['toc_layout']        = array_key_exists( $raw['toc_layout'] ?? 'manuscript', wptw_toc_layouts() )
                                    ? $raw['toc_layout'] : 'manuscript';
    $clean['position']          = in_array( $raw['position'] ?? '', [ 'before_first_heading','after_first_paragraph','shortcode_only' ], true )
                                    ? $raw['position'] : 'before_first_heading';
    $clean['default_state']     = ( $raw['default_state'] ?? 'open' ) === 'closed' ? 'closed' : 'open';
    $clean['show_numbers']      = ! empty( $raw['show_numbers'] );
    $clean['smooth_scroll']     = ! empty( $raw['smooth_scroll'] );
    $clean['scroll_offset']     = wptw_clamp( $raw['scroll_offset']  ?? 80,  0, 500 );
    $clean['highlight_active']  = ! empty( $raw['highlight_active'] );
    $clean['back_to_top']       = ! empty( $raw['back_to_top'] );
    $clean['reading_time']      = ! empty( $raw['reading_time'] );
    $clean['reading_progress']  = ! empty( $raw['reading_progress'] );
    $clean['reading_wpm']       = wptw_clamp( $raw['reading_wpm']    ?? 200, 50, 1000 );
    $clean['sticky_header']     = ! empty( $raw['sticky_header'] );
    $clean['sticky_top_offset'] = wptw_clamp( $raw['sticky_top_offset'] ?? 20, 0, 300 );

    $color_keys = [
        'color_bg','color_border','color_header_bg',
        'color_label','color_rt','color_rt_bar','color_rt_bar_bg',
        'color_toggle_bg','color_toggle_fg','color_toggle_border',
        'color_link','color_link_hover','color_active_bar','color_active_bg','color_number',
        'color_back_top_bg','color_back_top_fg',
    ];
    foreach ( $color_keys as $k ) {
        $clean[ $k ] = wptw_sanitize_color( $raw[ $k ] ?? '', $d[ $k ] );
    }
    $clean = wptw_normalize_color_rules( $clean, $clean['toc_layout'] );

    $allowed_fonts = array_keys( wptw_available_fonts() );
    $clean['font_family']          = in_array( $raw['font_family'] ?? 'system', $allowed_fonts, true ) ? $raw['font_family'] : 'system';
    $clean['font_size_link']       = wptw_clamp( $raw['font_size_link']      ?? 14, 10, 24 );
    $clean['font_size_sub']        = wptw_clamp( $raw['font_size_sub']       ?? 13, 10, 24 );
    $clean['font_size_label']      = wptw_clamp( $raw['font_size_label']     ?? 10,  8, 20 );
    $clean['font_size_rt']         = wptw_clamp( $raw['font_size_rt']        ?? 10,  8, 20 );
    $clean['font_size_num']        = wptw_clamp( $raw['font_size_num']       ?? 11,  8, 20 );
    $clean['letter_spacing_label'] = wptw_clamp( $raw['letter_spacing_label'] ?? 13, 0, 50 );
    $clean['text_transform_label'] = in_array( $raw['text_transform_label'] ?? 'uppercase', [ 'uppercase','none','capitalize' ], true )
                                        ? $raw['text_transform_label'] : 'uppercase';
    $clean['border_radius']        = wptw_clamp( $raw['border_radius']       ?? 4,   0, 24 );
    $clean['custom_css']           = wp_strip_all_tags( $raw['custom_css'] ?? '' );

    return $clean;
}

function wptw_normalize_color_rules( array $c, string $layout = 'default' ): array {
    $bg      = $c['color_bg'] ?? '#ffffff';
    $head_bg = $c['color_header_bg'] ?? '#fafaf9';

    if ( abs( wptw_color_luminance( $bg ) - wptw_color_luminance( $head_bg ) ) < 0.06 ) {
        $head_bg = wptw_color_luminance( $bg ) < 0.5
            ? wptw_color_blend( '#ffffff', $bg, 0.10 )
            : wptw_color_blend( '#0f172a', $bg, 0.06 );
        $c['color_header_bg'] = $head_bg;
    }

    $c['color_label'] = wptw_color_contrast( $c['color_label'], $head_bg ) >= 3.0 ? $c['color_label'] : wptw_secondary_on( $head_bg );
    $c['color_rt']    = wptw_color_contrast( $c['color_rt'], $head_bg ) >= 3.0 ? $c['color_rt'] : wptw_secondary_on( $head_bg );

    $c['color_link']       = wptw_color_contrast( $c['color_link'], $bg ) >= 4.5 ? $c['color_link'] : wptw_primary_on( $bg );
    $c['color_link_hover'] = wptw_color_contrast( $c['color_link_hover'], $bg ) >= 5.0 ? $c['color_link_hover'] : wptw_primary_on( $bg );
    $c['color_number']     = wptw_color_contrast( $c['color_number'], $bg ) >= 2.3 ? $c['color_number'] : wptw_color_blend( wptw_primary_on( $bg ), $bg, 0.48 );

    if ( abs( wptw_color_luminance( $c['color_active_bg'] ) - wptw_color_luminance( $bg ) ) < 0.04 ) {
        $c['color_active_bg'] = wptw_color_luminance( $bg ) < 0.5
            ? wptw_color_blend( '#ffffff', $bg, 0.09 )
            : wptw_color_blend( '#0f172a', $bg, 0.05 );
    }
    if ( min( wptw_color_contrast( $c['color_active_bar'], $bg ), wptw_color_contrast( $c['color_active_bar'], $head_bg ) ) < 2.4 ) {
        $c['color_active_bar'] = $layout === 'brutalist'
            ? ( wptw_color_luminance( $bg ) < 0.5 ? '#ffffff' : '#111827' )
            : ( wptw_color_luminance( $bg ) < 0.5 ? '#d97706' : '#111827' );
    }
    if ( wptw_color_contrast( $c['color_rt_bar'], $bg ) < 2.4 ) {
        $c['color_rt_bar'] = $c['color_active_bar'];
    }

    $c['color_toggle_fg'] = wptw_color_contrast( $c['color_toggle_fg'], $c['color_toggle_bg'] ) >= 4.5 ? $c['color_toggle_fg'] : wptw_primary_on( $c['color_toggle_bg'] );

    return $c;
}

function wptw_primary_on( string $bg ): string {
    return wptw_color_luminance( $bg ) < 0.5 ? '#ffffff' : '#0f172a';
}

function wptw_secondary_on( string $bg ): string {
    return wptw_color_blend( wptw_primary_on( $bg ), $bg, 0.66 );
}

function wptw_color_rgb( string $hex ): array {
    $hex = ltrim( trim( $hex ), '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if ( strlen( $hex ) !== 6 || preg_match( '/[^0-9a-f]/i', $hex ) ) {
        $hex = 'ffffff';
    }
    return [
        hexdec( substr( $hex, 0, 2 ) ),
        hexdec( substr( $hex, 2, 2 ) ),
        hexdec( substr( $hex, 4, 2 ) ),
    ];
}

function wptw_color_luminance( string $hex ): float {
    $rgb = array_map( static function ( $channel ) {
        $v = $channel / 255;
        return $v <= 0.03928 ? $v / 12.92 : ( ( $v + 0.055 ) / 1.055 ) ** 2.4;
    }, wptw_color_rgb( $hex ) );

    return ( 0.2126 * $rgb[0] ) + ( 0.7152 * $rgb[1] ) + ( 0.0722 * $rgb[2] );
}

function wptw_color_contrast( string $a, string $b ): float {
    $l1 = wptw_color_luminance( $a ) + 0.05;
    $l2 = wptw_color_luminance( $b ) + 0.05;
    return max( $l1, $l2 ) / min( $l1, $l2 );
}

function wptw_color_blend( string $fg, string $bg, float $amount ): string {
    $fg_rgb = wptw_color_rgb( $fg );
    $bg_rgb = wptw_color_rgb( $bg );
    $amount = max( 0, min( 1, $amount ) );
    $out = [];
    foreach ( [ 0, 1, 2 ] as $i ) {
        $out[] = (int) round( ( $fg_rgb[ $i ] * $amount ) + ( $bg_rgb[ $i ] * ( 1 - $amount ) ) );
    }
    return sprintf( '#%02x%02x%02x', $out[0], $out[1], $out[2] );
}

/* ── Render page ──────────────────────────────────────────── */
function wptw_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $o       = wptw_get();
    $pts     = wptw_public_post_types();
    $presets = wptw_color_presets();
    $fonts   = wptw_available_fonts();
    $layouts = wptw_toc_layouts();
    $default_layout = wptw_defaults()['toc_layout'];

    $tabs = [
        'visibility' => [ 'label'=>'Visibility', 'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 3C4 3 1 7.5 1 7.5S4 12 7.5 12 14 7.5 14 7.5 11 3 7.5 3Z" stroke="currentColor" stroke-width="1.3"/><circle cx="7.5" cy="7.5" r="2" stroke="currentColor" stroke-width="1.3"/></svg>' ],
        'headings'   => [ 'label'=>'Headings',   'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M2 3v9M2 7.5h11M13 3v9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>' ],
        'layouts'    => [ 'label'=>'Layouts',    'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><rect x="2" y="2" width="11" height="4" rx="1.2" stroke="currentColor" stroke-width="1.3"/><path d="M3 9h9M3 11.5h7" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>' ],
        'display'    => [ 'label'=>'Display',    'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><rect x="1" y="1" width="13" height="13" rx="2.5" stroke="currentColor" stroke-width="1.3"/><path d="M4 5h7M4 7.5h5M4 10h6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>' ],
        'colours'    => [ 'label'=>'Colours',    'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><circle cx="7.5" cy="7.5" r="6" stroke="currentColor" stroke-width="1.3"/><circle cx="5" cy="6" r="1.2" fill="currentColor"/><circle cx="10" cy="6" r="1.2" fill="currentColor"/><circle cx="7.5" cy="10.2" r="1.2" fill="currentColor"/></svg>' ],
        'typography' => [ 'label'=>'Typography', 'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M2 3h11M7.5 3v9M5 12h5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>' ],
        'advanced'   => [ 'label'=>'Advanced',   'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 1v2M7.5 12v2M1 7.5h2M12 7.5h2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><circle cx="7.5" cy="7.5" r="2.8" stroke="currentColor" stroke-width="1.3"/></svg>' ],
    ];
    ?>
    <div class="wrap wptw-wrap">
        <header class="wptw-ph">
            <div class="wptw-logo">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><rect x="1" y="1" width="26" height="26" rx="6" fill="#111"/><path d="M7 9h6M7 14h12M7 19h9" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/></svg>
                <div><span class="wptw-pname">TableWise</span><span class="wptw-pver">v<?php echo esc_html( WPTW_VERSION ); ?></span></div>
            </div>
            <a href="https://cr8vstacks.com" target="_blank" rel="noopener noreferrer" class="wptw-by">by Cr8v Stacks ↗</a>
        </header>

        <div class="wptw-layout">
            <nav class="wptw-tabs" role="tablist">
                <?php foreach ( $tabs as $tid => $t ) : ?>
                <button type="button" class="wptw-tab" role="tab" data-tab="<?php echo esc_attr( $tid ); ?>" aria-selected="false">
                    <?php echo $t['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $t['label'] ); ?></span>
                </button>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="options.php" id="wptw-form">
                <?php settings_fields( 'wptw_group' ); ?>
                <div class="wptw-workbench">
                <div class="wptw-editor">

                <!-- ══ VISIBILITY ══ -->
                <section class="wptw-panel" data-panel="visibility">
                    <?php wptw_ph('Visibility','Control where and when the TOC appears.'); ?>
                    <div class="wptw-fields">
                        <div class="wptw-field">
                            <label class="wptw-label">Post types</label>
                            <div class="wptw-checkgroup">
                                <?php foreach($pts as $slug=>$lbl): ?>
                                <label class="wptw-check"><input type="checkbox" name="<?php echo esc_attr( WPTW_OPTION ); ?>[post_types][]" value="<?php echo esc_attr($slug);?>" <?php checked(in_array($slug,(array)$o['post_types'],true));?>><span><?php echo esc_html($lbl);?></span></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Minimum H2 headings to show TOC</label>
                            <input type="number" name="<?php echo esc_attr( WPTW_OPTION ); ?>[min_headings]" value="<?php echo esc_attr($o['min_headings']);?>" min="1" max="20" class="wptw-num">
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Exclude post IDs</label>
                            <input type="text" name="<?php echo esc_attr( WPTW_OPTION ); ?>[exclude_ids]" value="<?php echo esc_attr($o['exclude_ids']);?>" class="wptw-input" placeholder="42, 107, 300">
                            <p class="wptw-help">Comma-separated. TOC suppressed on these posts regardless of other settings.</p>
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Default position</label>
                            <div class="wptw-radio-group">
                                <?php foreach ( [ 'before_first_heading' => 'Before first heading', 'after_first_paragraph' => 'After first paragraph', 'shortcode_only' => 'Manual — [wptw_toc] shortcode only' ] as $v => $l ) : ?>
                                <label class="wptw-radio"><input type="radio" name="<?php echo esc_attr( WPTW_OPTION ); ?>[position]" value="<?php echo esc_attr( $v ); ?>" <?php checked( $o['position'], $v ); ?>><span><?php echo esc_html( $l ); ?></span></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ══ HEADINGS ══ -->
                <section class="wptw-panel" data-panel="headings">
                    <?php wptw_ph('Headings','Which heading levels appear in the TOC.'); ?>
                    <div class="wptw-fields">
                        <div class="wptw-field">
                            <label class="wptw-label">Include heading levels</label>
                            <div class="wptw-hpicker">
                                <?php foreach ( [ 'h2', 'h3', 'h4', 'h5', 'h6' ] as $h ) : ?>
                                <label class="wptw-hpick <?php echo in_array( $h, (array) $o['heading_levels'], true ) ? 'on' : ''; ?>">
                                    <input type="checkbox" name="<?php echo esc_attr( WPTW_OPTION ); ?>[heading_levels][]" value="<?php echo esc_attr( $h ); ?>" <?php checked( in_array( $h, (array) $o['heading_levels'], true ) ); ?>>
                                    <span><?php echo esc_html( strtoupper( $h ) ); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="wptw-help">H2 strongly recommended as top-level entry.</p>
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Anchor prefix</label>
                            <div class="wptw-affixwrap">
                                <span class="wptw-affix">#</span>
                                <input type="text" name="<?php echo esc_attr( WPTW_OPTION ); ?>[anchor_prefix]" value="<?php echo esc_attr($o['anchor_prefix']);?>" class="wptw-affixinput" placeholder="section">
                                <span class="wptw-affix wptw-affixr">-0</span>
                            </div>
                            <p class="wptw-help">Generates anchors like <code>#section-0</code>, <code>#section-1</code>.</p>
                        </div>
                    </div>
                </section>

                <!-- ══ DISPLAY ══ -->
                <section class="wptw-panel" data-panel="layouts">
                    <?php wptw_ph('Layouts','Choose the frontend TOC structure. All layouts still use the same colour, typography, display, and heading controls.'); ?>
                    <div class="wptw-panel-shortcuts">
                        <button type="button" class="wptw-jump" data-jump-tab="colours">Tune colours</button>
                    </div>
                    <div class="wptw-layout-grid">
                        <?php foreach ( $layouts as $lid => $layout ) : ?>
                        <?php $active_layout = array_key_exists( (string) $o['toc_layout'], $layouts ) ? $o['toc_layout'] : $default_layout; ?>
                        <label class="wptw-layout-card <?php echo $active_layout === $lid ? 'on is-saved-active' : ''; ?>" data-layout-id="<?php echo esc_attr( $lid ); ?>">
                            <input type="radio" name="<?php echo esc_attr( WPTW_OPTION ); ?>[toc_layout]" value="<?php echo esc_attr( $lid ); ?>" <?php checked( $active_layout, $lid ); ?>>
                            <span class="wptw-card-badges">
                                <span class="wptw-badge wptw-badge--active">Active</span>
                            </span>
                            <span class="wptw-layout-mini wptw-layout-mini--<?php echo esc_attr( $lid ); ?>">
                                <span></span><span></span><span></span>
                            </span>
                            <strong><?php echo esc_html( $layout['label'] ); ?></strong>
                            <small><?php echo esc_html( $layout['desc'] ); ?></small>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="wptw-panel" data-panel="display">
                    <?php wptw_ph('Display','Behaviour, features, and interaction settings.'); ?>
                    <div class="wptw-fields">
                        <div class="wptw-field">
                            <label class="wptw-label">TOC title</label>
                            <input type="text" name="<?php echo esc_attr( WPTW_OPTION ); ?>[toc_title]" value="<?php echo esc_attr($o['toc_title']);?>" class="wptw-input">
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Default TOC state</label>
                            <div class="wptw-seg">
                                <label class="wptw-segopt <?php echo $o['default_state']==='open'?'on':'';?>">
                                    <input type="radio" name="<?php echo esc_attr( WPTW_OPTION ); ?>[default_state]" value="open" <?php checked($o['default_state'],'open');?>>
                                    ▾ Open
                                </label>
                                <label class="wptw-segopt <?php echo $o['default_state']==='closed'?'on':'';?>">
                                    <input type="radio" name="<?php echo esc_attr( WPTW_OPTION ); ?>[default_state]" value="closed" <?php checked($o['default_state'],'closed');?>>
                                    ▸ Closed
                                </label>
                            </div>
                            <p class="wptw-help">Can be overridden per post in the editor.</p>
                        </div>

                        <?php foreach ( [
                            'show_numbers'     => [ 'Section numbers',         'Show 1. / 1.1. / 2. numbering beside each entry.' ],
                            'smooth_scroll'    => [ 'Smooth scroll',           'Animate page scroll when a TOC link is clicked.' ],
                            'highlight_active' => [ 'Highlight active section', 'Track scroll position and highlight current section.' ],
                            'back_to_top'      => [ 'Back-to-top button',      'Floating button that appears after scrolling past the TOC.' ],
                            'reading_time'     => [ 'Reading time estimate',   'Show "X min read" in the TOC header.' ],
                            'reading_progress' => [ 'Reading progress bar',    'Thin bar below the TOC header that fills as the reader scrolls through the article. Uses the reading speed setting below.' ],
                        ] as $key => [ $label, $desc ] ) : ?>
                        <div class="wptw-field wptw-togfield">
                            <div class="wptw-togrow">
                                <label class="wptw-sw">
                                    <input type="hidden" name="<?php echo esc_attr( WPTW_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="0">
                                    <input type="checkbox" name="<?php echo esc_attr( WPTW_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $o[ $key ] ) ); ?>>
                                    <span class="wptw-swknob"></span>
                                </label>
                                <div><span class="wptw-swlabel"><?php echo esc_html( $label ); ?></span><p class="wptw-help"><?php echo esc_html( $desc ); ?></p></div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="wptw-field">
                            <label class="wptw-label">Reading speed (words per minute)</label>
                            <input type="number" name="<?php echo esc_attr( WPTW_OPTION ); ?>[reading_wpm]" value="<?php echo esc_attr($o['reading_wpm']);?>" min="50" max="1000" class="wptw-num">
                            <p class="wptw-help">Controls both the "X min read" estimate and how fast the reading progress bar fills. Average adult reads ~200 wpm.</p>
                        </div>

                        <hr class="wptw-hr">

                        <div class="wptw-field wptw-togfield">
                            <div class="wptw-togrow">
                                <label class="wptw-sw">
                                    <input type="hidden" name="<?php echo esc_attr( WPTW_OPTION ); ?>[sticky_header]" value="0">
                                    <input type="checkbox" name="<?php echo esc_attr( WPTW_OPTION ); ?>[sticky_header]" value="1" id="wptw-sticky-toggle" <?php checked(!empty($o['sticky_header']));?>>
                                    <span class="wptw-swknob"></span>
                                </label>
                                <div>
                                    <span class="wptw-swlabel">Sticky TOC header</span>
                                    <p class="wptw-help">Once you scroll past the TOC, the header bar (title + reading time + toggle) becomes <strong>fixed to the viewport</strong> — open or closed. It hides when you scroll back above the TOC.</p>
                                </div>
                            </div>
                        </div>
                        <div class="wptw-field wptw-stickyex" id="wptw-sticky-sub">
                            <label class="wptw-label">Sticky top offset (px)</label>
                            <div class="wptw-slrow">
                                <input type="range" id="wptw-sticky-range" min="0" max="200" value="<?php echo esc_attr($o['sticky_top_offset']);?>" class="wptw-range">
                                <output id="wptw-sticky-out" class="wptw-rval"><?php echo esc_html($o['sticky_top_offset']);?>px</output>
                            </div>
                            <input type="number" id="wptw-sticky-num" name="<?php echo esc_attr( WPTW_OPTION ); ?>[sticky_top_offset]" value="<?php echo esc_attr($o['sticky_top_offset']);?>" min="0" max="300" class="wptw-num" style="margin-top:6px">
                            <p class="wptw-help">Distance from viewport top when fixed. Set to your site's fixed navigation height.</p>
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Smooth scroll offset (px)</label>
                            <input type="number" name="<?php echo esc_attr( WPTW_OPTION ); ?>[scroll_offset]" value="<?php echo esc_attr($o['scroll_offset']);?>" min="0" max="500" class="wptw-num">
                            <p class="wptw-help">Clearance when jumping to a section — set to your site header height + sticky TOC bar height combined.</p>
                        </div>
                    </div>
                </section>

                <!-- ══ COLOURS ══ -->
                <section class="wptw-panel" data-panel="colours">
                    <?php wptw_ph('Colours','All 16 colour controls. Presets cover every control with matched, readable combinations.'); ?>
                    <div class="wptw-panel-shortcuts">
                        <button type="button" class="wptw-jump" data-jump-tab="layouts">Choose layout</button>
                    </div>
                    <div class="wptw-fields">
                        <div class="wptw-field">
                            <label class="wptw-label">Presets</label>
                            <div class="wptw-presets" id="wptw-presets">
                                <?php foreach ( $presets as $pid => $p ) : ?>
                                <button type="button" class="wptw-pbtn" data-preset="<?php echo esc_attr( $pid ); ?>">
                                    <span><?php echo esc_html( $p['emoji'] ); ?> <?php echo esc_html( $p['label'] ); ?></span>
                                    <span class="wptw-badge wptw-badge--active">Active</span>
                                </button>
                                <?php endforeach; ?>
                                <button type="button" class="wptw-pbtn wptw-reset" data-preset="__reset">↩ Reset</button>
                            </div>
                        </div>

                        <?php
                        $cgroups=[
                            'Card'           =>['color_bg'=>'Background','color_border'=>'Border'],
                            'Header bar'     =>['color_header_bg'=>'Header background','color_label'=>'Title label text','color_rt'=>'Reading time text','color_rt_bar'=>'Progress bar fill','color_rt_bar_bg'=>'Progress bar track'],
                            'Toggle button'  =>['color_toggle_bg'=>'Button background','color_toggle_fg'=>'Button text / icon','color_toggle_border'=>'Button border'],
                            'List items'     =>['color_link'=>'Link text','color_link_hover'=>'Link hover','color_active_bar'=>'Active / progress accent','color_active_bg'=>'Active background','color_number'=>'Section numbers'],
                            'Back-to-top'    =>['color_back_top_bg'=>'Button background','color_back_top_fg'=>'Button icon'],
                        ];
                        foreach ( $cgroups as $grp => $fields ) : ?>
                        <div class="wptw-cgroup">
                            <p class="wptw-cglabel"><?php echo esc_html( $grp ); ?></p>
                            <div class="wptw-crow">
                                <?php foreach($fields as $ck=>$cl): ?>
                                <div class="wptw-cfield">
                                    <label class="wptw-clabel"><?php echo esc_html($cl);?></label>
                                    <div class="wptw-cswatch">
                                        <input type="color" name="<?php echo esc_attr( WPTW_OPTION ); ?>[<?php echo esc_attr( $ck ); ?>]"
                                               value="<?php echo esc_attr($o[$ck]);?>"
                                               data-key="<?php echo esc_attr( $ck ); ?>"
                                               class="wptw-color"
                                               data-default="<?php echo esc_attr( wptw_defaults()[ $ck ] ); ?>">
                                        <span class="wptw-chex"><?php echo esc_html($o[$ck]);?></span>
                                        <button type="button" class="wptw-creset" title="Reset to default" data-key="<?php echo esc_attr( $ck ); ?>" data-default="<?php echo esc_attr( wptw_defaults()[ $ck ] ); ?>">↺</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- ══ TYPOGRAPHY ══ -->
                <section class="wptw-panel" data-panel="typography">
                    <?php wptw_ph('Typography','Font family, sizes, spacing, and border radius for every text element.'); ?>
                    <div class="wptw-fields">
                        <div class="wptw-field">
                            <label class="wptw-label">Font family</label>
                            <select name="<?php echo esc_attr( WPTW_OPTION ); ?>[font_family]" id="wptw-font-family" class="wptw-input">
                                <?php foreach($fonts as $fv=>$fl): ?>
                                <option value="<?php echo esc_attr($fv);?>" <?php selected($o['font_family'],$fv);?>><?php echo esc_html($fl);?></option>
                                <?php endforeach; ?>
                            </select>
                            <span id="wptw-font-preview" style="display:block;margin-top:8px;font-size:13px;color:#555;min-height:18px"></span>
                            <p class="wptw-help">Applies to link text in the list. System default inherits your theme's font. Google Fonts options load from Google's CDN on the frontend.</p>
                        </div>

                        <p class="wptw-subhead">Link list</p>
                        <div class="wptw-twofield">
                            <?php wptw_sf('font_size_link','Link font size (px)',$o['font_size_link'],10,24); ?>
                            <?php wptw_sf('font_size_sub','Sub-heading size (px)',$o['font_size_sub'],10,24); ?>
                        </div>

                        <hr class="wptw-hr">
                        <p class="wptw-subhead">Header bar elements</p>
                        <p class="wptw-help" style="margin-top:-10px">Controls <code>.wptw-toc__label</code> (title), <code>.wptw-toc__rt</code> (reading time), <code>.wptw-toc__num</code> (section numbers)</p>

                        <div class="wptw-twofield">
                            <?php wptw_sf('font_size_label','Title label size (px)',$o['font_size_label'],8,20); ?>
                            <?php wptw_sf('font_size_rt','Reading time size (px)',$o['font_size_rt'],8,20); ?>
                        </div>
                        <?php wptw_sf('font_size_num','Section number size (px)',$o['font_size_num'],8,20); ?>

                        <div class="wptw-field">
                            <label class="wptw-label">Title label letter-spacing</label>
                            <div class="wptw-slrow">
                                <input type="range" class="wptw-range wptw-slsync" data-num="wptw-num-lsp" min="0" max="50" value="<?php echo esc_attr($o['letter_spacing_label']);?>">
                                <output class="wptw-rval"><?php echo esc_html($o['letter_spacing_label']);?></output>
                            </div>
                            <input type="number" id="wptw-num-lsp" name="<?php echo esc_attr( WPTW_OPTION ); ?>[letter_spacing_label]" value="<?php echo esc_attr($o['letter_spacing_label']);?>" min="0" max="50" class="wptw-num" style="margin-top:6px">
                            <p class="wptw-help">In hundredths of em. 13 = 0.13em. Controls tracking on the "Contents" title label.</p>
                        </div>

                        <div class="wptw-field">
                            <label class="wptw-label">Title label text transform</label>
                            <div class="wptw-seg">
                                <?php foreach ( [ 'uppercase' => 'UPPERCASE', 'capitalize' => 'Capitalize', 'none' => 'none' ] as $tv => $tl ) : ?>
                                <label class="wptw-segopt <?php echo $o['text_transform_label'] === $tv ? 'on' : ''; ?>">
                                    <input type="radio" name="<?php echo esc_attr( WPTW_OPTION ); ?>[text_transform_label]" value="<?php echo esc_attr( $tv ); ?>" <?php checked( $o['text_transform_label'], $tv ); ?>>
                                    <?php echo esc_html( $tl ); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <hr class="wptw-hr">
                        <p class="wptw-subhead">Card shape</p>
                        <?php wptw_sf('border_radius','Border radius (px)',$o['border_radius'],0,24); ?>
                    </div>
                </section>

                <!-- ══ ADVANCED ══ -->
                <section class="wptw-panel" data-panel="advanced">
                    <?php wptw_ph('Advanced','Custom CSS and shortcode reference.'); ?>
                    <div class="wptw-fields">
                        <div class="wptw-field">
                            <label class="wptw-label">Custom CSS</label>
                            <textarea name="<?php echo esc_attr( WPTW_OPTION ); ?>[custom_css]" class="wptw-textarea" rows="12" spellcheck="false"><?php echo esc_textarea($o['custom_css']);?></textarea>
                            <p class="wptw-help">Appended after all plugin styles. Selectors: <code>.wptw-toc</code>, <code>.wptw-toc__label</code>, <code>.wptw-toc__rt</code>, <code>.wptw-toc__num</code>, <code>.wptw-toc__link</code>, <code>.wptw-toc__toggle</code></p>
                        </div>
                        <div class="wptw-field">
                            <label class="wptw-label">Shortcode</label>
                            <div class="wptw-codebox"><code>[wptw_toc]</code><button type="button" class="wptw-copybtn" data-copy="[wptw_toc]">Copy</button></div>
                        </div>
                    </div>
                </section>

                <div class="wptw-footer">
                    <?php submit_button('Save settings','primary wptw-savebtn','submit',false); ?>
                    <span id="wptw-saved" class="wptw-saved">✓ Saved</span>
                </div>
                </div>

                <aside class="wptw-preview" aria-label="Live table of contents preview">
                    <div class="wptw-preview__bar">
                        <span>Live preview</span>
                        <button type="button" class="wptw-preview__toggle" id="wptw-preview-toggle">Desktop</button>
                    </div>
                    <div class="wptw-preview__canvas">
                        <div class="wptw-toc wptw-preview-toc wptw-toc--layout-<?php echo esc_attr( array_key_exists( (string) $o['toc_layout'], $layouts ) ? $o['toc_layout'] : $default_layout ); ?>">
                            <div class="wptw-toc__head">
                                <div class="wptw-toc__head-left">
                                    <span class="wptw-toc__label"><?php echo esc_html( $o['toc_title'] ); ?></span>
                                    <span class="wptw-toc__rt">5 min read</span>
                                </div>
                                <button type="button" class="wptw-toc__toggle" aria-expanded="true">
                                    <span class="wptw-toc__tog-text">Hide</span>
                                    <svg class="wptw-toc__tog-icon" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="M2 4.5L6 8.5L10 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>
                            <div class="wptw-toc__prog" role="presentation"><div class="wptw-toc__prog-fill" style="width:42%"></div></div>
                            <div class="wptw-toc__body">
                                <ol class="wptw-toc__list" role="list">
                                    <li class="wptw-toc__item is-done" style="--i:0"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">1.</span><span class="wptw-toc__text">Getting started</span></a></li>
                                    <li class="wptw-toc__item is-done wptw-toc__item--sub wptw-toc__item--d3" style="--i:1"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">1.1.</span><span class="wptw-toc__text">Setup checklist</span></a></li>
                                    <li class="wptw-toc__item is-active" style="--i:2"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">2.</span><span class="wptw-toc__text">Design decisions</span></a></li>
                                    <li class="wptw-toc__item wptw-toc__item--sub wptw-toc__item--d3" style="--i:3"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">2.1.</span><span class="wptw-toc__text">Responsive behavior</span></a></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </aside>
                </div>
            </form>
        </div>
        <footer class="wptw-admin-footer">
            <span>TableWise <?php echo esc_html( WPTW_VERSION ); ?></span>
            <span>Built by <a href="https://cr8vstacks.com" target="_blank" rel="noopener noreferrer">Cr8v Stacks</a></span>
        </footer>
    </div>

    <style>
    /* ─── Admin UI ─────────────────────────────────────────── */
    .wptw-wrap{max-width:1460px;padding-bottom:80px;--a:#111;--b:#2271b1;--bd:#e2e2e2;--bg:#f7f7f7;--r:6px}
    .wptw-ph{display:flex;align-items:center;justify-content:space-between;margin:0 0 22px;padding:16px 18px;position:sticky;top:32px;z-index:30;background:#fff;border:1px solid var(--bd);border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
    .wptw-logo{display:flex;align-items:center;gap:10px}
    .wptw-pname{font-size:20px;font-weight:700;color:#111;display:block;line-height:1.1}
    .wptw-pver{font-size:11px;color:#bbb;font-weight:400}
    .wptw-by{font-size:12px;color:#999;text-decoration:none;border:1px solid var(--bd);border-radius:20px;padding:4px 12px;transition:.15s all}
    .wptw-by:hover{color:#111;border-color:#111}
    .wptw-layout{display:flex;background:#fff;border:1px solid var(--bd);border-radius:var(--r);box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden}
    /* tabs */
    .wptw-tabs{display:flex;flex-direction:column;min-width:164px;background:var(--bg);border-right:1px solid var(--bd);padding:8px 0;flex-shrink:0}
    .wptw-tab{display:flex;align-items:center;gap:9px;padding:10px 16px;background:none;border:none;border-left:3px solid transparent;cursor:pointer;font-size:13px;color:#666;text-align:left;transition:.14s all;line-height:1.3;width:100%}
    .wptw-tab svg{flex-shrink:0;opacity:.65}
    .wptw-tab:hover{background:#ebebeb;color:#333}
    .wptw-tab[aria-selected="true"]{border-left-color:var(--a);background:#fff;color:var(--a);font-weight:600}
    .wptw-tab[aria-selected="true"] svg{opacity:1}
    /* panels */
    .wptw-panel{display:none;flex:1;padding:26px 30px;min-width:0}
    .wptw-panel.is-active{display:block}
    .wptw-panel-header{margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #eee}
    .wptw-panel-header h2{font-size:15px;font-weight:700;margin:0 0 3px;color:#111}
    .wptw-panel-header p{color:#888;font-size:12.5px;margin:0}
    .wptw-panel-shortcuts{display:flex;justify-content:flex-end;margin:-8px 0 16px}
    .wptw-jump{align-items:center;background:#fff;border:1px solid #dcdcde;border-radius:5px;color:#1d2327;cursor:pointer;display:inline-flex;font-size:12px;font-weight:600;gap:6px;padding:7px 10px}
    .wptw-jump:hover{border-color:#111;color:#111}
    /* fields */
    .wptw-fields{display:flex;flex-direction:column;gap:18px}
    .wptw-field{display:flex;flex-direction:column;gap:0}
    .wptw-twofield{display:grid;grid-template-columns:1fr 1fr;gap:12px 24px}
    .wptw-label{font-size:12.5px;font-weight:600;color:#333;margin-bottom:6px;display:block}
    .wptw-clabel{font-size:11.5px;font-weight:500;color:#666;margin-bottom:5px;display:block}
    .wptw-help{font-size:11.5px;color:#999;margin:5px 0 0;line-height:1.5}
    .wptw-help code{background:#f2f2f2;padding:1px 4px;border-radius:3px;font-size:10.5px}
    .wptw-help strong{font-weight:600;color:#555;background:none}
    .wptw-subhead{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#bbb;margin:2px 0 10px}
    .wptw-input{width:100%;max-width:400px;padding:7px 10px;border:1px solid #ddd;border-radius:4px;font-size:13px;box-sizing:border-box}
    .wptw-input:focus{outline:none;border-color:var(--b);box-shadow:0 0 0 2px rgba(34,113,177,.15)}
    .wptw-num{width:80px;padding:6px 8px;border:1px solid #ddd;border-radius:4px;font-size:13px}
    .wptw-num:focus{outline:none;border-color:var(--b);box-shadow:0 0 0 2px rgba(34,113,177,.15)}
    .wptw-textarea{width:100%;max-width:560px;font-family:'Fira Mono','Courier New',monospace;font-size:12px;padding:10px;border:1px solid #ddd;border-radius:4px;resize:vertical;box-sizing:border-box}
    .wptw-hr{border:none;border-top:1px solid #eee;margin:4px 0}
    /* checkgroup */
    .wptw-checkgroup{display:flex;flex-wrap:wrap;gap:10px 18px}
    .wptw-check{display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer}
    .wptw-radio-group{display:flex;flex-direction:column;gap:8px}
    .wptw-radio{display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer}
    /* segmented */
    .wptw-seg{display:inline-flex;border:1px solid #ddd;border-radius:5px;overflow:hidden;max-width:fit-content;width:fit-content}
    .wptw-segopt{display:flex;align-items:center;gap:6px;padding:7px 16px;cursor:pointer;font-size:12.5px;color:#666;background:#fafafa;transition:.14s;user-select:none}
    .wptw-segopt:not(:last-child){border-right:1px solid #ddd}
    .wptw-segopt input[type="radio"]{display:none}
    .wptw-segopt.on,.wptw-segopt:has(input:checked){background:var(--a);color:#fff}
    /* toggle switch */
    .wptw-togfield{padding:2px 0}
    .wptw-togrow{display:flex;align-items:flex-start;gap:12px}
    .wptw-sw{position:relative;flex-shrink:0;display:inline-block;width:38px;height:22px;margin-top:2px}
    .wptw-sw input[type="hidden"]{display:none}
    .wptw-sw input[type="checkbox"]{opacity:0;width:0;height:0;position:absolute}
    .wptw-swknob{position:absolute;inset:0;background:#ccc;border-radius:22px;cursor:pointer;transition:.2s}
    .wptw-swknob::after{content:'';position:absolute;left:3px;top:3px;width:16px;height:16px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
    .wptw-sw input:checked+.wptw-swknob{background:var(--a)}
    .wptw-sw input:checked+.wptw-swknob::after{transform:translateX(16px)}
    .wptw-swlabel{font-size:13px;font-weight:600;color:#333;display:block;margin-bottom:2px}
    /* heading picker */
    .wptw-hpicker{display:flex;gap:8px}
    .wptw-hpick{display:flex;align-items:center;justify-content:center;width:44px;height:36px;border:1px solid #ddd;border-radius:4px;cursor:pointer;font-size:12px;font-weight:700;color:#666;background:#fafafa;transition:.15s;user-select:none}
    .wptw-hpick.on{background:var(--a);color:#fff;border-color:var(--a)}
    .wptw-hpick input{display:none}
    /* affix */
    .wptw-affixwrap{display:flex;align-items:center;max-width:260px;border:1px solid #ddd;border-radius:4px;overflow:hidden}
    .wptw-affix{padding:7px 10px;background:#f5f5f5;color:#888;font-size:12px;font-family:monospace}
    .wptw-affixr{border-left:1px solid #ddd}
    .wptw-affixinput{border:none;padding:7px 10px;font-size:13px;flex:1;outline:none;min-width:0}
    /* colour controls */
    .wptw-cgroup{margin-bottom:6px}
    .wptw-cglabel{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#bbb;margin:0 0 10px;padding-top:6px;border-top:1px solid #f2f2f2}
    .wptw-crow{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px 20px}
    .wptw-cfield{display:flex;flex-direction:column}
    .wptw-cswatch{display:flex;align-items:center;gap:6px}
    /* native colour input styled */
    .wptw-color{-webkit-appearance:none;width:36px;height:28px;border:1px solid #ddd;border-radius:4px;padding:2px;cursor:pointer;flex-shrink:0;background:#fff}
    .wptw-color::-webkit-color-swatch-wrapper{padding:0;border-radius:2px}
    .wptw-color::-webkit-color-swatch{border:none;border-radius:2px}
    .wptw-color::-moz-color-swatch{border:none;border-radius:2px}
    .wptw-chex{font-size:11px;font-family:monospace;color:#666;flex:1;user-select:all}
    .wptw-creset{background:none;border:none;cursor:pointer;color:#bbb;font-size:14px;padding:0 2px;line-height:1;transition:.15s}
    .wptw-creset:hover{color:#c00}
    /* presets */
    .wptw-presets{display:flex;flex-wrap:wrap;gap:8px}
    .wptw-pbtn{align-items:center;display:inline-flex;gap:7px;padding:6px 12px;background:#f5f5f5;border:1px solid #ddd;border-radius:20px;cursor:pointer;font-size:12px;transition:.14s;font-family:inherit}
    .wptw-pbtn:hover{background:#111;color:#fff;border-color:#111}
    .wptw-pbtn.is-preview{border-color:#111;box-shadow:0 0 0 1px #111}
    .wptw-pbtn.is-saved-active{background:#ecfdf5;border-color:#047857;color:#065f46}
    .wptw-reset:hover{background:#f5f5f5;color:#333;border-color:#bbb}
    /* sliders */
    .wptw-slrow{display:flex;align-items:center;gap:14px}
    .wptw-range{flex:1;max-width:280px;accent-color:var(--a);cursor:pointer}
    .wptw-rval{font-size:12px;color:#666;font-family:monospace;min-width:34px}
    .wptw-stickyex{padding-left:50px}
    /* shortcode */
    .wptw-codebox{display:flex;align-items:center;gap:10px;background:#f5f5f5;border:1px solid #ddd;border-radius:4px;padding:10px 14px;max-width:280px}
    .wptw-codebox code{font-size:14px;flex:1}
    .wptw-copybtn{padding:4px 12px;background:#111;color:#fff;border:none;border-radius:3px;cursor:pointer;font-size:11px;font-family:inherit;transition:opacity .15s}
    .wptw-copybtn:hover{opacity:.75}
    /* footer */
    .wptw-footer{margin-top:28px;padding:18px 30px;border-top:1px solid #eee;display:flex;align-items:center;gap:16px}
    .wptw-savebtn{font-size:13.5px!important;padding:8px 24px!important;height:auto!important}
    .wptw-saved{color:#2e7d32;font-size:13px;font-weight:600;opacity:0;transition:opacity .3s}
    .wptw-saved.on{opacity:1}
    .wptw-wrap{max-width:1460px}
    .wptw-layout{align-items:stretch}
    #wptw-form{flex:1;min-width:0}
    .wptw-workbench{display:grid;grid-template-columns:minmax(0,1fr) 520px;min-height:680px}
    .wptw-editor{min-width:0;border-right:1px solid var(--bd)}
    .wptw-panel{padding:28px 32px}
    .wptw-preview{background:#f6f7f7;padding:22px;position:sticky;top:40px;align-self:start;min-height:100%}
    .wptw-preview__bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#6b7280}
    .wptw-preview__toggle{border:1px solid #dcdcde;background:#fff;border-radius:4px;color:#1d2327;cursor:pointer;font-size:11px;padding:4px 8px}
    .wptw-preview__canvas{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:28px;box-shadow:inset 0 0 0 1px rgba(255,255,255,.5);overflow:visible}
    .wptw-preview__canvas.is-mobile{max-width:285px;margin:0 auto;padding:16px}
    .wptw-layout-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px}
    .wptw-layout-card{display:flex;flex-direction:column;gap:8px;border:1px solid #dcdcde;border-radius:8px;padding:14px;background:#fff;cursor:pointer;position:relative;transition:.15s border-color,.15s box-shadow,.15s transform}
    .wptw-layout-card:hover{border-color:#111;transform:translateY(-1px)}
    .wptw-layout-card.on,.wptw-layout-card:has(input:checked){border-color:#111;box-shadow:0 0 0 2px #111}
    .wptw-layout-card input{position:absolute;opacity:0;pointer-events:none}
    .wptw-layout-card strong{font-size:13px;color:#111}
    .wptw-layout-card small{font-size:11.5px;line-height:1.45;color:#777}
    .wptw-card-badges{display:flex;gap:5px;left:10px;position:absolute;top:10px;z-index:3}
    .wptw-badge{border-radius:999px;display:none;font-size:9px;font-weight:800;letter-spacing:.05em;line-height:1;padding:4px 6px;text-transform:uppercase}
    .wptw-badge--active{background:#047857;color:#ecfdf5}
    .wptw-layout-card.is-saved-active .wptw-badge--active,.wptw-pbtn.is-saved-active .wptw-badge--active{display:inline-flex}
    .wptw-layout-mini{height:82px;border:1px solid #eee;border-radius:6px;background:#fafafa;padding:11px;display:flex;flex-direction:column;gap:7px;position:relative;overflow:hidden}
    .wptw-layout-mini span{display:block;height:8px;background:#111;border-radius:3px;opacity:.8}
    .wptw-layout-mini span:first-child{width:70%;height:12px}
    .wptw-layout-mini span:nth-child(2){width:88%;opacity:.42}
    .wptw-layout-mini span:nth-child(3){width:58%;opacity:.28}
    .wptw-layout-mini--manuscript{padding-left:22px;background:#111}
    .wptw-layout-mini--manuscript::before{content:'';position:absolute;left:12px;top:0;right:0;height:3px;background:#d97706}
    .wptw-layout-mini--manuscript::after{content:'';position:absolute;left:16px;top:18px;bottom:12px;border-left:1px solid rgba(255,255,255,.24)}
    .wptw-layout-mini--manuscript span{background:#fff}
    .wptw-layout-mini--default{background:#fafafa}
    .wptw-layout-mini--default span:first-child{width:62%;height:12px}
    .wptw-layout-mini--default span{background:#111}
    .wptw-layout-mini--editorial{padding-left:42px;background:#fff}
    .wptw-layout-mini--editorial::before{content:'';position:absolute;left:14px;top:17px;width:20px;height:20px;border-radius:8px;background:#ccfbf1}
    .wptw-layout-mini--editorial::after{content:'';position:absolute;left:24px;top:43px;bottom:12px;border-left:1px solid #d7e4df}
    .wptw-layout-mini--brutalist{background:#18181b;border:2px solid #050505;border-radius:0;box-shadow:none;overflow:visible}
    .wptw-layout-mini--brutalist::after{content:'';position:absolute;inset:5px -6px -6px 5px;border:2px solid #111;background:transparent;z-index:0}
    .wptw-layout-mini--brutalist span{position:relative;z-index:1}
    .wptw-layout-mini--brutalist span{border-radius:0;background:#f8fafc}
    .wptw-layout-mini--brutalist span:first-child{height:14px;width:100%}
    .wptw-admin-footer{align-items:center;color:#7a7a7a;display:flex;font-size:12px;justify-content:space-between;margin:18px 2px 0}
    .wptw-admin-footer a{color:#555;text-decoration:none}.wptw-admin-footer a:hover{color:#111}
    @media (max-width:1100px){.wptw-workbench{grid-template-columns:1fr}.wptw-editor{border-right:0}.wptw-preview{position:relative;top:auto;border-top:1px solid var(--bd)}}@media (max-width:780px){.wptw-layout{display:block}.wptw-tabs{flex-direction:row;overflow:auto;border-right:0;border-bottom:1px solid var(--bd)}.wptw-tab{border-left:0;border-bottom:3px solid transparent;min-width:max-content}.wptw-tab[aria-selected="true"]{border-bottom-color:#111}.wptw-twofield{grid-template-columns:1fr}.wptw-panel{padding:22px}.wptw-stickyex{padding-left:0}}
    </style>
    <?php
    if ( function_exists( 'wptw_render_toc_styles' ) ) {
        wptw_render_toc_styles( $o, false, 'wptw-admin-preview-frontend-styles' );
    }
    ?>
    <style id="wptw-admin-preview-frame">
    .wptw-preview .wptw-preview-toc{margin:0!important;width:100%!important;max-width:none!important}
    .wptw-preview__canvas .wptw-preview-toc{position:relative}
    </style>
    <?php
}

function wptw_ph( string $t, string $d ): void {
    echo '<div class="wptw-panel-header"><h2>' . esc_html($t) . '</h2><p>' . esc_html($d) . '</p></div>';
}

function wptw_sf( string $key, string $label, $val, int $min, int $max ): void {
    $id = 'wptw-num-' . $key;
    echo '<div class="wptw-field">';
    echo '<label class="wptw-label">' . esc_html( $label ) . '</label>';
    echo '<div class="wptw-slrow">';
    echo '<input type="range" class="wptw-range wptw-slsync" data-num="' . esc_attr( $id ) . '" min="' . (int) $min . '" max="' . (int) $max . '" value="' . esc_attr( $val ) . '">';
    echo '<output class="wptw-rval">' . esc_html( $val ) . '</output>';
    echo '</div>';
    echo '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( WPTW_OPTION ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" min="' . (int) $min . '" max="' . (int) $max . '" class="wptw-num" style="margin-top:6px">';
    echo '</div>';
}

/* ── Admin JS — pure vanilla, zero WP JS dependencies ──────── */
function wptw_admin_js() {
    $presets_json  = wp_json_encode( array_map( fn($p) => $p['colors'], wptw_color_presets() ) );
    $def_colors    = array_filter( wptw_defaults(), static function ( $k ) {
        return strpos( (string) $k, 'color_' ) === 0;
    }, ARRAY_FILTER_USE_KEY );
    $defaults_json = wp_json_encode( $def_colors );
    $default_layout = wptw_defaults()['toc_layout'];
    ?>
    <script>
    /* WP TableWise settings — pure vanilla JS, no jQuery dependencies */
    (function(){
        'use strict';

        var presets  = <?php echo $presets_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
        var defClrs  = <?php echo $defaults_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
        var defaultLayout = <?php echo wp_json_encode( $default_layout ); ?>;
        var layoutPresetColors = {
            manuscript: {
                default: {
                    color_bg:'#0f172a', color_border:'#243044', color_header_bg:'#0b1120',
                    color_label:'#d97706', color_rt:'#94a3b8', color_rt_bar:'#d97706', color_rt_bar_bg:'#243044',
                    color_toggle_bg:'#f8fafc', color_toggle_fg:'#0f172a', color_toggle_border:'#f8fafc',
                    color_link:'#cbd5e1', color_link_hover:'#ffffff', color_active_bar:'#d97706',
                    color_active_bg:'#1e293b', color_number:'#d97706', color_back_top_bg:'#f8fafc', color_back_top_fg:'#0f172a'
                },
                light: {
                    color_bg:'#ffffff', color_border:'#e5e7eb', color_header_bg:'#f8fafc',
                    color_label:'#9a3412', color_rt:'#64748b', color_rt_bar:'#d97706', color_rt_bar_bg:'#e5e7eb',
                    color_toggle_bg:'#111827', color_toggle_fg:'#ffffff', color_toggle_border:'#111827',
                    color_link:'#334155', color_link_hover:'#0f172a', color_active_bar:'#d97706',
                    color_active_bg:'#fff7ed', color_number:'#d97706', color_back_top_bg:'#111827', color_back_top_fg:'#ffffff'
                },
                dark: {
                    color_bg:'#0f172a', color_border:'#243044', color_header_bg:'#0b1120',
                    color_label:'#d97706', color_rt:'#94a3b8', color_rt_bar:'#d97706', color_rt_bar_bg:'#243044',
                    color_toggle_bg:'#f8fafc', color_toggle_fg:'#0f172a', color_toggle_border:'#f8fafc',
                    color_link:'#cbd5e1', color_link_hover:'#ffffff', color_active_bar:'#d97706',
                    color_active_bg:'#1e293b', color_number:'#d97706', color_back_top_bg:'#f8fafc', color_back_top_fg:'#0f172a'
                }
            },
            editorial: {
                default: {
                    color_bg:'#ffffff', color_border:'#d1d5db', color_header_bg:'#f8fafc',
                    color_label:'#111827', color_rt:'#64748b', color_rt_bar:'#111827', color_rt_bar_bg:'#e5e7eb',
                    color_toggle_bg:'#111827', color_toggle_fg:'#ffffff', color_toggle_border:'#111827',
                    color_link:'#374151', color_link_hover:'#111827', color_active_bar:'#111827',
                    color_active_bg:'#f3f4f6', color_number:'#94a3b8', color_back_top_bg:'#111827', color_back_top_fg:'#ffffff'
                },
                light: {
                    color_bg:'#ffffff', color_border:'#e5e7eb', color_header_bg:'#f9fafb',
                    color_label:'#111827', color_rt:'#6b7280', color_rt_bar:'#111827', color_rt_bar_bg:'#e5e7eb',
                    color_toggle_bg:'#111827', color_toggle_fg:'#ffffff', color_toggle_border:'#111827',
                    color_link:'#374151', color_link_hover:'#111827', color_active_bar:'#111827',
                    color_active_bg:'#f3f4f6', color_number:'#9ca3af', color_back_top_bg:'#111827', color_back_top_fg:'#ffffff'
                },
                dark: {
                    color_bg:'#111827', color_border:'#374151', color_header_bg:'#030712',
                    color_label:'#f9fafb', color_rt:'#9ca3af', color_rt_bar:'#f9fafb', color_rt_bar_bg:'#374151',
                    color_toggle_bg:'#f9fafb', color_toggle_fg:'#111827', color_toggle_border:'#f9fafb',
                    color_link:'#e5e7eb', color_link_hover:'#ffffff', color_active_bar:'#f9fafb',
                    color_active_bg:'#1f2937', color_number:'#9ca3af', color_back_top_bg:'#f9fafb', color_back_top_fg:'#111827'
                }
            },
            brutalist: {
                default: {
                    color_bg:'#18181b', color_border:'#0a0a0a', color_header_bg:'#050505',
                    color_label:'#f8fafc', color_rt:'#a1a1aa', color_rt_bar:'#f8fafc', color_rt_bar_bg:'#3f3f46',
                    color_toggle_bg:'#f8fafc', color_toggle_fg:'#0a0a0a', color_toggle_border:'#f8fafc',
                    color_link:'#e4e4e7', color_link_hover:'#ffffff', color_active_bar:'#f8fafc',
                    color_active_bg:'#27272a', color_number:'#a1a1aa', color_back_top_bg:'#f8fafc', color_back_top_fg:'#0a0a0a'
                },
                light: {
                    color_bg:'#ffffff', color_border:'#111111', color_header_bg:'#f4f4f5',
                    color_label:'#111111', color_rt:'#52525b', color_rt_bar:'#111111', color_rt_bar_bg:'#d4d4d8',
                    color_toggle_bg:'#111111', color_toggle_fg:'#ffffff', color_toggle_border:'#111111',
                    color_link:'#27272a', color_link_hover:'#000000', color_active_bar:'#111111',
                    color_active_bg:'#f4f4f5', color_number:'#71717a', color_back_top_bg:'#111111', color_back_top_fg:'#ffffff'
                },
                dark: {
                    color_bg:'#09090b', color_border:'#000000', color_header_bg:'#000000',
                    color_label:'#ffffff', color_rt:'#a1a1aa', color_rt_bar:'#ffffff', color_rt_bar_bg:'#27272a',
                    color_toggle_bg:'#ffffff', color_toggle_fg:'#000000', color_toggle_border:'#ffffff',
                    color_link:'#f4f4f5', color_link_hover:'#ffffff', color_active_bar:'#ffffff',
                    color_active_bg:'#27272a', color_number:'#d4d4d8', color_back_top_bg:'#ffffff', color_back_top_fg:'#000000'
                }
            },
            default: {
                default: {
                    color_bg:'#ffffff', color_border:'#e8e8e8', color_header_bg:'#fafafa',
                    color_label:'#666666', color_rt:'#737373', color_rt_bar:'#111111', color_rt_bar_bg:'#e8e8e8',
                    color_toggle_bg:'#111111', color_toggle_fg:'#ffffff', color_toggle_border:'#111111',
                    color_link:'#333333', color_link_hover:'#000000', color_active_bar:'#111111',
                    color_active_bg:'#f4f4f4', color_number:'#737373', color_back_top_bg:'#111111', color_back_top_fg:'#ffffff'
                },
                light: {
                    color_bg:'#ffffff', color_border:'#e5e7eb', color_header_bg:'#f3f4f6',
                    color_label:'#4b5563', color_rt:'#6b7280', color_rt_bar:'#111827', color_rt_bar_bg:'#d1d5db',
                    color_toggle_bg:'#111827', color_toggle_fg:'#ffffff', color_toggle_border:'#111827',
                    color_link:'#374151', color_link_hover:'#111827', color_active_bar:'#111827',
                    color_active_bg:'#f9fafb', color_number:'#6b7280', color_back_top_bg:'#111827', color_back_top_fg:'#ffffff'
                },
                dark: {
                    color_bg:'#1a1a1a', color_border:'#3a3a3a', color_header_bg:'#111111',
                    color_label:'#e5e5e5', color_rt:'#a3a3a3', color_rt_bar:'#e5e5e5', color_rt_bar_bg:'#3a3a3a',
                    color_toggle_bg:'#e5e5e5', color_toggle_fg:'#111111', color_toggle_border:'#e5e5e5',
                    color_link:'#d4d4d4', color_link_hover:'#ffffff', color_active_bar:'#e5e5e5',
                    color_active_bg:'#2a2a2a', color_number:'#a3a3a3', color_back_top_bg:'#e5e5e5', color_back_top_fg:'#111111'
                }
            }
        };
        presets['__reset'] = defClrs;
        var currentPreset = 'default';
        var lastPreset = 'default';
        var savedPreset = 'default';

        /* ── TABS ── */
        var tabs   = document.querySelectorAll('.wptw-tab');
        var panels = document.querySelectorAll('.wptw-panel');
        function activateTab(id){
            tabs.forEach(function(t){ t.setAttribute('aria-selected','false'); });
            panels.forEach(function(p){ p.classList.remove('is-active'); });
            var tab = document.querySelector('.wptw-tab[data-tab="'+id+'"]');
            var pnl = document.querySelector('.wptw-panel[data-panel="'+id+'"]');
            if(tab) tab.setAttribute('aria-selected','true');
            if(pnl) pnl.classList.add('is-active');
            try{ localStorage.setItem('wptw_tab',id); }catch(e){}
        }
        tabs.forEach(function(t){
            t.addEventListener('click', function(){ activateTab(this.dataset.tab); });
        });
        document.querySelectorAll('[data-jump-tab]').forEach(function(btn){
            btn.addEventListener('click', function(){ activateTab(this.dataset.jumpTab); });
        });
        var init; try{ init=localStorage.getItem('wptw_tab'); }catch(e){}
        activateTab(init||'visibility');

        /* ── Segmented radio — keep .on class in sync ── */
        document.querySelectorAll('.wptw-seg').forEach(function(seg){
            seg.querySelectorAll('input[type="radio"]').forEach(function(r){
                r.addEventListener('change', function(){
                    seg.querySelectorAll('.wptw-segopt').forEach(function(o){ o.classList.remove('on'); });
                    if(r.checked) r.closest('.wptw-segopt').classList.add('on');
                });
            });
        });

        /* ── Heading picker visual toggle ── */
        document.querySelectorAll('.wptw-hpick input').forEach(function(cb){
            cb.addEventListener('change', function(){
                cb.closest('.wptw-hpick').classList.toggle('on', cb.checked);
            });
        });

        /* ── Sliders synced to number inputs ── */
        document.querySelectorAll('.wptw-slsync').forEach(function(sl){
            var numId = sl.dataset.num;
            var out   = sl.nextElementSibling; // <output>
            sl.addEventListener('input', function(){
                if(out) out.textContent = sl.value;
                if(numId){ var n=document.getElementById(numId); if(n) n.value=sl.value; }
            });
        });

        /* ── Sticky offset slider ↔ number ── */
        var sRange = document.getElementById('wptw-sticky-range');
        var sOut   = document.getElementById('wptw-sticky-out');
        var sNum   = document.getElementById('wptw-sticky-num');
        if(sRange && sNum){
            sRange.addEventListener('input', function(){ sNum.value=sRange.value; if(sOut) sOut.textContent=sRange.value+'px'; });
            sNum.addEventListener('input', function(){ sRange.value=sNum.value; if(sOut) sOut.textContent=sNum.value+'px'; });
        }

        /* ── Sticky sub-field dim ── */
        var sToggle = document.getElementById('wptw-sticky-toggle');
        var sSub    = document.getElementById('wptw-sticky-sub');
        function dimSticky(){ if(sSub) sSub.style.opacity = (sToggle&&sToggle.checked)?'1':'0.4'; }
        if(sToggle){ sToggle.addEventListener('change', dimSticky); dimSticky(); }

        /* ── Native colour inputs: show hex text + sync ── */
        document.querySelectorAll('.wptw-color').forEach(function(inp){
            var hex = inp.nextElementSibling; // .wptw-chex
            inp.addEventListener('input', function(){ if(hex) hex.textContent = inp.value; });
        });

        /* ── Per-colour reset buttons ── */
        document.querySelectorAll('.wptw-creset').forEach(function(btn){
            btn.addEventListener('click', function(){
                var key = btn.dataset.key;
                var presetId = currentPreset && currentPreset !== 'custom' ? currentPreset : lastPreset || 'default';
                var preset = presetId ? resolvedPreset(presetId) : null;
                var def = preset && preset[key] ? preset[key] : btn.dataset.default;
                var inp = document.querySelector('input.wptw-color[data-key="'+key+'"]');
                if(inp){
                    inp.value = def;
                    var hex = inp.nextElementSibling;
                    if(hex) hex.textContent = def;
                }
                if(typeof updatePreview === 'function') updatePreview();
            });
        });

        /* ── Colour presets ── */
        document.querySelectorAll('.wptw-pbtn').forEach(function(btn){
            btn.addEventListener('click', function(){
                if(btn.dataset.preset !== '__reset'){
                    currentPreset = btn.dataset.preset;
                    lastPreset = currentPreset;
                } else {
                    currentPreset = currentPreset && currentPreset !== 'custom' ? currentPreset : lastPreset || 'default';
                    lastPreset = currentPreset;
                }
                var p = resolvedPreset(btn.dataset.preset);
                if(!p) return;
                applyColors(p);
                if(typeof updatePreview === 'function') updatePreview();
            });
        });

        /* ── Font preview ── */
        var fontSel = document.getElementById('wptw-font-family');
        var fontPrv = document.getElementById('wptw-font-preview');
        function showFontPreview(font){
            if(!fontPrv) return;
            if(!font||font==='system'){ fontPrv.textContent=''; fontPrv.style.fontFamily=''; return; }
            var lid='wptw-gfont-link';
            var old=document.getElementById(lid); if(old) old.remove();
            var lk=document.createElement('link');
            lk.id=lid; lk.rel='stylesheet';
            lk.href='https://fonts.googleapis.com/css2?family='+encodeURIComponent(font)+':wght@400;500&display=swap';
            document.head.appendChild(lk);
            fontPrv.style.fontFamily="'"+font+"',sans-serif";
            fontPrv.textContent='The quick brown fox — Aa Bb Cc 0123456789';
        }
        if(fontSel){ fontSel.addEventListener('change', function(){ showFontPreview(this.value); }); showFontPreview(fontSel.value); }

        /* Live preview */
        var form = document.getElementById('wptw-form');
        var preview = document.querySelector('.wptw-preview-toc');
        var previewCanvas = document.querySelector('.wptw-preview__canvas');
        var previewMode = document.getElementById('wptw-preview-toggle');
        var optionName = <?php echo wp_json_encode( WPTW_OPTION ); ?>;
        var cssMap = {
            color_bg:'--wptw-bg', color_border:'--wptw-border', color_header_bg:'--wptw-head-bg',
            color_label:'--wptw-label-c', color_rt:'--wptw-rt-c', color_rt_bar:'--wptw-rtbar-fill',
            color_rt_bar_bg:'--wptw-rtbar-bg', color_toggle_bg:'--wptw-tog-bg', color_toggle_fg:'--wptw-tog-fg',
            color_toggle_border:'--wptw-tog-bdr', color_link:'--wptw-link', color_link_hover:'--wptw-link-hov',
            color_active_bar:'--wptw-bar', color_active_bg:'--wptw-act-bg', color_number:'--wptw-num-c'
        };
        function field(key){ return form ? form.querySelector('[name="'+optionName+'['+key+']"]:not([type="hidden"])') : null; }
        function checked(key){ var el = field(key); return !!(el && el.checked); }
        function value(key, fallback){
            var checkedRadio = form ? form.querySelector('[name="'+optionName+'['+key+']"][type="radio"]:checked') : null;
            if(checkedRadio) return checkedRadio.value;
            var el = field(key);
            return el ? el.value : fallback;
        }
        function activeLayout(){
            return value('toc_layout', defaultLayout || 'manuscript') || 'manuscript';
        }
        function resolvedPreset(presetId){
            var id = presetId === '__reset' ? (currentPreset && currentPreset !== 'custom' ? currentPreset : lastPreset || 'default') : presetId;
            var layout = activeLayout();
            var layoutPresets = layoutPresetColors[layout] || layoutPresetColors.default || {};
            if(id === 'default') return layoutPresets.default || presets.default || defClrs;
            if(id === 'dark' && layoutPresets.dark) return layoutPresets.dark;
            return presets[id] || layoutPresets.default || presets.default || defClrs;
        }
        function colorsMatch(colors){
            return Object.keys(cssMap).every(function(key){
                var el = document.querySelector('input.wptw-color[data-key="'+key+'"]');
                return el && colors && colors[key] && el.value.toLowerCase() === colors[key].toLowerCase();
            });
        }
        function inferSavedPreset(){
            var ids = ['default','light','dark','ocean','forest','rose'];
            for(var i = 0; i < ids.length; i++){
                if(colorsMatch(resolvedPreset(ids[i]))) return ids[i];
            }
            return 'default';
        }
        function fontStack(font){
            if(!font) return 'inherit';
            if(font === 'system') return "system-ui,-apple-system,'Helvetica Neue',Arial,sans-serif";
            return "'" + font.replace(/'/g,'') + "',system-ui,sans-serif";
        }
        function applyColors(colors){
            Object.keys(colors || {}).forEach(function(key){
                var inp = document.querySelector('input.wptw-color[data-key="'+key+'"]');
                if(!inp) return;
                inp.value = colors[key];
                var hex = inp.nextElementSibling;
                if(hex) hex.textContent = colors[key];
            });
        }
        function rgb(hex){
            hex = String(hex || '').replace('#','').trim();
            if(hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
            if(!/^[0-9a-f]{6}$/i.test(hex)) hex = 'ffffff';
            return [parseInt(hex.slice(0,2),16), parseInt(hex.slice(2,4),16), parseInt(hex.slice(4,6),16)];
        }
        function lum(hex){
            return rgb(hex).map(function(ch){
                var v = ch / 255;
                return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
            }).reduce(function(sum, v, i){ return sum + v * [0.2126,0.7152,0.0722][i]; }, 0);
        }
        function contrast(a,b){
            var l1 = lum(a) + 0.05, l2 = lum(b) + 0.05;
            return Math.max(l1,l2) / Math.min(l1,l2);
        }
        function blend(fg,bg,amt){
            var f = rgb(fg), b = rgb(bg);
            return '#' + f.map(function(v,i){
                var n = Math.round((v * amt) + (b[i] * (1 - amt))).toString(16);
                return n.length === 1 ? '0' + n : n;
            }).join('');
        }
        function primaryOn(bg){ return lum(bg) < 0.5 ? '#ffffff' : '#0f172a'; }
        function secondaryOn(bg){ return blend(primaryOn(bg), bg, 0.66); }
        function normalizedColors(){
            var c = {};
            Object.keys(cssMap).forEach(function(key){ c[key] = value(key, ''); });
            var bg = c.color_bg || '#ffffff', head = c.color_header_bg || '#fafaf9';
            if(Math.abs(lum(bg) - lum(head)) < 0.06){
                head = lum(bg) < 0.5 ? blend('#ffffff', bg, 0.10) : blend('#0f172a', bg, 0.06);
                c.color_header_bg = head;
            }
            if(contrast(c.color_label, head) < 3) c.color_label = secondaryOn(head);
            if(contrast(c.color_rt, head) < 3) c.color_rt = secondaryOn(head);
            if(contrast(c.color_link, bg) < 4.5) c.color_link = primaryOn(bg);
            if(contrast(c.color_link_hover, bg) < 5) c.color_link_hover = primaryOn(bg);
            if(contrast(c.color_number, bg) < 2.3) c.color_number = blend(primaryOn(bg), bg, 0.48);
            if(Math.abs(lum(c.color_active_bg) - lum(bg)) < 0.04){
                c.color_active_bg = lum(bg) < 0.5 ? blend('#ffffff', bg, 0.09) : blend('#0f172a', bg, 0.05);
            }
            if(Math.min(contrast(c.color_active_bar, bg), contrast(c.color_active_bar, head)) < 2.4){
                c.color_active_bar = activeLayout() === 'brutalist'
                    ? (lum(bg) < 0.5 ? '#ffffff' : '#111827')
                    : (lum(bg) < 0.5 ? '#d97706' : '#111827');
            }
            if(contrast(c.color_rt_bar, bg) < 2.4){
                c.color_rt_bar = c.color_active_bar;
            }
            if(contrast(c.color_toggle_fg, c.color_toggle_bg) < 4.5) c.color_toggle_fg = primaryOn(c.color_toggle_bg);
            return c;
        }
        function updateLayoutCards(){
            document.querySelectorAll('.wptw-layout-card').forEach(function(card){
                var input = card.querySelector('input');
                card.classList.toggle('on', !!(input && input.checked));
            });
        }
        function updatePresetButtons(){
            document.querySelectorAll('.wptw-pbtn[data-preset]').forEach(function(btn){
                var previewId = currentPreset && currentPreset !== 'custom' ? currentPreset : lastPreset || 'default';
                btn.classList.toggle('is-saved-active', btn.dataset.preset !== '__reset' && btn.dataset.preset === savedPreset);
                btn.classList.toggle('is-preview', btn.dataset.preset !== '__reset' && btn.dataset.preset === previewId);
            });
        }
        var previewLayout = '';
        function previewToggle(){
            return '<button type="button" class="wptw-toc__toggle" aria-expanded="true"><span class="wptw-toc__tog-text">Hide</span><svg class="wptw-toc__tog-icon" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="M2 4.5L6 8.5L10 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
        }
        function renderPreviewMarkup(layout){
            if(!preview || previewLayout === layout) return;
            previewLayout = layout;
            if(layout === 'manuscript'){
                preview.innerHTML =
                    '<div class="toc-manuscript-eyebrow"><span class="wptw-toc__label"></span><span class="toc-ms-actions"><span class="wptw-toc__rt">5 min read</span>'+previewToggle()+'</span></div>'+
                    '<div class="wptw-toc__body"><ol class="wptw-toc__list toc-manuscript-list" role="list">'+
                    '<li class="wptw-toc__item is-done toc-ms-item"><span class="toc-ms-node"><span class="toc-ms-node-inner"></span></span><span class="toc-ms-content"><span class="wptw-toc__num toc-ms-roman">I</span><a class="wptw-toc__link toc-ms-main" href="#"><span class="wptw-toc__text toc-ms-title">Preface</span></a></span></li>'+
                    '<li class="wptw-toc__item is-done toc-ms-item"><span class="toc-ms-node"><span class="toc-ms-node-inner"></span></span><span class="toc-ms-content"><span class="wptw-toc__num toc-ms-roman">II</span><a class="wptw-toc__link toc-ms-main" href="#"><span class="wptw-toc__text toc-ms-title">Origins and context</span></a><span class="toc-ms-sub"><a class="wptw-toc__link toc-ms-sub-link" href="#">The founding years</a><a class="wptw-toc__link toc-ms-sub-link" href="#">Key influences</a></span></span></li>'+
                    '<li class="wptw-toc__item is-active toc-ms-item"><span class="toc-ms-node"><span class="toc-ms-node-inner"></span></span><span class="toc-ms-content"><span class="wptw-toc__num toc-ms-roman">III</span><a class="wptw-toc__link toc-ms-main" href="#"><span class="wptw-toc__text toc-ms-title">A theory of everything</span></a><span class="toc-ms-sub"><a class="wptw-toc__link toc-ms-sub-link" href="#">Framework overview</a><a class="wptw-toc__link toc-ms-sub-link" href="#">Core propositions</a></span></span></li>'+
                    '<li class="wptw-toc__item toc-ms-item"><span class="toc-ms-node"><span class="toc-ms-node-inner"></span></span><span class="toc-ms-content"><span class="wptw-toc__num toc-ms-roman">IV</span><a class="wptw-toc__link toc-ms-main" href="#"><span class="wptw-toc__text toc-ms-title">Evidence and proof</span></a></span></li>'+
                    '</ol></div><div class="toc-ms-footer"><span class="toc-ms-footer-label">Progress</span><div class="wptw-toc__prog toc-ms-track" role="presentation"><div class="wptw-toc__prog-fill toc-ms-track-fill" style="width:42%"></div></div></div>';
            } else if(layout === 'brutalist'){
                preview.innerHTML =
                    '<div class="wptw-toc__head toc-brut-header"><div class="wptw-toc__head-left"><span class="wptw-toc__label toc-brut-title"></span><span class="wptw-toc__rt">5 min read</span></div><div class="wptw-toc__actions toc-brut-actions">'+previewToggle()+'</div></div>'+
                    '<div class="wptw-toc__body"><ol class="wptw-toc__list" role="list">'+
                    '<li class="wptw-toc__item is-done toc-brut-item"><span class="toc-brut-row"><span class="toc-brut-step"><span class="wptw-toc__num toc-brut-num">1</span></span><span class="toc-brut-body"><a class="wptw-toc__link toc-brut-main" href="#"><span class="wptw-toc__text toc-brut-name">Introduction</span></a></span><span class="toc-brut-check"><svg width="8" height="6" viewBox="0 0 8 6" fill="none"><path d="M1 3l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span></span></li>'+
                    '<li class="wptw-toc__item is-done toc-brut-item"><span class="toc-brut-row"><span class="toc-brut-step"><span class="wptw-toc__num toc-brut-num">2</span></span><span class="toc-brut-body"><a class="wptw-toc__link toc-brut-main" href="#"><span class="wptw-toc__text toc-brut-name">Background and theory</span></a><span class="toc-brut-subs"><a class="wptw-toc__link toc-brut-sub-link" href="#">Historical context</a><a class="wptw-toc__link toc-brut-sub-link" href="#">Key frameworks</a></span></span><span class="toc-brut-check"><svg width="8" height="6" viewBox="0 0 8 6" fill="none"><path d="M1 3l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span></span></li>'+
                    '<li class="wptw-toc__item is-active toc-brut-item"><span class="toc-brut-row"><span class="toc-brut-step"><span class="wptw-toc__num toc-brut-num">3</span></span><span class="toc-brut-body"><a class="wptw-toc__link toc-brut-main" href="#"><span class="wptw-toc__text toc-brut-name">Methodology</span></a><span class="toc-brut-subs"><a class="wptw-toc__link toc-brut-sub-link" href="#">Research design</a><a class="wptw-toc__link toc-brut-sub-link" href="#">Data collection</a></span><span class="toc-brut-pill">Reading now</span></span><span class="toc-brut-check"></span></span></li>'+
                    '<li class="wptw-toc__item toc-brut-item"><span class="toc-brut-row"><span class="toc-brut-step"><span class="wptw-toc__num toc-brut-num">4</span></span><span class="toc-brut-body"><a class="wptw-toc__link toc-brut-main" href="#"><span class="wptw-toc__text toc-brut-name">Results</span></a></span><span class="toc-brut-check"></span></span></li>'+
                    '</ol></div><div class="wptw-toc__prog toc-brut-progress" role="presentation"><div class="wptw-toc__prog-fill toc-brut-progress-fill" style="width:42%"></div></div>';
            } else if(layout === 'default') {
                preview.innerHTML =
                    '<div class="wptw-toc__head"><div class="wptw-toc__head-left"><span class="wptw-toc__label"></span><span class="wptw-toc__rt">5 min read</span></div>'+previewToggle()+'</div>'+
                    '<div class="wptw-toc__prog" role="presentation"><div class="wptw-toc__prog-fill" style="width:42%"></div></div>'+
                    '<div class="wptw-toc__body"><ol class="wptw-toc__list" role="list">'+
                    '<li class="wptw-toc__item is-done"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">1.</span><span class="wptw-toc__text">Getting started</span></a></li>'+
                    '<li class="wptw-toc__item is-done wptw-toc__item--sub wptw-toc__item--d3"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">1.1.</span><span class="wptw-toc__text">Setup checklist</span></a></li>'+
                    '<li class="wptw-toc__item is-active"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">2.</span><span class="wptw-toc__text">Design decisions</span></a></li>'+
                    '<li class="wptw-toc__item wptw-toc__item--sub wptw-toc__item--d3"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">2.1.</span><span class="wptw-toc__text">Responsive behavior</span></a></li>'+
                    '</ol></div>';
            } else {
                preview.innerHTML =
                    '<div class="wptw-toc__head toc-ed-header"><div class="wptw-toc__head-left toc-ed-header-left"><span class="toc-ed-icon"><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M2 4h12M2 8h8M2 12h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span><span class="wptw-toc__label toc-ed-label"></span></div><div class="wptw-toc__actions toc-ed-actions"><span class="toc-ed-badge">4 sections</span><span class="wptw-toc__rt">5 min read</span>'+previewToggle()+'</div></div>'+
                    '<div class="wptw-toc__body toc-ed-body"><ol class="wptw-toc__list" role="list">'+
                    '<li class="wptw-toc__item is-done toc-ed-item"><span class="toc-ed-gutter"><span class="toc-ed-dot">&#10003;</span></span><span class="toc-ed-row"><a class="wptw-toc__link toc-ed-main" href="#"><span class="wptw-toc__text toc-ed-title">Overview</span></a><span class="toc-ed-meta"><span class="toc-ed-mins">2 min</span></span></span></li>'+
                    '<li class="wptw-toc__item is-done toc-ed-item"><span class="toc-ed-gutter"><span class="toc-ed-dot">&#10003;</span></span><span class="toc-ed-row"><a class="wptw-toc__link toc-ed-main" href="#"><span class="wptw-toc__text toc-ed-title">Prerequisites</span></a><span class="toc-ed-meta"><span class="toc-ed-mins">3 min</span></span></span></li>'+
                    '<li class="wptw-toc__item is-active toc-ed-item"><span class="toc-ed-gutter"><span class="toc-ed-dot">3</span></span><span class="toc-ed-row"><a class="wptw-toc__link toc-ed-main" href="#"><span class="wptw-toc__text toc-ed-title">Installation</span></a><span class="toc-ed-meta"><span class="toc-ed-mins">5 min</span></span><span class="toc-ed-sub"><a class="wptw-toc__link toc-ed-sub-link" href="#">Package setup</a><a class="wptw-toc__link toc-ed-sub-link" href="#">Environment variables</a><a class="wptw-toc__link toc-ed-sub-link" href="#">Verify your install</a></span></span></li>'+
                    '<li class="wptw-toc__item toc-ed-item"><span class="toc-ed-gutter"><span class="toc-ed-dot">4</span></span><span class="toc-ed-row"><a class="wptw-toc__link toc-ed-main" href="#"><span class="wptw-toc__text toc-ed-title">Configuration</span></a><span class="toc-ed-meta"><span class="toc-ed-mins">4 min</span></span></span></li>'+
                    '</ol></div><div class="toc-ed-footer"><div class="wptw-toc__prog toc-ed-progress" role="presentation"><div class="wptw-toc__prog-fill toc-ed-progress-fill" style="width:42%"></div></div><span class="toc-ed-progress-label">42% done</span></div>';
            }
        }
        function syncPreviewState(){
            if(!preview) return;
            var items = Array.prototype.slice.call(preview.querySelectorAll('.wptw-toc__item'));
            var activeIndex = items.findIndex(function(item){ return item.classList.contains('is-active'); });
            items.forEach(function(item, idx){
                item.classList.toggle('is-done', activeIndex > -1 && idx < activeIndex);
            });
            if(preview.classList.contains('wptw-toc--layout-editorial')){
                preview.querySelectorAll('.toc-ed-dot').forEach(function(dot, idx){
                    dot.textContent = idx < activeIndex ? '\u2713' : String(idx + 1);
                });
            }
            var edLabel = preview.querySelector('.toc-ed-progress-label');
            if(edLabel) edLabel.textContent = '42% done';
        }
        function updatePreview(){
            if(!preview) return;
            var colors = normalizedColors();
            Object.keys(cssMap).forEach(function(key){ preview.style.setProperty(cssMap[key], colors[key] || value(key, '')); });
            preview.style.setProperty('--wptw-radius', value('border_radius', 4) + 'px');
            preview.style.setProperty('--wptw-label-sz', value('font_size_label', 10) + 'px');
            preview.style.setProperty('--wptw-label-ls', (parseInt(value('letter_spacing_label', 13), 10) / 100) + 'em');
            preview.style.setProperty('--wptw-label-tt', value('text_transform_label', 'uppercase'));
            preview.style.setProperty('--wptw-rt-sz', value('font_size_rt', 10) + 'px');
            preview.style.setProperty('--wptw-num-sz', value('font_size_num', 11) + 'px');
            preview.style.setProperty('--wptw-flink', value('font_size_link', 14) + 'px');
            preview.style.setProperty('--wptw-fsub', value('font_size_sub', 13) + 'px');
            preview.style.setProperty('--wptw-font', fontStack(value('font_family', 'system')));
            var title = preview.querySelector('.wptw-toc__label');
            if(title) title.textContent = value('toc_title', 'Contents') || 'Contents';
            var layout = value('toc_layout', defaultLayout || 'manuscript');
            preview.className = preview.className.replace(/\bwptw-toc--layout-[a-z0-9_-]+/g, '').trim();
            preview.classList.add('wptw-toc--layout-' + layout);
            renderPreviewMarkup(layout);
            syncPreviewState();
            title = preview.querySelector('.wptw-toc__label');
            if(title) title.textContent = value('toc_title', 'Contents') || 'Contents';
            updateLayoutCards();
            updatePresetButtons();
            preview.querySelectorAll('.wptw-toc__num').forEach(function(num){ num.style.display = checked('show_numbers') ? '' : 'none'; });
            var rt = preview.querySelector('.wptw-toc__rt');
            if(rt) rt.style.display = checked('reading_time') ? '' : 'none';
            preview.querySelectorAll('.wptw-toc__prog,.toc-ms-footer,.toc-ed-footer').forEach(function(prog){
                prog.style.display = checked('reading_progress') ? '' : 'none';
            });
            var isOpen = value('default_state', 'open') !== 'closed';
            var list = preview.querySelector('.wptw-toc__list');
            var toggle = preview.querySelector('.wptw-toc__toggle');
            var ttext = preview.querySelector('.wptw-toc__tog-text');
            if(list) list.style.display = isOpen ? '' : 'none';
            if(toggle) toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            if(ttext) ttext.textContent = isOpen ? 'Hide' : 'Show';
        }
        if(form){
            form.addEventListener('input', function(e){
                if(e.target && e.target.classList && e.target.classList.contains('wptw-color')){
                    currentPreset = 'custom';
                }
                updatePreview();
            });
            form.addEventListener('change', function(e){
                if(e.target && e.target.name === optionName + '[toc_layout]'){
                    currentPreset = currentPreset && currentPreset !== 'custom' ? currentPreset : lastPreset || 'default';
                    lastPreset = currentPreset;
                    applyColors(resolvedPreset(currentPreset));
                } else if(e.target && e.target.classList && e.target.classList.contains('wptw-color')){
                    currentPreset = 'custom';
                }
                updatePreview();
            });
        }
        if(previewMode && previewCanvas){
            previewMode.addEventListener('click', function(){
                var mobile = previewCanvas.classList.toggle('is-mobile');
                previewMode.textContent = mobile ? 'Mobile' : 'Desktop';
            });
        }
        savedPreset = inferSavedPreset();
        currentPreset = savedPreset;
        lastPreset = savedPreset;
        updatePreview();

        /* ── Copy shortcode ── */
        document.querySelectorAll('.wptw-copybtn').forEach(function(btn){
            btn.addEventListener('click', function(){
                var t = btn.dataset.copy;
                if(navigator.clipboard) navigator.clipboard.writeText(t);
                btn.textContent='Copied!';
                setTimeout(function(){ btn.textContent='Copy'; }, 2000);
            });
        });

        /* ── Save flash ── */
        <?php 
        // Security: Nonce verification is handled by options.php before redirection.
        // We only show a UI flash if the settings-updated flag is present.
        if ( ! empty( $_GET['settings-updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
        var sv = document.getElementById('wptw-saved');
        if(sv){ sv.classList.add('on'); setTimeout(function(){ sv.classList.remove('on'); }, 3000); }
        <?php endif; ?>

    })();
    </script>
    <?php
}
