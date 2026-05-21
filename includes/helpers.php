<?php
defined( 'ABSPATH' ) || exit;

function wptw_sanitize_color( string $val, string $fallback ): string {
    $s = sanitize_hex_color( $val );
    return $s !== null ? $s : $fallback;
}

function wptw_reading_time( string $content, int $wpm = 200 ): int {
    $words = str_word_count( wp_strip_all_tags( $content ) );
    return max( 1, (int) round( $words / max( 1, $wpm ) ) );
}

function wptw_clamp( $val, int $min, int $max ): int {
    return max( $min, min( $max, (int) $val ) );
}

function wptw_public_post_types(): array {
    $types = [];
    foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $pt ) {
        $types[ $pt->name ] = $pt->label;
    }
    return $types;
}

/**
 * Colour presets.
 * Every key must map 1:1 to a colour option in wptw_defaults().
 * Toggle button gets an explicit border-color to override theme defaults.
 */
function wptw_color_presets(): array {
    return [
        'default' => [
            'label' => 'Default', 'emoji' => '*',
            'colors' => [
                'color_bg'             => '#0f172a',
                'color_border'         => '#243044',
                'color_header_bg'      => '#0b1120',
                'color_label'          => '#d97706',
                'color_rt'             => '#94a3b8',
                'color_rt_bar'         => '#d97706',
                'color_rt_bar_bg'      => '#243044',
                'color_toggle_bg'      => '#f8fafc',
                'color_toggle_fg'      => '#0f172a',
                'color_toggle_border'  => '#f8fafc',
                'color_link'           => '#cbd5e1',
                'color_link_hover'     => '#ffffff',
                'color_active_bar'     => '#d97706',
                'color_active_bg'      => '#1e293b',
                'color_number'         => '#d97706',
                'color_back_top_bg'    => '#f8fafc',
                'color_back_top_fg'    => '#0f172a',
            ],
        ],
        'light' => [
            'label' => 'Light', 'emoji' => '☀',
            'colors' => [
                'color_bg'             => '#ffffff',
                'color_border'         => '#e8e8e8',
                'color_header_bg'      => '#fafafa',
                'color_label'          => '#888888',
                'color_rt'             => '#aaaaaa',
                'color_rt_bar'         => '#111111',
                'color_rt_bar_bg'      => '#e0e0e0',
                'color_toggle_bg'      => '#111111',
                'color_toggle_fg'      => '#ffffff',
                'color_toggle_border'  => '#111111',
                'color_link'           => '#333333',
                'color_link_hover'     => '#000000',
                'color_active_bar'     => '#111111',
                'color_active_bg'      => '#f2f2f2',
                'color_number'         => '#cccccc',
                'color_back_top_bg'    => '#111111',
                'color_back_top_fg'    => '#ffffff',
            ],
        ],
        'dark' => [
            'label' => 'Dark', 'emoji' => '🌙',
            'colors' => [
                'color_bg'             => '#1a1a1a',
                'color_border'         => '#3a3a3a',
                'color_header_bg'      => '#111111',
                'color_label'          => '#888888',
                'color_rt'             => '#666666',
                'color_rt_bar'         => '#e0e0e0',
                'color_rt_bar_bg'      => '#3a3a3a',
                'color_toggle_bg'      => '#e0e0e0',
                'color_toggle_fg'      => '#111111',
                'color_toggle_border'  => '#e0e0e0',
                'color_link'           => '#c8c8c8',
                'color_link_hover'     => '#ffffff',
                'color_active_bar'     => '#e0e0e0',
                'color_active_bg'      => '#2a2a2a',
                'color_number'         => '#555555',
                'color_back_top_bg'    => '#e0e0e0',
                'color_back_top_fg'    => '#111111',
            ],
        ],
        'ocean' => [
            'label' => 'Ocean', 'emoji' => '🌊',
            'colors' => [
                'color_bg'             => '#f0f8ff',
                'color_border'         => '#b8d8f0',
                'color_header_bg'      => '#dff0fc',
                'color_label'          => '#3a7499',
                'color_rt'             => '#5a94b8',
                'color_rt_bar'         => '#0d6eaa',
                'color_rt_bar_bg'      => '#b8d8f0',
                'color_toggle_bg'      => '#0d6eaa',
                'color_toggle_fg'      => '#ffffff',
                'color_toggle_border'  => '#0d6eaa',
                'color_link'           => '#1a4e6e',
                'color_link_hover'     => '#0a2e48',
                'color_active_bar'     => '#0d6eaa',
                'color_active_bg'      => '#cce8f7',
                'color_number'         => '#8ab8d8',
                'color_back_top_bg'    => '#0d6eaa',
                'color_back_top_fg'    => '#ffffff',
            ],
        ],
        'forest' => [
            'label' => 'Forest', 'emoji' => '🌿',
            'colors' => [
                'color_bg'             => '#f5faf3',
                'color_border'         => '#b8d4b0',
                'color_header_bg'      => '#e3f2db',
                'color_label'          => '#3e7230',
                'color_rt'             => '#5a8a4a',
                'color_rt_bar'         => '#2a6018',
                'color_rt_bar_bg'      => '#b8d4b0',
                'color_toggle_bg'      => '#2a6018',
                'color_toggle_fg'      => '#ffffff',
                'color_toggle_border'  => '#2a6018',
                'color_link'           => '#1e4415',
                'color_link_hover'     => '#0c2808',
                'color_active_bar'     => '#2a6018',
                'color_active_bg'      => '#d0eac8',
                'color_number'         => '#90bc88',
                'color_back_top_bg'    => '#2a6018',
                'color_back_top_fg'    => '#ffffff',
            ],
        ],
        'rose' => [
            'label' => 'Rose', 'emoji' => '🌸',
            'colors' => [
                'color_bg'             => '#fff5f7',
                'color_border'         => '#eab8c4',
                'color_header_bg'      => '#fce4ec',
                'color_label'          => '#a03050',
                'color_rt'             => '#c05070',
                'color_rt_bar'         => '#b02040',
                'color_rt_bar_bg'      => '#eab8c4',
                'color_toggle_bg'      => '#b02040',
                'color_toggle_fg'      => '#ffffff',
                'color_toggle_border'  => '#b02040',
                'color_link'           => '#6a1428',
                'color_link_hover'     => '#3a0010',
                'color_active_bar'     => '#b02040',
                'color_active_bg'      => '#f8d0da',
                'color_number'         => '#d898a8',
                'color_back_top_bg'    => '#b02040',
                'color_back_top_fg'    => '#ffffff',
            ],
        ],
    ];
}
