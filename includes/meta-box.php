<?php
defined( 'ABSPATH' ) || exit;

/* ─── Register meta box ───────────────────────────────────── */
add_action( 'add_meta_boxes', function () {
    $post_types = (array) wptw_get( 'post_types' );
    foreach ( $post_types as $pt ) {
        add_meta_box(
            'wptw-meta-box',
            'WP TableWise',
            'wptw_render_meta_box',
            $pt,
            'side',
            'default'
        );
    }
} );

/* ─── Render meta box ─────────────────────────────────────── */
function wptw_render_meta_box( WP_Post $post ) {
    $meta  = wptw_post_meta( $post->ID );
    $g     = wptw_get();
    wp_nonce_field( 'wptw_meta_save', 'wptw_meta_nonce' );
    ?>
    <div class="wptw-meta">

        <!-- Disable TOC -->
        <div class="wptw-meta-field">
            <label class="wptw-meta-toggle">
                <input type="hidden" name="wptw_meta[disable]" value="0">
                <input type="checkbox" name="wptw_meta[disable]" value="1"
                       id="wptw-meta-disable"
                       <?php checked( ! empty( $meta['disable'] ) ); ?>>
                <span class="wptw-meta-knob"></span>
                <span class="wptw-meta-toggle-label">Disable TOC on this post</span>
            </label>
            <p class="wptw-meta-help">Hides the TOC regardless of global settings.</p>
        </div>

        <div id="wptw-meta-options" class="<?php echo ! empty( $meta['disable'] ) ? 'wptw-meta-dimmed' : ''; ?>">

            <!-- Initial state override -->
            <div class="wptw-meta-field">
                <label class="wptw-meta-label" for="wptw-meta-state">Initial TOC state</label>
                <select name="wptw_meta[default_state]" id="wptw-meta-state" class="wptw-meta-select">
                    <option value="" <?php selected( $meta['default_state'] ?? '', '' ); ?>>
                        — Use global setting (<?php echo esc_html( $g['default_state'] ); ?>)
                    </option>
                    <option value="open"   <?php selected( $meta['default_state'] ?? '', 'open'   ); ?>>Open</option>
                    <option value="closed" <?php selected( $meta['default_state'] ?? '', 'closed' ); ?>>Closed</option>
                </select>
            </div>

            <!-- Position override -->
            <div class="wptw-meta-field">
                <label class="wptw-meta-label" for="wptw-meta-pos">TOC position</label>
                <select name="wptw_meta[position]" id="wptw-meta-pos" class="wptw-meta-select">
                    <option value="" <?php selected( $meta['position'] ?? '', '' ); ?>>— Use global setting</option>
                    <option value="before_first_heading"  <?php selected( $meta['position'] ?? '', 'before_first_heading'  ); ?>>Before first heading</option>
                    <option value="after_first_paragraph" <?php selected( $meta['position'] ?? '', 'after_first_paragraph' ); ?>>After first paragraph</option>
                    <option value="shortcode_only"        <?php selected( $meta['position'] ?? '', 'shortcode_only'        ); ?>>Manual — shortcode only</option>
                </select>
            </div>

            <!-- Custom title override -->
            <div class="wptw-meta-field">
                <label class="wptw-meta-label" for="wptw-meta-title">TOC title</label>
                <input type="text" name="wptw_meta[toc_title]" id="wptw-meta-title"
                       value="<?php echo esc_attr( $meta['toc_title'] ?? '' ); ?>"
                       placeholder="<?php echo esc_attr( $g['toc_title'] ); ?>"
                       class="wptw-meta-input">
                <p class="wptw-meta-help">Leave blank to use global title.</p>
            </div>

            <!-- Show/hide numbers -->
            <div class="wptw-meta-field">
                <label class="wptw-meta-label" for="wptw-meta-nums">Section numbers</label>
                <select name="wptw_meta[show_numbers]" id="wptw-meta-nums" class="wptw-meta-select">
                    <option value="" <?php selected( $meta['show_numbers'] ?? '', '' ); ?>>— Use global setting</option>
                    <option value="1" <?php selected( $meta['show_numbers'] ?? '', '1' ); ?>>Show numbers</option>
                    <option value="0" <?php selected( $meta['show_numbers'] ?? '', '0' ); ?>>Hide numbers</option>
                </select>
            </div>

            <!-- Sticky header override -->
            <div class="wptw-meta-field">
                <label class="wptw-meta-label" for="wptw-meta-sticky">Sticky TOC header</label>
                <select name="wptw_meta[sticky_header]" id="wptw-meta-sticky" class="wptw-meta-select">
                    <option value="" <?php selected( $meta['sticky_header'] ?? '', '' ); ?>>— Use global setting</option>
                    <option value="1" <?php selected( $meta['sticky_header'] ?? '', '1' ); ?>>Enabled</option>
                    <option value="0" <?php selected( $meta['sticky_header'] ?? '', '0' ); ?>>Disabled</option>
                </select>
            </div>

            <!-- Reading time override -->
            <div class="wptw-meta-field">
                <label class="wptw-meta-label" for="wptw-meta-rt">Reading time</label>
                <select name="wptw_meta[reading_time]" id="wptw-meta-rt" class="wptw-meta-select">
                    <option value="" <?php selected( $meta['reading_time'] ?? '', '' ); ?>>— Use global setting</option>
                    <option value="1" <?php selected( $meta['reading_time'] ?? '', '1' ); ?>>Show</option>
                    <option value="0" <?php selected( $meta['reading_time'] ?? '', '0' ); ?>>Hide</option>
                </select>
            </div>

        </div><!-- /#wptw-meta-options -->
    </div>

    <style>
        .wptw-meta { font-size: 13px; }
        .wptw-meta-field { margin-bottom: 14px; }
        .wptw-meta-label { display: block; font-weight: 600; color: #333; margin-bottom: 4px; font-size: 12px; }
        .wptw-meta-help { font-size: 11px; color: #999; margin: 3px 0 0; line-height: 1.4; }
        .wptw-meta-input,
        .wptw-meta-select { width: 100%; padding: 5px 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 12.5px; box-sizing: border-box; }
        .wptw-meta-input:focus,
        .wptw-meta-select:focus { border-color: #2271b1; outline: none; box-shadow: 0 0 0 2px rgba(34,113,177,.15); }
        .wptw-meta-toggle { display: flex; align-items: center; gap: 9px; cursor: pointer; }
        .wptw-meta-toggle input[type="hidden"] { display: none; }
        .wptw-meta-toggle input[type="checkbox"] { opacity: 0; width: 0; height: 0; position: absolute; }
        .wptw-meta-knob { position: relative; flex-shrink: 0; width: 32px; height: 18px; background: #ccc; border-radius: 18px; transition: background .2s; }
        .wptw-meta-knob::after { content: ''; position: absolute; left: 2px; top: 2px; width: 14px; height: 14px; background: #fff; border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 2px rgba(0,0,0,.2); }
        .wptw-meta-toggle input[type="checkbox"]:checked ~ .wptw-meta-knob { background: #c0392b; }
        .wptw-meta-toggle input[type="checkbox"]:checked ~ .wptw-meta-knob::after { transform: translateX(14px); }
        .wptw-meta-toggle-label { font-weight: 600; font-size: 12.5px; color: #333; }
        .wptw-meta-dimmed { opacity: .4; pointer-events: none; }
        #wptw-meta-options { margin-top: 14px; padding-top: 14px; border-top: 1px solid #eee; transition: opacity .2s; }
    </style>

    <script>
    (function(){
        var cb  = document.getElementById('wptw-meta-disable');
        var box = document.getElementById('wptw-meta-options');
        if (!cb || !box) return;
        cb.addEventListener('change', function(){
            box.classList.toggle('wptw-meta-dimmed', this.checked);
        });
    })();
    </script>
    <?php
}

/* ─── Save meta box ───────────────────────────────────────── */
add_action( 'save_post', function ( $post_id ) {
    if ( ! isset( $_POST['wptw_meta_nonce'] ) ) return;
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wptw_meta_nonce'] ) ), 'wptw_meta_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $raw = isset( $_POST['wptw_meta'] ) ? map_deep( wp_unslash( $_POST['wptw_meta'] ), 'sanitize_text_field' ) : [];
    $clean = [];

    $clean['disable']       = ! empty( $raw['disable'] ) ? 1 : 0;
    $clean['default_state'] = in_array( $raw['default_state'] ?? '', [ 'open','closed','' ], true ) ? $raw['default_state'] : '';
    $clean['position']      = in_array( $raw['position'] ?? '', [ 'before_first_heading','after_first_paragraph','shortcode_only','' ], true ) ? $raw['position'] : '';
    $clean['toc_title']     = sanitize_text_field( $raw['toc_title'] ?? '' );
    $clean['show_numbers']  = in_array( $raw['show_numbers'] ?? '', [ '0','1','' ], true ) ? $raw['show_numbers'] : '';
    $clean['sticky_header'] = in_array( $raw['sticky_header'] ?? '', [ '0','1','' ], true ) ? $raw['sticky_header'] : '';
    $clean['reading_time']  = in_array( $raw['reading_time'] ?? '', [ '0','1','' ], true ) ? $raw['reading_time'] : '';

    update_post_meta( $post_id, WPTW_META, $clean );
} );

/* ─── Gutenberg sidebar panel via REST + block editor sidebar ─ */
add_action( 'enqueue_block_editor_assets', function () {
    $screen = get_current_screen();
    $types  = (array) wptw_get( 'post_types' );
    if ( ! $screen || ! in_array( $screen->post_type, $types, true ) ) return;

    $post_id = get_the_ID();
    $meta    = wptw_post_meta( $post_id );
    $g       = wptw_get();

    // Register meta for REST API
    foreach ( [ 'post', 'page' ] + $types as $pt ) {
        register_post_meta( $pt, WPTW_META, [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'object',
            'auth_callback' => fn() => current_user_can( 'edit_posts' ),
        ] );
    }

    // Inline Gutenberg sidebar script
    wp_add_inline_script( 'wp-blocks', wptw_gutenberg_sidebar_js( $meta, $g ), 'after' );
} );

function wptw_gutenberg_sidebar_js( array $meta, array $g ): string {
    $global_state = esc_js( $g['default_state'] );
    $global_title = esc_js( $g['toc_title'] );
    $meta_key     = WPTW_META;

    ob_start();
    ?>
(function(){
    var el   = wp.element.createElement;
    var __   = wp.i18n.__;
    var frag = wp.element.Fragment;

    var { registerPlugin } = wp.plugins;
    var { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
    var { PanelBody, SelectControl, TextControl, ToggleControl, Tip } = wp.components;
    var { useSelect, useDispatch } = wp.data;

    function TableWiseSidebar(){
        var postId = useSelect(function(s){ return s('core/editor').getCurrentPostId(); });
        var metaRaw = useSelect(function(s){
            return s('core/editor').getEditedPostAttribute('meta') || {};
        });

        var { editPost } = useDispatch('core/editor');

        var meta = metaRaw['<?php echo esc_js( $meta_key ); ?>'] || {};

        function setMeta(key, val){
            var updated = Object.assign({}, metaRaw['<?php echo esc_js( $meta_key ); ?>'] || {});
            updated[key] = val;
            var metaUpdate = {};
            metaUpdate['<?php echo esc_js( $meta_key ); ?>'] = updated;
            editPost({ meta: metaUpdate });
        }

        return el(frag, null,
            el(PluginSidebarMoreMenuItem, { target: 'wptw-sidebar' }, 'TableWise'),
            el(PluginSidebar, { name: 'wptw-sidebar', title: 'TableWise', icon: el('svg',{width:16,height:16,viewBox:'0 0 16 16',fill:'none'},el('rect',{x:.5,y:.5,width:15,height:15,rx:3,stroke:'currentColor','strokeWidth':1.2}),el('path',{d:'M4 5h4M4 8h8M4 11h6',stroke:'currentColor','strokeWidth':1.2,'strokeLinecap':'round'})) },
                el(PanelBody, { title: 'Per-post Settings', initialOpen: true },

                    el(ToggleControl, {
                        label: 'Disable TOC on this post',
                        checked: !!meta.disable,
                        onChange: function(v){ setMeta('disable', v ? 1 : 0); },
                        help: 'Hides the TOC regardless of global settings.'
                    }),

                    el(SelectControl, {
                        label: 'Initial TOC state',
                        value: meta.default_state || '',
                        options: [
                            { value: '', label: '— Global (' + '<?php echo esc_js( $global_state ); ?>' + ')' },
                            { value: 'open',   label: 'Open'   },
                            { value: 'closed', label: 'Closed' },
                        ],
                        onChange: function(v){ setMeta('default_state', v); }
                    }),

                    el(SelectControl, {
                        label: 'TOC position',
                        value: meta.position || '',
                        options: [
                            { value: '', label: '— Use global setting' },
                            { value: 'before_first_heading',  label: 'Before first heading'   },
                            { value: 'after_first_paragraph', label: 'After first paragraph'  },
                            { value: 'shortcode_only',        label: 'Shortcode only'         },
                        ],
                        onChange: function(v){ setMeta('position', v); }
                    }),

                    el(TextControl, {
                        label: 'TOC title',
                        value: meta.toc_title || '',
                        placeholder: '<?php echo esc_js( $global_title ); ?>',
                        onChange: function(v){ setMeta('toc_title', v); },
                        help: 'Leave blank to use global title.'
                    }),

                    el(SelectControl, {
                        label: 'Section numbers',
                        value: meta.show_numbers !== undefined ? String(meta.show_numbers) : '',
                        options: [
                            { value: '',  label: '— Use global setting' },
                            { value: '1', label: 'Show' },
                            { value: '0', label: 'Hide' },
                        ],
                        onChange: function(v){ setMeta('show_numbers', v); }
                    }),

                    el(SelectControl, {
                        label: 'Sticky TOC header',
                        value: meta.sticky_header !== undefined ? String(meta.sticky_header) : '',
                        options: [
                            { value: '',  label: '— Use global setting' },
                            { value: '1', label: 'Enabled'  },
                            { value: '0', label: 'Disabled' },
                        ],
                        onChange: function(v){ setMeta('sticky_header', v); }
                    }),

                    el(SelectControl, {
                        label: 'Reading time',
                        value: meta.reading_time !== undefined ? String(meta.reading_time) : '',
                        options: [
                            { value: '',  label: '— Use global setting' },
                            { value: '1', label: 'Show' },
                            { value: '0', label: 'Hide' },
                        ],
                        onChange: function(v){ setMeta('reading_time', v); }
                    }),

                    el(Tip, null, 'Use [wptw_toc] shortcode for manual TOC placement.')
                )
            )
        );
    }

    registerPlugin('tablewise', { render: TableWiseSidebar });
})();
    <?php
    return (string) ob_get_clean();
}
