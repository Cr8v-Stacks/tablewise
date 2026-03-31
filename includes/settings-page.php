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

/* ── Render page ──────────────────────────────────────────── */
function wptw_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $o       = wptw_get();
    $pts     = wptw_public_post_types();
    $presets = wptw_color_presets();
    $fonts   = wptw_available_fonts();

    $tabs = [
        'visibility' => [ 'label'=>'Visibility', 'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 3C4 3 1 7.5 1 7.5S4 12 7.5 12 14 7.5 14 7.5 11 3 7.5 3Z" stroke="currentColor" stroke-width="1.3"/><circle cx="7.5" cy="7.5" r="2" stroke="currentColor" stroke-width="1.3"/></svg>' ],
        'headings'   => [ 'label'=>'Headings',   'svg'=>'<svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M2 3v9M2 7.5h11M13 3v9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>' ],
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
            <a href="https://cr8vstacks.com" target="_blank" class="wptw-by">by Cr8v Stacks ↗</a>
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
                    <div class="wptw-fields">
                        <div class="wptw-field">
                            <label class="wptw-label">Presets</label>
                            <div class="wptw-presets" id="wptw-presets">
                                <?php foreach ( $presets as $pid => $p ) : ?>
                                <button type="button" class="wptw-pbtn" data-preset="<?php echo esc_attr( $pid ); ?>"><?php echo esc_html( $p['emoji'] ); ?> <?php echo esc_html( $p['label'] ); ?></button>
                                <?php endforeach; ?>
                                <button type="button" class="wptw-pbtn wptw-reset" data-preset="__reset">↩ Reset</button>
                            </div>
                        </div>

                        <?php
                        $cgroups=[
                            'Card'           =>['color_bg'=>'Background','color_border'=>'Border'],
                            'Header bar'     =>['color_header_bg'=>'Header background','color_label'=>'Title label text','color_rt'=>'Reading time text','color_rt_bar'=>'Progress bar fill','color_rt_bar_bg'=>'Progress bar track'],
                            'Toggle button'  =>['color_toggle_bg'=>'Button background','color_toggle_fg'=>'Button text / icon','color_toggle_border'=>'Button border'],
                            'List items'     =>['color_link'=>'Link text','color_link_hover'=>'Link hover','color_active_bar'=>'Active — left bar','color_active_bg'=>'Active — background','color_number'=>'Section numbers'],
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
            </form>
        </div>
    </div>

    <style>
    /* ─── Admin UI ─────────────────────────────────────────── */
    .wptw-wrap{max-width:920px;padding-bottom:80px;--a:#111;--b:#2271b1;--bd:#e2e2e2;--bg:#f7f7f7;--r:6px}
    .wptw-ph{display:flex;align-items:center;justify-content:space-between;margin:18px 0 22px}
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
    .wptw-pbtn{padding:6px 14px;background:#f5f5f5;border:1px solid #ddd;border-radius:20px;cursor:pointer;font-size:12px;transition:.14s;font-family:inherit}
    .wptw-pbtn:hover{background:#111;color:#fff;border-color:#111}
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
    $def_colors    = array_filter( wptw_defaults(), fn($k) => str_starts_with($k,'color_'), ARRAY_FILTER_USE_KEY );
    $defaults_json = wp_json_encode( $def_colors );
    ?>
    <script>
    /* WP TableWise settings — pure vanilla JS, no jQuery dependencies */
    (function(){
        'use strict';

        var presets  = <?php echo $presets_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
        var defClrs  = <?php echo $defaults_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
        presets['__reset'] = defClrs;

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
                var def = btn.dataset.default;
                var inp = document.querySelector('input.wptw-color[data-key="'+key+'"]');
                if(inp){
                    inp.value = def;
                    var hex = inp.nextElementSibling;
                    if(hex) hex.textContent = def;
                }
            });
        });

        /* ── Colour presets ── */
        document.querySelectorAll('.wptw-pbtn').forEach(function(btn){
            btn.addEventListener('click', function(){
                var p = presets[btn.dataset.preset];
                if(!p) return;
                Object.keys(p).forEach(function(key){
                    var val = p[key];
                    var inp = document.querySelector('input.wptw-color[data-key="'+key+'"]');
                    if(!inp) return;
                    inp.value = val;
                    var hex = inp.nextElementSibling;
                    if(hex) hex.textContent = val;
                });
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
