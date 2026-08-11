<?php
/**
 * Main Listing Health Score class.
 *
 * @since 0.2.0
 * @package ListiScore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ListiScore_Plugin class.
 */
final class ListiScore_Plugin {

	/**
	 * The single instance of the class.
	 *
	 * @var ListiScore_Plugin
	 */
	private static $instance = null;

	/**
	 * Main ListiScore_Plugin instance.
	 *
	 * Ensures only one instance of the plugin is loaded or can be loaded.
	 *
	 * @since 0.2.0
	 * @return ListiScore_Plugin
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
			do_action( 'listiscore_loaded' );
		}

		return self::$instance;
	}

	/**
	 * Include required files.
	 *
	 * @since 0.2.0
	 */
	private function includes() {
		require_once LISTISCORE_DIR . 'includes/class-listiscore-settings.php';
		require_once LISTISCORE_DIR . 'includes/class-listiscore-criteria.php';
		require_once LISTISCORE_DIR . 'includes/class-listiscore-scorer.php';
		require_once LISTISCORE_DIR . 'includes/class-listiscore-hooks.php';
		require_once LISTISCORE_DIR . 'includes/widgets/class-listiscore-widget-health-score.php';

		if ( is_admin() ) {
			require_once LISTISCORE_DIR . 'includes/class-listiscore-admin-column.php';
			require_once LISTISCORE_DIR . 'includes/class-listiscore-admin-list-table.php';
			require_once LISTISCORE_DIR . 'includes/class-listiscore-admin-settings.php';
		}
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 0.2.0
	 */
	private function init_hooks() {
		ListiScore_Settings::init();
		ListiScore_Hooks::init();
		add_filter( 'geodir_get_widgets', array( 'ListiScore_Widget_Health_Score', 'register' ) );

		if ( is_admin() ) {
			ListiScore_Admin_Column::init();
			ListiScore_Admin_List_Table::init();
			ListiScore_Admin_Settings::init();
		}
	}
}
