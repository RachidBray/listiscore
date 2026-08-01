<?php
/**
 * Plugin Name:       Listing Health Score for GeoDirectory
 * Plugin URI:        https://addictedtoweb.com
 * Description:       Scores every GeoDirectory listing 0-100 based on completeness and quality, with actionable recommendations.
 * Version:           0.1.0
 * Author:            AddictedToWeb
 * Author URI:        https://addictedtoweb.com
 * License:           GPL-2.0-or-later
 * Text Domain:       listing-health-score
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LHS_VERSION', '0.1.0' );
define( 'LHS_FILE', __FILE__ );
define( 'LHS_DIR', plugin_dir_path( __FILE__ ) );
define( 'LHS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Boot the plugin once all plugins are loaded so we can detect GeoDirectory.
 */
function lhs_init() {
	if ( ! class_exists( 'GeoDirectory' ) ) {
		add_action( 'admin_notices', 'lhs_missing_gd_notice' );
		return;
	}

	require_once LHS_DIR . 'includes/class-lhs-criteria.php';
	require_once LHS_DIR . 'includes/class-lhs-scorer.php';
	require_once LHS_DIR . 'includes/class-lhs-hooks.php';

	LHS_Hooks::init();

	if ( is_admin() ) {
		require_once LHS_DIR . 'includes/class-lhs-admin-column.php';
		LHS_Admin_Column::init();
	}
}
add_action( 'plugins_loaded', 'lhs_init', 20 );

/**
 * Admin notice when GeoDirectory is not active.
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
register_deactivation_hook( __FILE__, 'lhs_deactivate' );
