<?php
/**
 * Plugin Name:       ListiScore - Listing Health Score for GeoDirectory
 * Plugin URI:        https://github.com/RachidBray/listiscore
 * Description:       Scores every GeoDirectory listing 0-100 based on completeness and quality, with actionable recommendations.
 * Version:           1.0.0
 * Author:            AddictedToWeb
 * Author URI:        https://addictedtoweb.com
 * License:           GPL-2.0-or-later
 * Text Domain:       listiscore
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 * @package ListiScore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LISTISCORE_VERSION', '1.0.0' );
define( 'LISTISCORE_FILE', __FILE__ );
define( 'LISTISCORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LISTISCORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Begins execution of the plugin once GeoDirectory core has finished loading.
 *
 * Since everything within the plugin is registered via hooks, kicking off
 * the plugin from this point in the page life cycle does not affect it.
 *
 * @since 0.2.0
 */
function listiscore_load() {
	require_once LISTISCORE_DIR . 'includes/class-listiscore-plugin.php';

	return ListiScore_Plugin::instance();
}

/*
 * `geodirectory_loaded` fires the instant GD's plugin file is included by
 * WordPress, which can happen before or after ours depending on plugin load
 * order (not guaranteed alphabetical). If GD already loaded by the time this
 * line runs, the action has already fired and hooking it now would miss it
 * forever, so call straight away instead of waiting for a hook that will
 * never come.
 */
if ( class_exists( 'GeoDirectory' ) ) {
	listiscore_load();
} else {
	add_action( 'geodirectory_loaded', 'listiscore_load' );
}

/**
 * Admin notice when GeoDirectory is not active at all.
 *
 * This can't live inside ListiScore_Plugin because `geodirectory_loaded` simply
 * never fires when GeoDirectory is missing, so nothing in that class would
 * ever run. `plugins_loaded` fires regardless, once every plugin file has
 * been included, so it's a reliable place to check.
 */
add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'GeoDirectory' ) ) {
			add_action( 'admin_notices', 'listiscore_missing_gd_notice' );
		}
	},
	20
);

/**
 * Renders the missing-GeoDirectory admin notice.
 */
function listiscore_missing_gd_notice() {
	echo '<div class="notice notice-error"><p>';
	esc_html_e( 'Listing Health Score requires the GeoDirectory plugin to be installed and active.', 'listiscore' );
	echo '</p></div>';
}

/**
 * Clear scheduled events on deactivation.
 */
function listiscore_deactivate() {
	wp_clear_scheduled_hook( 'listiscore_daily_recalc' );
}
register_deactivation_hook( LISTISCORE_FILE, 'listiscore_deactivate' );
