<?php
/**
 * Injects the Health Score tab into GeoDirectory's settings UI.
 *
 * @package ListiScore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ListiScore_Admin_Settings class.
 */
class ListiScore_Admin_Settings {

	/**
	 * Hook the settings page into GD's settings pages list.
	 */
	public static function init() {
		add_filter( 'geodir_get_settings_pages', array( __CLASS__, 'add_settings_page' ) );
	}

	/**
	 * Append our settings page.
	 *
	 * @param GeoDir_Settings_Page[] $settings_pages Registered settings pages.
	 * @return GeoDir_Settings_Page[]
	 */
	public static function add_settings_page( $settings_pages ) {
		$settings_pages[] = include LISTISCORE_DIR . 'includes/admin/settings/class-listiscore-settings-health-score.php';

		return $settings_pages;
	}
}
