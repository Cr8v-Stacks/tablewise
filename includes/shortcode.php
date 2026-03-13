<?php
defined( 'ABSPATH' ) || exit;

/**
 * [wptw_toc] shortcode
 * Renders the TOC for the current post. All settings come from the global
 * options panel + per-post meta box overrides. No shortcode attributes needed.
 */
add_shortcode( 'wptw_toc', function () {
    if ( ! in_the_loop() && ! is_singular() ) return '';

    global $post;
    if ( ! $post ) return '';

    $meta = wptw_post_meta( $post->ID );
    if ( ! empty( $meta['disable'] ) ) return '';

    // Output placeholder. 
    // Actual injection happens in frontend.php's the_content filter to prevent infinite loops.
    return '<!-- wptw_toc_placeholder -->';
} );
