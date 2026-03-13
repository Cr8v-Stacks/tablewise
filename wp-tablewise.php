<?php
/**
 * Plugin Name:       WP TableWise
 * Plugin URI:        https://cr8vstacks.com/wp-tablewise
 * Description:       A clean, minimal, and highly customisable Table of Contents plugin. Supports sticky headers, per-post overrides, active-section tracking, reading time estimates, and a full settings panel.
 * Version:           1.2.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Cr8v Stacks
 * Author URI:        https://cr8vstacks.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-tablewise
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'WPTW_VERSION', '1.2.0' );
define( 'WPTW_DIR',     plugin_dir_path( __FILE__ ) );
define( 'WPTW_URL',     plugin_dir_url( __FILE__ ) );
define( 'WPTW_OPTION',  'wptw_settings' );
define( 'WPTW_META',    '_wptw_post_settings' );

require_once WPTW_DIR . 'includes/defaults.php';
require_once WPTW_DIR . 'includes/helpers.php';
require_once WPTW_DIR . 'includes/settings-page.php';
require_once WPTW_DIR . 'includes/meta-box.php';
require_once WPTW_DIR . 'includes/quick-edit.php';
require_once WPTW_DIR . 'includes/frontend.php';
require_once WPTW_DIR . 'includes/shortcode.php';

register_activation_hook( __FILE__, function () {
    if ( ! get_option( WPTW_OPTION ) ) {
        add_option( WPTW_OPTION, wptw_defaults() );
    }
} );

register_uninstall_hook( __FILE__, 'wptw_uninstall' );
function wptw_uninstall() {
    delete_option( WPTW_OPTION );
}
