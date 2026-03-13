<?php
defined( 'ABSPATH' ) || exit;

/* ─── Content filter ──────────────────────────────────────── */
add_filter( 'the_content', 'wptw_inject_toc', 20 );

function wptw_inject_toc( string $content ): string {
    if ( ! is_singular() ) return $content;
    $post_id   = get_the_ID();
    $post_type = get_post_type( $post_id );
    $types     = (array) wptw_get( 'post_types' );
    if ( ! in_array( $post_type, $types, true ) ) return $content;

    $excluded = array_filter( array_map( 'intval', explode( ',', wptw_get( 'exclude_ids' ) ) ) );
    if ( in_array( $post_id, $excluded, true ) ) return $content;

    $meta = wptw_post_meta( $post_id );
    if ( ! empty( $meta['disable'] ) ) return $content;

    $position = ( $meta['position'] ?? '' ) ?: wptw_get( 'position' );

    $toc = wptw_build_toc( $content, $post_id );
    if ( $toc === null ) return $content;

    $marker = '<!-- wptw_toc_placeholder -->';
    if ( strpos( $content, $marker ) !== false ) {
        return str_replace( $marker, $toc, $content );
    }

    if ( $position === 'shortcode_only' ) return $content;

    if ( $position === 'after_first_paragraph' ) {
        $p = strpos( $content, '</p>' );
        if ( $p !== false ) return substr_replace( $content, '</p>' . $toc, $p, 4 );
    }

    $levels = (array) wptw_get( 'heading_levels' );
    $nums   = implode( '', array_map( fn($h) => substr($h,1), $levels ) );
    if ( preg_match( '/<h[' . $nums . '][\s>]/i', $content, $m, PREG_OFFSET_CAPTURE ) ) {
        return substr_replace( $content, $toc, $m[0][1], 0 );
    }

    return $toc . $content;
}

/* ─── Build TOC HTML ──────────────────────────────────────── */
function wptw_build_toc( string &$content, int $post_id = 0 ): ?string {
    $levels = (array) wptw_get( 'heading_levels' );
    if ( empty( $levels ) ) return null;

    $nums    = implode( '', array_map( fn($h) => substr($h,1), $levels ) );
    $pattern = '/<h([' . $nums . '])([^>]*)>(.*?)<\/h[' . $nums . ']>/is';
    preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );
    if ( empty( $matches ) ) return null;

    $h2_count = 0;
    foreach ( $matches as $m ) { if ( $m[1] === '2' ) $h2_count++; }
    if ( $h2_count < (int) wptw_get( 'min_headings' ) ) return null;

    $eff = fn( string $k ) => wptw_effective( $k, $post_id );

    $state     = $eff('default_state') === 'closed' ? 'closed' : 'open';
    $expanded  = $state === 'open';
    $show_nums = (bool)( $eff('show_numbers') !== '' ? (int)$eff('show_numbers') : wptw_get('show_numbers') );
    $show_rt   = (bool)( $eff('reading_time') !== '' ? (int)$eff('reading_time') : wptw_get('reading_time') );
    $show_prog = (bool) wptw_get('reading_progress');
    $sticky    = (bool)( $eff('sticky_header') !== '' ? (int)$eff('sticky_header') : wptw_get('sticky_header') );
    $toc_title = trim((string)$eff('toc_title')) ?: wptw_get('toc_title');
    $prefix    = sanitize_key( wptw_get('anchor_prefix') );
    $pid_sfx   = $post_id ? '-' . $post_id : '';
    $list_id   = 'wptw-list' . $pid_sfx;
    $toc_id    = 'wptw-toc' . $pid_sfx;

    /* Reading time — stored in data attr so JS can count down */
    $total_mins = wptw_reading_time( $content, (int) wptw_get('reading_wpm') );
    $rt_html = '';
    if ( $show_rt ) {
        $rt_html = '<span class="wptw-toc__rt" data-total-mins="' . $total_mins . '">' . $total_mins . ' min&nbsp;read</span>';
    }

    /* Progress bar markup */
    $prog_html = '';
    if ( $show_prog ) {
        $prog_html = '<div class="wptw-toc__prog" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="wptw-toc__prog-fill"></div></div>';
    }

    $aria_exp   = $expanded ? 'true' : 'false';
    $tog_label  = $expanded ? 'Hide' : 'Show';
    $list_style = $expanded ? '' : ' style="height:0;opacity:0;padding-top:0;padding-bottom:0;"';
    $sticky_cls = $sticky   ? ' wptw-toc--sticky' : '';

    /*
     * HTML structure:
     *   .wptw-toc                — outer card
     *     .wptw-toc__head        — header bar (source of truth for sticky clone)
     *     .wptw-toc__prog        — progress bar (also cloned into sticky bar)
     *     .wptw-toc__body        — overflow:hidden collapse region
     *       ol.wptw-toc__list
     */
    $out  = '<div class="wptw-toc' . $sticky_cls . '" id="' . $toc_id . '" role="navigation" aria-label="' . esc_attr__('Table of contents','wp-tablewise') . '">';
    $out .= '<div class="wptw-toc__head">';
    $out .=   '<div class="wptw-toc__head-left">';
    $out .=     '<span class="wptw-toc__label">' . esc_html($toc_title) . '</span>';
    $out .=     $rt_html;
    $out .=   '</div>';
    $out .=   '<button type="button" class="wptw-toc__toggle" aria-expanded="' . $aria_exp . '" aria-controls="' . $list_id . '">';
    $out .=     '<span class="wptw-toc__tog-text">' . $tog_label . '</span>';
    $out .=     '<svg class="wptw-toc__tog-icon" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="M2 4.5L6 8.5L10 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    $out .=   '</button>';
    $out .= '</div>';
    $out .= $prog_html;
    $out .= '<div class="wptw-toc__body">';
    $out .=   '<ol class="wptw-toc__list" id="' . $list_id . '" role="list"' . $list_style . '>';

    $counters  = [ 0, 0, 0, 0, 0 ];
    $min_depth = (int) substr( $levels[0], 1 );

    foreach ( $matches as $i => $match ) {
        $depth  = (int) $match[1];
        $attrs  = $match[2];
        $inner  = $match[3];
        $title  = wp_strip_all_tags( $inner );
        $anchor = $prefix . '-' . $i . $pid_sfx;

        $idx = $depth - 2;
        $counters[$idx]++;
        for ($j = $idx+1; $j <= 4; $j++) $counters[$j] = 0;

        $num_str = '';
        if ( $show_nums && ! preg_match('/^\d+\.\s/',$title) ) {
            $num_str = implode('.', array_slice($counters,0,$depth-1)) . '.';
        }

        $rel = $depth - $min_depth;
        $cls = 'wptw-toc__item' . ( $rel > 0 ? ' wptw-toc__item--sub wptw-toc__item--d' . $depth : '' );

        $out .= '<li class="' . $cls . '" style="--i:' . $i . '">';
        $out .= '<a class="wptw-toc__link" href="#' . esc_attr($anchor) . '">';
        if ($num_str) $out .= '<span class="wptw-toc__num" aria-hidden="true">' . esc_html($num_str) . '</span>';
        $out .= '<span class="wptw-toc__text">' . esc_html($title) . '</span>';
        $out .= '</a></li>' . "\n";

        if ( strpos($attrs,'id=') === false ) {
            $new_h = '<h' . $depth . ' id="' . esc_attr($anchor) . '"' . $attrs . '>' . $inner . '</h' . $depth . '>';
        } else {
            preg_match('/id=["\']([^"\']+)["\']/', $attrs, $id_m);
            if ( ! empty($id_m[1]) ) $out = str_replace('href="#'.esc_attr($anchor).'"','href="#'.esc_attr($id_m[1]).'"',$out);
            $new_h = $match[0];
        }
        $content = str_replace( $match[0], $new_h, $content );
    }

    $out .=   '</ol>';
    $out .= '</div>'; /* /.wptw-toc__body */
    $out .= '</div>'; /* /.wptw-toc */

    static $btt_done = false;
    if ( ! $btt_done && (bool) wptw_get('back_to_top') ) {
        $out     .= '<button type="button" class="wptw-btt" aria-label="' . esc_attr__('Back to contents','wp-tablewise') . '" hidden>↑</button>';
        $btt_done = true;
    }

    return $out;
}

/* ─── Google Font ─────────────────────────────────────────── */
add_action( 'wp_enqueue_scripts', function () {
    if ( ! is_singular( (array) wptw_get('post_types') ) ) return;
    $url = wptw_google_font_url( wptw_get('font_family') );
    if ( $url ) wp_enqueue_style( 'wptw-font', $url, [], null );
} );

/* ─── Frontend styles ─────────────────────────────────────── */
add_action( 'wp_head', 'wptw_frontend_styles', 5 );

function wptw_frontend_styles() {
    if ( ! is_singular( (array) wptw_get('post_types') ) ) return;
    $o    = wptw_get();
    $font = wptw_font_stack( $o['font_family'] );
    $ls   = round( $o['letter_spacing_label'] / 100, 4 );
    $tog_border = $o['color_toggle_border'] ?? $o['color_toggle_bg'];
    ?>
    <style id="wptw-styles">
    .wptw-toc {
        --wptw-bg:          <?php echo $o['color_bg']; ?>;
        --wptw-border:      <?php echo $o['color_border']; ?>;
        --wptw-radius:      <?php echo (int)$o['border_radius']; ?>px;
        --wptw-head-bg:     <?php echo $o['color_header_bg']; ?>;
        --wptw-label-c:     <?php echo $o['color_label']; ?>;
        --wptw-label-sz:    <?php echo (int)$o['font_size_label']; ?>px;
        --wptw-label-ls:    <?php echo $ls; ?>em;
        --wptw-label-tt:    <?php echo $o['text_transform_label']; ?>;
        --wptw-rt-c:        <?php echo $o['color_rt']; ?>;
        --wptw-rt-sz:       <?php echo (int)$o['font_size_rt']; ?>px;
        --wptw-rtbar-fill:  <?php echo $o['color_rt_bar']; ?>;
        --wptw-rtbar-bg:    <?php echo $o['color_rt_bar_bg']; ?>;
        --wptw-tog-bg:      <?php echo $o['color_toggle_bg']; ?>;
        --wptw-tog-fg:      <?php echo $o['color_toggle_fg']; ?>;
        --wptw-tog-bdr:     <?php echo $tog_border; ?>;
        --wptw-link:        <?php echo $o['color_link']; ?>;
        --wptw-link-hov:    <?php echo $o['color_link_hover']; ?>;
        --wptw-bar:         <?php echo $o['color_active_bar']; ?>;
        --wptw-act-bg:      <?php echo $o['color_active_bg']; ?>;
        --wptw-num-c:       <?php echo $o['color_number']; ?>;
        --wptw-num-sz:      <?php echo (int)$o['font_size_num']; ?>px;
        --wptw-flink:       <?php echo (int)$o['font_size_link']; ?>px;
        --wptw-fsub:        <?php echo (int)$o['font_size_sub']; ?>px;
        --wptw-btt-bg:      <?php echo $o['color_back_top_bg']; ?>;
        --wptw-btt-fg:      <?php echo $o['color_back_top_fg']; ?>;
        --wptw-font:        <?php echo $font; ?>;
        --wptw-mono:        'DM Mono','Fira Mono','Courier New',monospace;
        --wptw-ease:        200ms cubic-bezier(0.4,0,0.2,1);
        --wptw-sticky-top:  <?php echo (int)$o['sticky_top_offset']; ?>px;
    }

    /* ── Card ─────────────────────────────────────── */
    .wptw-toc {
        font-family:   var(--wptw-font);
        background:    var(--wptw-bg);
        border:        1px solid var(--wptw-border);
        border-radius: var(--wptw-radius);
        margin:        2.25rem 0;
        position:      relative;
    }

    /* ── Header ───────────────────────────────────── */
    .wptw-toc__head {
        display:         flex;
        align-items:     center;
        justify-content: space-between;
        padding:         11px 18px;
        background:      var(--wptw-head-bg);
        border-bottom:   1px solid var(--wptw-border);
        border-radius:   var(--wptw-radius) var(--wptw-radius) 0 0;
        position:        relative;
        z-index:         2;
    }
    .wptw-toc__head-left {
        display:     flex;
        align-items: center;
        gap:         12px;
        flex:        1;
        min-width:   0;
    }

    /* ── .wptw-toc__label ──────────────────────────── */
    .wptw-toc__label {
        font-family:    var(--wptw-mono);
        font-size:      var(--wptw-label-sz);
        font-weight:    500;
        letter-spacing: var(--wptw-label-ls);
        text-transform: var(--wptw-label-tt);
        color:          var(--wptw-label-c);
        white-space:    nowrap;
    }

    /* ── .wptw-toc__rt (reading time + countdown) ─── */
    .wptw-toc__rt {
        font-family: var(--wptw-mono);
        font-size:   var(--wptw-rt-sz);
        color:       var(--wptw-rt-c);
        white-space: nowrap;
        padding-left: 10px;
        border-left:  1px solid var(--wptw-border);
        transition:   color 0.3s ease;
    }

    /* ── Progress bar ─────────────────────────────── */
    .wptw-toc__prog {
        height:     3px;
        background: var(--wptw-rtbar-bg);
        position:   relative;
        overflow:   hidden;
    }
    .wptw-toc__prog-fill {
        position:   absolute;
        top:        0; left: 0;
        height:     100%;
        width:      0%;
        background: var(--wptw-rtbar-fill);
        transition: width 120ms linear;
    }

    /* ── .wptw-toc__toggle ────────────────────────── */
    .wptw-toc__toggle {
        display:        flex;
        align-items:    center;
        gap:            5px;
        background:     var(--wptw-tog-bg) !important; /* override theme */
        color:          var(--wptw-tog-fg) !important;
        border:         1px solid var(--wptw-tog-bdr) !important; /* explicit: prevents theme border */
        border-radius:  calc(var(--wptw-radius) / 2 + 1px);
        padding:        5px 11px;
        cursor:         pointer;
        font-family:    var(--wptw-mono);
        font-size:      10px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        line-height:    1;
        flex-shrink:    0;
        transition:     opacity var(--wptw-ease);
        outline:        none;
    }
    .wptw-toc__toggle:hover { opacity: .72; }
    .wptw-toc__tog-icon { transition: transform var(--wptw-ease); flex-shrink:0; }
    .wptw-toc__toggle[aria-expanded="false"] .wptw-toc__tog-icon { transform: rotate(-90deg); }

    /* ── Body ─────────────────────────────────────── */
    .wptw-toc__body {
        overflow:      hidden;
        border-radius: 0 0 var(--wptw-radius) var(--wptw-radius);
    }

    /* ── List ─────────────────────────────────────── */
    .wptw-toc__list {
        list-style: none;
        margin:     0;
        padding:    10px 0;
        transition: height var(--wptw-ease), opacity var(--wptw-ease), padding var(--wptw-ease);
    }

    /* ── Item animations ──────────────────────────── */
    .wptw-toc__item {
        opacity:         0;
        transform:       translateY(5px);
        animation:       wptw-in 260ms ease forwards;
        animation-delay: calc(var(--i,0) * 22ms + 35ms);
    }
    @keyframes wptw-in { to { opacity:1; transform:none; } }

    .wptw-toc__item--d3 .wptw-toc__link { padding-left: 36px; }
    .wptw-toc__item--d4 .wptw-toc__link { padding-left: 54px; }
    .wptw-toc__item--d5 .wptw-toc__link { padding-left: 72px; }
    .wptw-toc__item--d6 .wptw-toc__link { padding-left: 90px; }

    /* ── .wptw-toc__link ──────────────────────────── */
    .wptw-toc__link {
        display:         flex;
        align-items:     baseline;
        gap:             8px;
        padding:         7px 18px;
        text-decoration: none;
        color:           var(--wptw-link);
        font-size:       var(--wptw-flink);
        line-height:     1.45;
        border-left:     2px solid transparent;
        transition:      color var(--wptw-ease), border-color var(--wptw-ease), background var(--wptw-ease);
    }
    .wptw-toc__link:hover {
        color:              var(--wptw-link-hov);
        background:         color-mix(in srgb, var(--wptw-head-bg) 65%, transparent);
        border-left-color:  var(--wptw-border);
        text-decoration:    none;
    }
    .wptw-toc__item.is-active > .wptw-toc__link {
        color:              var(--wptw-link-hov);
        border-left-color:  var(--wptw-bar);
        background:         var(--wptw-act-bg);
        font-weight:        500;
    }
    .wptw-toc__item.is-active .wptw-toc__num { color: var(--wptw-bar); }

    /* ── .wptw-toc__num ───────────────────────────── */
    .wptw-toc__num {
        font-family: var(--wptw-mono);
        font-size:   var(--wptw-num-sz);
        color:       var(--wptw-num-c);
        flex-shrink: 0;
        min-width:   20px;
        transition:  color var(--wptw-ease);
    }
    .wptw-toc__text { flex:1; }

    .wptw-toc__item--sub > .wptw-toc__link {
        font-size: var(--wptw-fsub);
        color:     color-mix(in srgb, var(--wptw-link) 72%, transparent);
    }
    .wptw-toc__item--sub > .wptw-toc__link:hover { color: var(--wptw-link-hov); }

    /* ═══ STICKY BAR ═══════════════════════════════════════════
     * Fixed-position clone of the TOC header.
     * Matches the TOC card width and position — not full viewport.
     * JS sets left/width to mirror the card's getBoundingClientRect().
     * ══════════════════════════════════════════════════════════ */
    .wptw-sticky-bar {
        position:        fixed;
        /* left/width set dynamically by JS to match card width */
        top:             0; /* overridden by JS with sticky_top offset */
        z-index:         9999;
        display:         flex;
        align-items:     center;
        justify-content: space-between;
        padding:         11px 18px;
        background:      var(--wptw-head-bg);
        border:          1px solid var(--wptw-border);
        border-radius:   var(--wptw-radius);
        box-shadow:      0 4px 18px rgba(0,0,0,0.13);
        opacity:         0;
        transform:       translateY(-6px);
        transition:      opacity 200ms ease, transform 200ms ease;
        pointer-events:  none;
        font-family:     var(--wptw-font);
        box-sizing:      border-box;
    }
    .wptw-sticky-bar.is-visible {
        opacity:        1;
        transform:      none;
        pointer-events: auto;
    }
    /* Progress bar inside sticky bar */
    .wptw-sticky-bar .wptw-toc__prog {
        position: absolute;
        bottom:   0; left: 0; right: 0;
        height:   3px;
        border-radius: 0;
    }
    /* Inherit toggle styles inside bar */
    .wptw-sticky-bar .wptw-toc__toggle {
        background:  var(--wptw-tog-bg) !important;
        color:       var(--wptw-tog-fg) !important;
        border:      1px solid var(--wptw-tog-bdr) !important;
    }
    .wptw-sticky-bar .wptw-toc__head-left {
        display:     flex;
        align-items: center;
        gap:         12px;
        flex:        1;
        min-width:   0;
    }

    /* ── Back-to-top ──────────────────────────────── */
    .wptw-btt {
        position:      fixed;
        bottom:        28px;
        right:         28px;
        width:         42px;
        height:        42px;
        background:    var(--wptw-btt-bg) !important;
        color:         var(--wptw-btt-fg) !important;
        border:        none !important;
        border-radius: 50%;
        font-size:     17px;
        cursor:        pointer;
        opacity:       0;
        transform:     translateY(10px) scale(0.9);
        transition:    opacity 260ms ease, transform 260ms ease;
        z-index:       10000;
        box-shadow:    0 3px 12px rgba(0,0,0,0.20);
        line-height:   1;
    }
    .wptw-btt.is-visible { opacity:1; transform:none; }
    .wptw-btt:hover { opacity:0.8; }

    /* ── Mobile ───────────────────────────────────── */
    @media (max-width:600px) {
        .wptw-toc                           { margin:1.5rem 0; }
        .wptw-toc__head                     { padding:9px 14px; }
        .wptw-toc__link                     { padding:7px 14px; }
        .wptw-toc__item--d3 .wptw-toc__link { padding-left:28px; }
        .wptw-toc__item--d4 .wptw-toc__link { padding-left:42px; }
        .wptw-sticky-bar                    { padding:9px 14px; }
        .wptw-btt                           { bottom:16px; right:16px; width:38px; height:38px; font-size:15px; }
    }

    <?php if(!empty($o['custom_css'])) echo $o['custom_css']; ?>
    </style>
    <?php
}

/* ─── Frontend scripts ────────────────────────────────────── */
add_action( 'wp_footer', 'wptw_frontend_scripts' );

function wptw_frontend_scripts() {
    if ( ! is_singular( (array) wptw_get('post_types') ) ) return;
    $o   = wptw_get();
    $cfg = [
        'smoothScroll'    => (bool) $o['smooth_scroll'],
        'scrollOffset'    => (int)  $o['scroll_offset'],
        'highlightActive' => (bool) $o['highlight_active'],
        'backToTop'       => (bool) $o['back_to_top'],
        'readingProgress' => (bool) $o['reading_progress'],
        'anchorPrefix'    => sanitize_key( $o['anchor_prefix'] ),
        'stickyTop'       => (int)  $o['sticky_top_offset'],
        'readingWpm'      => (int)  $o['reading_wpm'],
    ];
    ?>
    <script id="wptw-script">
    (function(){
        'use strict';
        var cfg = <?php echo wp_json_encode($cfg); ?>;

        document.addEventListener('DOMContentLoaded', function(){

            /* ══ Helper: get live rect of TOC card ══════════════════ */
            function getCardRect(toc) {
                return toc.getBoundingClientRect();
            }

            /* ══ Per TOC ════════════════════════════════════════════ */
            document.querySelectorAll('.wptw-toc').forEach(function(toc){
                var head     = toc.querySelector('.wptw-toc__head');
                var body     = toc.querySelector('.wptw-toc__body');
                var toggle   = toc.querySelector('.wptw-toc__toggle');
                var list     = toc.querySelector('.wptw-toc__list');
                var ttext    = toc.querySelector('.wptw-toc__tog-text');
                var progFill = toc.querySelector('.wptw-toc__prog-fill');
                var rtSpan   = toc.querySelector('.wptw-toc__rt');
                if (!toggle || !list || !body) return;

                /* Natural height for CSS transition */
                if (toggle.getAttribute('aria-expanded') === 'true') {
                    list.style.height = list.scrollHeight + 'px';
                }

                /* ── Collapse / expand ── */
                toggle.addEventListener('click', function(){
                    var open = toggle.getAttribute('aria-expanded') === 'true';
                    if (open) {
                        list.style.height = list.scrollHeight + 'px';
                        requestAnimationFrame(function(){
                            list.style.height       = '0px';
                            list.style.opacity      = '0';
                            list.style.paddingTop   = '0';
                            list.style.paddingBottom= '0';
                        });
                        toggle.setAttribute('aria-expanded','false');
                        if (ttext) ttext.textContent = 'Show';
                        syncBarToggle(false);
                    } else {
                        list.style.paddingTop    = '';
                        list.style.paddingBottom = '';
                        list.style.opacity = '1';
                        list.style.height  = list.scrollHeight + 'px';
                        toggle.setAttribute('aria-expanded','true');
                        if (ttext) ttext.textContent = 'Hide';
                        syncBarToggle(true);
                        list.addEventListener('transitionend', function rst(){
                            list.style.height = 'auto';
                            list.removeEventListener('transitionend',rst);
                        });
                    }
                });

                /* ══ STICKY BAR ═════════════════════════════════════
                 *
                 * BEHAVIOUR:
                 *   When TOC is OPEN → sticky triggers when the TOC HEADER
                 *   scrolls out of view (hybrid feel — header was visible).
                 *   When TOC is CLOSED → sticky triggers when the whole
                 *   (small) card exits viewport.
                 *
                 *   Either way, the bar matches the card's pixel width and
                 *   horizontal position — NOT full viewport width.
                 *
                 *   Bar also contains a synced progress bar fill.
                 * ═════════════════════════════════════════════════ */
                var stickyBar    = null;
                var barProgFill  = null;
                var lastBarLeft  = -1;
                var lastBarWidth = -1;

                function positionBar() {
                    if (!stickyBar) return;
                    /* Get current card position relative to viewport */
                    var rect = toc.getBoundingClientRect();
                    var left = Math.round(rect.left + window.scrollX);
                    var w    = Math.round(rect.width);
                    if (left !== lastBarLeft || w !== lastBarWidth) {
                        stickyBar.style.left  = left + 'px';
                        stickyBar.style.width = w + 'px';
                        lastBarLeft  = left;
                        lastBarWidth = w;
                    }
                }

                if (toc.classList.contains('wptw-toc--sticky') && head) {

                    /* Build bar — clone header content */
                    stickyBar = document.createElement('div');
                    stickyBar.className = 'wptw-sticky-bar';
                    stickyBar.style.top = cfg.stickyTop + 'px';

                    /* Inherit CSS custom properties from toc element */
                    var tocStyle = window.getComputedStyle(toc);
                    [
                        '--wptw-head-bg','--wptw-border','--wptw-radius',
                        '--wptw-label-c','--wptw-label-sz','--wptw-label-ls','--wptw-label-tt',
                        '--wptw-rt-c','--wptw-rt-sz',
                        '--wptw-tog-bg','--wptw-tog-fg','--wptw-tog-bdr',
                        '--wptw-rtbar-fill','--wptw-rtbar-bg',
                        '--wptw-font','--wptw-mono','--wptw-ease'
                    ].forEach(function(v){
                        stickyBar.style.setProperty(v, tocStyle.getPropertyValue(v).trim());
                    });

                    /* Clone header HTML */
                    stickyBar.innerHTML = head.innerHTML;

                    /* Add progress bar clone inside sticky bar */
                    if (progFill) {
                        var barProg = document.createElement('div');
                        barProg.className = 'wptw-toc__prog';
                        barProg.innerHTML = '<div class="wptw-toc__prog-fill"></div>';
                        stickyBar.appendChild(barProg);
                        barProgFill = barProg.querySelector('.wptw-toc__prog-fill');
                    }

                    /* Wire bar's toggle → real toggle */
                    var barToggle = stickyBar.querySelector('.wptw-toc__toggle');
                    if (barToggle) {
                        barToggle.addEventListener('click', function(){ toggle.click(); });
                    }

                    document.body.appendChild(stickyBar);
                    positionBar();

                    /* Keep bar positioned on resize */
                    window.addEventListener('resize', positionBar, { passive:true });

                    /* ── Scroll-based sticky trigger (hybrid) ──
                     *
                     * Strategy: watch the TOC HEADER (not the card).
                     * - When header scrolls above sticky-top offset → show bar.
                     * - When header is below that point → hide bar.
                     * This means: if TOC is open, you get the sticky feeling
                     * immediately when the header leaves. If TOC is closed
                     * (header IS the whole card), same behaviour.
                     * ─────────────────────────────────────────── */
                    var isBarVisible = false;

                    function checkSticky() {
                        var headRect  = head.getBoundingClientRect();
                        var shouldShow = headRect.bottom < cfg.stickyTop;
                        if (shouldShow !== isBarVisible) {
                            isBarVisible = shouldShow;
                            stickyBar.classList.toggle('is-visible', shouldShow);
                        }
                        positionBar();
                    }

                    window.addEventListener('scroll', checkSticky, { passive:true });
                    checkSticky(); /* run once on load */
                }

                /* Keep bar toggle text/state in sync */
                function syncBarToggle(isOpen) {
                    if (!stickyBar) return;
                    var bt = stickyBar.querySelector('.wptw-toc__tog-text');
                    var si = stickyBar.querySelector('.wptw-toc__toggle');
                    if (bt) bt.textContent = isOpen ? 'Hide' : 'Show';
                    if (si) si.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    /* also sync icon rotation */
                    var icon = si ? si.querySelector('.wptw-toc__tog-icon') : null;
                    if (icon) icon.style.transform = isOpen ? '' : 'rotate(-90deg)';
                }

                /* ══ READING PROGRESS + TIME COUNTDOWN ═════════════ */
                if (cfg.readingProgress || rtSpan) {
                    var totalMins = rtSpan ? parseFloat(rtSpan.dataset.totalMins) || 0 : 0;

                    window.addEventListener('scroll', function(){
                        /* Progress: scroll from bottom-of-card to bottom-of-page */
                        var tocBottom  = toc.offsetTop + toc.offsetHeight;
                        var docH       = document.documentElement.scrollHeight;
                        var winH       = window.innerHeight;
                        var scrollable = docH - winH - tocBottom;
                        if (scrollable <= 0) return;

                        var scrolled = Math.max(0, window.scrollY - tocBottom);
                        var pct      = Math.min(100, scrolled / scrollable);
                        var pctInt   = Math.round(pct * 100);

                        /* Update progress bars (card + sticky) */
                        if (cfg.readingProgress) {
                            var w = pctInt + '%';
                            if (progFill)    progFill.style.width    = w;
                            if (barProgFill) barProgFill.style.width = w;
                            /* Update aria */
                            var prog = toc.querySelector('.wptw-toc__prog');
                            if (prog) prog.setAttribute('aria-valuenow', pctInt);
                        }

                        /* Update reading time countdown */
                        if (rtSpan && totalMins > 0) {
                            var remaining = Math.ceil(totalMins * (1 - pct));
                            remaining = Math.max(0, remaining);
                            var label;
                            if (pctInt >= 98) {
                                label = '✓ Done';
                            } else if (pct < 0.02) {
                                label = totalMins + ' min\u00a0read';
                            } else {
                                label = remaining + ' min\u00a0left';
                            }
                            /* Update in card */
                            if (rtSpan.textContent !== label) rtSpan.textContent = label;
                            /* Update in sticky bar */
                            if (stickyBar) {
                                var barRt = stickyBar.querySelector('.wptw-toc__rt');
                                if (barRt && barRt.textContent !== label) barRt.textContent = label;
                            }
                        }

                    }, { passive:true });
                }

            }); /* forEach .wptw-toc */

            /* ══ Smooth scroll ══════════════════════════════════════ */
            if (cfg.smoothScroll) {
                document.querySelectorAll('.wptw-toc__link').forEach(function(link){
                    link.addEventListener('click', function(e){
                        e.preventDefault();
                        var id  = this.getAttribute('href').slice(1);
                        var el  = document.getElementById(id);
                        if (!el) return;
                        var top = el.getBoundingClientRect().top + window.pageYOffset - cfg.scrollOffset;
                        window.scrollTo({ top:top, behavior:'smooth' });
                    });
                });
            }

            /* ══ Active section highlight ═══════════════════════════ */
            if (cfg.highlightActive && 'IntersectionObserver' in window) {
                var p        = cfg.anchorPrefix;
                var headings = document.querySelectorAll('h2[id^="'+p+'"],h3[id^="'+p+'"],h4[id^="'+p+'"],h5[id^="'+p+'"],h6[id^="'+p+'"]');
                var items    = document.querySelectorAll('.wptw-toc__item');
                var activeId = null;

                var obs = new IntersectionObserver(function(entries){
                    entries.forEach(function(e){ if(e.isIntersecting) activeId = e.target.id; });
                    items.forEach(function(item){
                        var a = item.querySelector('.wptw-toc__link');
                        item.classList.toggle('is-active', !!(a && a.getAttribute('href') === '#'+activeId));
                    });
                }, { rootMargin: '-'+cfg.scrollOffset+'px 0px -55% 0px', threshold:0 });

                headings.forEach(function(h){ obs.observe(h); });
            }

            /* ══ Back-to-top ════════════════════════════════════════ */
            if (cfg.backToTop) {
                var btn   = document.querySelector('.wptw-btt');
                var tocEl = document.querySelector('.wptw-toc');
                if (btn && tocEl) {
                    btn.removeAttribute('hidden');
                    window.addEventListener('scroll', function(){
                        var past = window.pageYOffset > (tocEl.offsetTop + tocEl.offsetHeight + 150);
                        btn.classList.toggle('is-visible', past);
                    }, { passive:true });
                    btn.addEventListener('click', function(){
                        window.scrollTo({ top: tocEl.offsetTop - cfg.scrollOffset, behavior:'smooth' });
                    });
                }
            }

        }); /* DOMContentLoaded */
    })();
    </script>
    <?php
}
