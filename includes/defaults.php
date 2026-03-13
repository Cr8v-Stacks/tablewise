<?php
defined( 'ABSPATH' ) || exit;

function wptw_available_fonts(): array {
    return [
        ''                 => 'System default (inherit from theme)',
        'system'           => 'System UI stack',
        'Inter'            => 'Inter',
        'DM Sans'          => 'DM Sans',
        'Lato'             => 'Lato',
        'Nunito'           => 'Nunito',
        'Open Sans'        => 'Open Sans',
        'Poppins'          => 'Poppins',
        'Raleway'          => 'Raleway',
        'Roboto'           => 'Roboto',
        'Source Sans 3'    => 'Source Sans 3',
        'Work Sans'        => 'Work Sans',
        'Playfair Display' => 'Playfair Display',
        'Merriweather'     => 'Merriweather',
        'DM Mono'          => 'DM Mono (monospace)',
        'Fira Mono'        => 'Fira Mono (monospace)',
        'JetBrains Mono'   => 'JetBrains Mono (monospace)',
    ];
}

function wptw_font_stack( string $font ): string {
    if ( $font === '' )       return 'inherit';
    if ( $font === 'system' ) return "system-ui,-apple-system,'Helvetica Neue',Arial,sans-serif";
    return "'{$font}',system-ui,sans-serif";
}

function wptw_google_font_url( string $font ): string {
    if ( in_array( $font, [ '', 'system' ], true ) ) return '';
    return "https://fonts.googleapis.com/css2?family=" . urlencode( $font ) . ":wght@400;500;600&display=swap";
}

function wptw_defaults(): array {
    return [
        /* Visibility */
        'post_types'            => [ 'post' ],
        'min_headings'          => 2,
        'exclude_ids'           => '',

        /* Headings */
        'heading_levels'        => [ 'h2', 'h3', 'h4' ],
        'anchor_prefix'         => 'section',

        /* Display */
        'toc_title'             => 'Contents',
        'position'              => 'before_first_heading',
        'default_state'         => 'open',
        'show_numbers'          => true,
        'smooth_scroll'         => true,
        'scroll_offset'         => 80,
        'highlight_active'      => true,
        'back_to_top'           => true,
        'reading_time'          => true,
        'reading_progress'      => true,   // reading progress bar
        'reading_wpm'           => 200,

        /* Sticky header — implemented as fixed overlay, not CSS sticky */
        'sticky_header'         => true,
        'sticky_top_offset'     => 20,

        /* Colours — TOC card */
        'color_bg'              => '#ffffff',
        'color_border'          => '#e8e8e8',

        /* Colours — header bar */
        'color_header_bg'       => '#fafafa',
        'color_label'           => '#999999',   // .wptw-toc__label
        'color_rt'              => '#bbbbbb',   // .wptw-toc__rt (reading time text)
        'color_rt_bar'          => '#111111',   // reading progress bar fill
        'color_rt_bar_bg'       => '#e8e8e8',   // reading progress bar track

        /* Colours — toggle button */
        'color_toggle_bg'       => '#111111',
        'color_toggle_fg'       => '#ffffff',
        'color_toggle_border'   => '#111111',

        /* Colours — list */
        'color_link'            => '#333333',
        'color_link_hover'      => '#000000',
        'color_active_bar'      => '#111111',
        'color_active_bg'       => '#f4f4f4',
        'color_number'          => '#cccccc',   // .wptw-toc__num

        /* Colours — back-to-top */
        'color_back_top_bg'     => '#111111',
        'color_back_top_fg'     => '#ffffff',

        /* Typography — link list */
        'font_family'           => 'system',
        'font_size_link'        => 14,
        'font_size_sub'         => 13,

        /* Typography — header elements */
        'font_size_label'       => 10,    // px — .wptw-toc__label
        'font_size_rt'          => 10,    // px — .wptw-toc__rt
        'font_size_num'         => 11,    // px — .wptw-toc__num

        'letter_spacing_label'  => 13,    // hundredths of em (13 = 0.13em)
        'text_transform_label'  => 'uppercase',  // uppercase | none

        'border_radius'         => 4,

        /* Advanced */
        'custom_css'            => '',
    ];
}

function wptw_get( string $key = '' ) {
    static $cache = null;
    if ( $cache === null ) {
        $cache = wp_parse_args( (array) get_option( WPTW_OPTION, [] ), wptw_defaults() );
    }
    return $key === '' ? $cache : ( $cache[ $key ] ?? null );
}

function wptw_post_meta( int $post_id ): array {
    $raw = get_post_meta( $post_id, WPTW_META, true );
    return is_array( $raw ) ? $raw : [];
}

function wptw_effective( string $key, int $post_id = 0 ) {
    $meta = $post_id ? wptw_post_meta( $post_id ) : [];
    if ( isset( $meta[ $key ] ) && $meta[ $key ] !== '' ) {
        return $meta[ $key ];
    }
    return wptw_get( $key );
}
