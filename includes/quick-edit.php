<?php
defined( 'ABSPATH' ) || exit;

/* ─── Add custom column to post list ─────────────────────── */
add_filter( 'manage_posts_columns',       'wptw_add_list_column' );
add_filter( 'manage_pages_columns',       'wptw_add_list_column' );

function wptw_add_list_column( array $cols ): array {
    $types = (array) wptw_get( 'post_types' );
    $screen = get_current_screen();
    if ( $screen && in_array( $screen->post_type, $types, true ) ) {
        $cols['wptw_toc'] = '<span title="WP TableWise">TOC</span>';
    }
    return $cols;
}

/* ─── Populate column ─────────────────────────────────────── */
add_action( 'manage_posts_custom_column', 'wptw_render_list_column', 10, 2 );
add_action( 'manage_pages_custom_column', 'wptw_render_list_column', 10, 2 );

function wptw_render_list_column( string $col, int $post_id ): void {
    if ( $col !== 'wptw_toc' ) return;
    $meta = wptw_post_meta( $post_id );

    if ( ! empty( $meta['disable'] ) ) {
        echo '<span class="wptw-col-off" title="TOC disabled">✕</span>';
        return;
    }
    $state = $meta['default_state'] ?? '';
    if ( $state === 'open' ) {
        echo '<span class="wptw-col-open" title="Open by default">▾ Open</span>';
    } elseif ( $state === 'closed' ) {
        echo '<span class="wptw-col-closed" title="Closed by default">▸ Closed</span>';
    } else {
        echo '<span class="wptw-col-global" title="Using global setting">Global</span>';
    }
}

/* ─── Quick Edit fields ───────────────────────────────────── */
add_action( 'quick_edit_custom_box', function ( $col, $post_type ) {
    if ( $col !== 'wptw_toc' ) return;
    $types = (array) wptw_get( 'post_types' );
    if ( ! in_array( $post_type, $types, true ) ) return;
    $g = wptw_get();
    ?>
    <fieldset class="inline-edit-col-right wptw-qe-fieldset">
        <div class="inline-edit-col">
            <label class="wptw-qe-heading">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none" style="vertical-align:-2px"><rect x=".5" y=".5" width="12" height="12" rx="2.5" stroke="currentColor" stroke-width="1.2"/><path d="M3 4h4M3 6.5h7M3 9h5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/></svg>
                WP TableWise
            </label>

            <div class="wptw-qe-row">
                <label class="wptw-qe-label">
                    <input type="checkbox" name="wptw_qe[disable]" value="1" class="wptw-qe-disable">
                    <span>Disable TOC</span>
                </label>
            </div>

            <div class="wptw-qe-row">
                <label class="wptw-qe-label">Initial state</label>
                <select name="wptw_qe[default_state]" class="wptw-qe-select">
                    <option value="">— Global (<?php echo esc_html( $g['default_state'] ); ?>)</option>
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                </select>
            </div>

            <div class="wptw-qe-row">
                <label class="wptw-qe-label">Position</label>
                <select name="wptw_qe[position]" class="wptw-qe-select">
                    <option value="">— Global</option>
                    <option value="before_first_heading">Before first heading</option>
                    <option value="after_first_paragraph">After first paragraph</option>
                    <option value="shortcode_only">Shortcode only</option>
                </select>
            </div>

            <div class="wptw-qe-row">
                <label class="wptw-qe-label">Section numbers</label>
                <select name="wptw_qe[show_numbers]" class="wptw-qe-select">
                    <option value="">— Global</option>
                    <option value="1">Show</option>
                    <option value="0">Hide</option>
                </select>
            </div>

            <input type="hidden" name="wptw_qe_nonce" value="">
            <input type="hidden" name="wptw_qe_post_id" value="">
        </div>
    </fieldset>
    <style>
        .wptw-qe-fieldset { padding-top: 4px; }
        .wptw-qe-heading { display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 12px; color: #333; margin-bottom: 10px; }
        .wptw-qe-row { margin-bottom: 8px; }
        .wptw-qe-label { display: flex; align-items: center; gap: 7px; font-size: 12px; color: #444; }
        .wptw-qe-select { width: 100%; max-width: 200px; font-size: 12px; padding: 4px 6px; border: 1px solid #ddd; border-radius: 3px; margin-top: 3px; display: block; }
        /* List column styles */
        .wptw-col-off    { color: #c0392b; font-size: 11px; font-weight: 600; }
        .wptw-col-open   { color: #2e7d32; font-size: 11px; }
        .wptw-col-closed { color: #777;    font-size: 11px; }
        .wptw-col-global { color: #aaa;    font-size: 11px; }
    </style>
    <?php
}, 10, 2 );

/* ─── Populate Quick Edit via JS ──────────────────────────── */
add_action( 'admin_footer-edit.php', function () {
    $types = (array) wptw_get( 'post_types' );
    $screen = get_current_screen();
    if ( ! $screen || ! in_array( $screen->post_type, $types, true ) ) return;
    ?>
    <script>
    (function($){
        var qeNonce = '<?php echo esc_js( wp_create_nonce( 'wptw_qe_save' ) ); ?>';

        /* Pre-populate Quick Edit fields when row is opened */
        $(document).on('click', '.editinline', function(){
            var postId  = $(this).closest('tr').attr('id').replace('post-','');
            var $row    = $('#post-' + postId);

            /* Pull hidden data attributes we'll embed in each row */
            var disable  = $row.find('.wptw-qe-data').data('disable')  || 0;
            var state    = $row.find('.wptw-qe-data').data('state')    || '';
            var position = $row.find('.wptw-qe-data').data('position') || '';
            var nums     = $row.find('.wptw-qe-data').data('nums');
            if(nums === undefined) nums = '';

            /* Small delay for WP to render the quick-edit row */
            setTimeout(function(){
                var $qe = $('.inline-edit-row:visible');
                $qe.find('[name="wptw_qe[disable]"]').prop('checked', !!parseInt(disable));
                $qe.find('[name="wptw_qe[default_state]"]').val(state);
                $qe.find('[name="wptw_qe[position]"]').val(position);
                $qe.find('[name="wptw_qe[show_numbers]"]').val(String(nums));
                $qe.find('[name="wptw_qe_nonce"]').val(qeNonce);
                $qe.find('[name="wptw_qe_post_id"]').val(postId);
            }, 50);
        });

        /* Intercept the Quick Edit save request and send our data too */
        $(document).on('click', '.save.button', function(){
            var $qe      = $(this).closest('.inline-edit-row');
            var postId   = $qe.find('[name="wptw_qe_post_id"]').val();
            var nonce    = $qe.find('[name="wptw_qe_nonce"]').val();
            if(!postId || !nonce) return;

            var data = {
                action:   'wptw_quick_edit_save',
                post_id:  postId,
                nonce:    nonce,
                disable:  $qe.find('[name="wptw_qe[disable]"]').is(':checked') ? 1 : 0,
                default_state: $qe.find('[name="wptw_qe[default_state]"]').val(),
                position:      $qe.find('[name="wptw_qe[position]"]').val(),
                show_numbers:  $qe.find('[name="wptw_qe[show_numbers]"]').val(),
            };
            $.post(ajaxurl, data);
        });
    })(jQuery);
    </script>
    <?php
} );

/* ─── Embed data attrs in each row (for JS pre-population) ── */
add_action( 'manage_posts_custom_column', function( $col, $post_id ) {
    if ( $col !== 'wptw_toc' ) return;
    $meta = wptw_post_meta( $post_id );
    $nums = isset( $meta['show_numbers'] ) ? $meta['show_numbers'] : '';
    echo '<span class="wptw-qe-data" style="display:none"'
        . ' data-disable="'  . esc_attr( $meta['disable'] ?? 0 )      . '"'
        . ' data-state="'    . esc_attr( $meta['default_state'] ?? '' ) . '"'
        . ' data-position="' . esc_attr( $meta['position'] ?? '' )     . '"'
        . ' data-nums="'     . esc_attr( $nums )                       . '"'
        . '></span>';
}, 20, 2 );

/* ─── AJAX handler for Quick Edit save ───────────────────── */
add_action( 'wp_ajax_wptw_quick_edit_save', function () {
    check_ajax_referer( 'wptw_qe_save', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) wp_send_json_error( 'Permissions' );

    $existing = wptw_post_meta( $post_id );

    $existing['disable']       = ! empty( $_POST['disable'] ) ? 1 : 0;
    $existing['default_state'] = in_array( sanitize_text_field( wp_unslash( $_POST['default_state'] ?? '' ) ), [ 'open', 'closed', '' ], true ) ? sanitize_text_field( wp_unslash( $_POST['default_state'] ) ) : '';
    $existing['position']      = in_array( sanitize_text_field( wp_unslash( $_POST['position'] ?? '' ) ), [ 'before_first_heading', 'after_first_paragraph', 'shortcode_only', '' ], true ) ? sanitize_text_field( wp_unslash( $_POST['position'] ) ) : '';
    $existing['show_numbers']  = in_array( sanitize_text_field( wp_unslash( $_POST['show_numbers'] ?? '' ) ), [ '0', '1', '' ], true ) ? sanitize_text_field( wp_unslash( $_POST['show_numbers'] ) ) : '';

    update_post_meta( $post_id, WPTW_META, $existing );
    wp_send_json_success();
} );
