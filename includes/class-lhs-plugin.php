<?php
/**
 * Main Listing Health Score class.
 *
 * @since 0.2.0
 * @package Listing_Health_Score
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LHS_Plugin class.
 */
final class LHS_Plugin {

	/**
	 * The single instance of the class.
	 *
	 * @var LHS_Plugin
	 */
	private static $instance = null;

	/**
	 * Main LHS_Plugin instance.
	 *
	 * Ensures only one instance of the plugin is loaded or can be loaded.
	 *
	 * @since 0.2.0
	 * @return LHS_Plugin
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();

			// Belt and suspenders: `geodirectory_loaded` only fires once GD
			// core exists, but guard here too in case anything ever calls
			// this directly.
			if ( ! class_exists( 'GeoDirectory' ) ) {
				return self::$instance;
			}

			self::$instance->includes();
			self::$instance->init_hooks();

			/**
			 * Fires once the plugin has finished loading its includes and
			 * registering its hooks.
			 */
			do_action( 'lhs_loaded' );
		}

		return self::$instance;
	}

	/**
	 * Include required files.
	 *
	 * @since 0.2.0
	 */
	private function includes() {
		require_once LHS_DIR . 'includes/class-lhs-settings.php';
		require_once LHS_DIR . 'includes/class-lhs-criteria.php';
		require_once LHS_DIR . 'includes/class-lhs-scorer.php';
		require_once LHS_DIR . 'includes/class-lhs-hooks.php';
		require_once LHS_DIR . 'includes/widgets/class-lhs-widget-health-score.php';

		if ( is_admin() ) {
			require_once LHS_DIR . 'includes/class-lhs-admin-column.php';
			require_once LHS_DIR . 'includes/class-lhs-admin-settings.php';
		}
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 0.2.0
	 */
	private function init_hooks() {
		LHS_Settings::init();
		LHS_Hooks::init();
		add_filter( 'geodir_get_widgets', array( 'LHS_Widget_Health_Score', 'register' ) );

		if ( is_admin() ) {
			LHS_Admin_Column::init();
			LHS_Admin_Settings::init();
		}
	}
}
