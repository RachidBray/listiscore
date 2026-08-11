<?php
/**
 * Settings storage.
 *
 * Everything configurable lives in a single `listiscore_settings` option: score band
 * thresholds, scaling targets, and per-criterion enable/weight overrides.
 * A `version` counter is bumped on every save so `ListiScore_Scorer` knows to
 * recalculate listings that were scored under an older configuration.
 *
 * @package ListiScore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ListiScore_Settings class.
 */
class ListiScore_Settings {

	/**
	 * The option name settings are stored under.
	 *
	 * @var string
	 */
	const OPTION = 'listiscore_settings';

	/**
	 * Register filters that let saved settings override the criteria
	 * registry's built-in target/threshold defaults.
	 */
	public static function init() {
		$filter_map = array(
			'listiscore_description_target_length' => 'description_target_length',
			'listiscore_photos_target_count'       => 'photos_target_count',
			'listiscore_reviews_target_count'      => 'reviews_target_count',
			'listiscore_social_target_count'       => 'social_target_count',
			'listiscore_freshness_max_days'        => 'freshness_max_days',
			'listiscore_band_good_threshold'       => 'band_good',
			'listiscore_band_ok_threshold'         => 'band_ok',
		);

		foreach ( $filter_map as $filter => $key ) {
			add_filter(
				$filter,
				function ( $fallback ) use ( $key ) {
					return (int) self::get( $key, $fallback );
				}
			);
		}
	}

	/**
	 * Default settings, excluding criteria overrides (those come from
	 * `ListiScore_Criteria::get_defaults()` and are only ever partially overridden).
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'version'                   => 1,
			'band_good'                 => 80,
			'band_ok'                   => 50,
			'description_target_length' => 300,
			'photos_target_count'       => 5,
			'reviews_target_count'      => 3,
			'social_target_count'       => 2,
			'freshness_max_days'        => 180,
			'criteria'                  => array(),
		);
	}

	/**
	 * Get all settings, saved values merged over the defaults.
	 *
	 * @return array
	 */
	public static function get_all() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Get a single top-level setting.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Fallback if not set.
	 * @return mixed
	 */
	public static function get( $key, $fallback = null ) {
		$settings = self::get_all();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $fallback;
	}

	/**
	 * Get the current settings version, bumped on every save.
	 *
	 * @return int
	 */
	public static function get_version() {
		return (int) self::get( 'version', 1 );
	}

	/**
	 * Save settings, bumping the version so cached scores are invalidated.
	 *
	 * @param array $data New settings values (need not include every key).
	 */
	public static function update( array $data ) {
		$current = get_option( self::OPTION, array() );
		if ( ! is_array( $current ) ) {
			$current = array();
		}

		$data['version'] = self::get_version() + 1;

		update_option( self::OPTION, array_merge( $current, $data ) );
	}
}
