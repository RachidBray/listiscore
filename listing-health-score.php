<?php
/**
 * Plugin Name:       Listing Health Score for GeoDirectory
 * Plugin URI:        https://addictedtoweb.com
 * Description:       Scores every GeoDirectory listing 0-100 based on completeness and quality, with actionable recommendations.
 * Version:           0.5.0
 * Author:            AddictedToWeb
 * Author URI:        https://addictedtoweb.com
 * License:           GPL-2.0-or-later
 * Text Domain:       listing-health-score
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 * @package Listing_Health_Score
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LHS_VERSION', '0.5.0' );
define( 'LHS_FILE', __FILE__ );
define( 'LHS_DIR', plugin_dir_path( __FILE__ ) );
define( 'LHS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Begins execution of the plugin once GeoDirectory core has finished loading.
 *
 * Since everything within the plugin is registered via hooks, kicking off
 * the plugin from this point in the page life cycle does not affect it.
 *
 * @since 0.2.0
 */
function lhs_load() {
	require_once LHS_DIR . 'includes/class-lhs-plugin.php';

	return LHS_Plugin::instance();
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
	lhs_load();
} else {
	add_action( 'geodirectory_loaded', 'lhs_load' );
}

/**
 * Admin notice when GeoDirectory is not active at all.
 *
 * This can't live inside LHS_Plugin because `geodirectory_loaded` simply
 * never fires when GeoDirectory is missing, so nothing in that class would
 * ever run. `plugins_loaded` fires regardless, once every plugin file has
 * been included, so it's a reliable place to check.
 */
add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'GeoDirectory' ) ) {
			add_action( 'admin_notices', 'lhs_missing_gd_notice' );
		}
	},
	20
);

/**
 * Renders the missing-GeoDirectory admin notice.
 */
function lhs_missing_gd_notice() {
	echo '<div class="notice notice-error"><p>';
	esc_html_e( 'Listing Health Score requires the GeoDirectory plugin to be installed and active.', 'listing-health-score' );
	echo '</p></div>';
}

/**
 * Clear scheduled events on deactivation.
 */
function lhs_deactivate() {
	wp_clear_scheduled_hook( 'lhs_daily_recalc' );
}
register_deactivation_hook( LHS_FILE, 'lhs_deactivate' );
