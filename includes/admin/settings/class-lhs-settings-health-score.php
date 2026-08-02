<?php
/**
 * Health Score settings tab.
 *
 * @package Listing_Health_Score
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LHS_Settings_Health_Score class.
 */
class LHS_Settings_Health_Score extends GeoDir_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'health_score';
		$this->label = __( 'Health Score', 'listing-health-score' );

		parent::__construct();
	}

	/**
	 * Get the settings fields for this tab.
	 *
	 * @param string $current_section Current section (unused, this tab has only one).
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		$current  = LHS_Settings::get_all();
		$criteria = LHS_Criteria::get_defaults();

		$settings   = array();
		$settings[] = array(
			'name' => __( 'Score Bands', 'listing-health-score' ),
			'type' => 'title',
			'desc' => __( 'Thresholds used to color-code the Health column and owner dashboards.', 'listing-health-score' ),
			'id'   => 'lhs_band_options',
		);
		$settings[] = array(
			'name'              => __( '"Good" threshold', 'listing-health-score' ),
			'desc'              => __( 'Scores at or above this value are shown as good.', 'listing-health-score' ),
			'id'                => 'band_good',
			'type'              => 'number',
			'value'             => $current['band_good'],
			'default'           => 80,
			'desc_tip'          => true,
			'custom_attributes' => array(
				'min' => 0,
				'max' => 100,
			),
		);
		$settings[] = array(
			'name'              => __( '"OK" threshold', 'listing-health-score' ),
			'desc'              => __( 'Scores at or above this value (and below "Good") are shown as OK. Anything lower is "poor".', 'listing-health-score' ),
			'id'                => 'band_ok',
			'type'              => 'number',
			'value'             => $current['band_ok'],
			'default'           => 50,
			'desc_tip'          => true,
			'custom_attributes' => array(
				'min' => 0,
				'max' => 100,
			),
		);
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'lhs_band_options',
		);

		$settings[] = array(
			'name' => __( 'Targets', 'listing-health-score' ),
			'type' => 'title',
			'desc' => __( 'Targets used to calculate partial credit for criteria that scale, such as description length or photo count.', 'listing-health-score' ),
			'id'   => 'lhs_target_options',
		);
		$settings[] = array(
			'name'     => __( 'Description target length', 'listing-health-score' ),
			'desc'     => __( 'Character count that earns full credit for the description criterion.', 'listing-health-score' ),
			'id'       => 'description_target_length',
			'type'     => 'number',
			'value'    => $current['description_target_length'],
			'default'  => 300,
			'desc_tip' => true,
		);
		$settings[] = array(
			'name'     => __( 'Photos target count', 'listing-health-score' ),
			'desc'     => __( 'Number of gallery photos that earns full credit.', 'listing-health-score' ),
			'id'       => 'photos_target_count',
			'type'     => 'number',
			'value'    => $current['photos_target_count'],
			'default'  => 5,
			'desc_tip' => true,
		);
		$settings[] = array(
			'name'     => __( 'Reviews target count', 'listing-health-score' ),
			'desc'     => __( 'Number of reviews that earns full credit.', 'listing-health-score' ),
			'id'       => 'reviews_target_count',
			'type'     => 'number',
			'value'    => $current['reviews_target_count'],
			'default'  => 3,
			'desc_tip' => true,
		);
		$settings[] = array(
			'name'     => __( 'Social links target count', 'listing-health-score' ),
			'desc'     => __( 'Number of connected social profiles that earns full credit.', 'listing-health-score' ),
			'id'       => 'social_target_count',
			'type'     => 'number',
			'value'    => $current['social_target_count'],
			'default'  => 2,
			'desc_tip' => true,
		);
		$settings[] = array(
			'name'     => __( 'Freshness max days', 'listing-health-score' ),
			'desc'     => __( 'Days since the last update after which the freshness criterion decays to zero.', 'listing-health-score' ),
			'id'       => 'freshness_max_days',
			'type'     => 'number',
			'value'    => $current['freshness_max_days'],
			'default'  => 180,
			'desc_tip' => true,
		);
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'lhs_target_options',
		);

		$settings[] = array(
			'name' => __( 'Criteria', 'listing-health-score' ),
			'type' => 'title',
			'desc' => __( 'Enable or disable individual criteria and adjust how many points each is worth.', 'listing-health-score' ),
			'id'   => 'lhs_criteria_options',
		);

		foreach ( $criteria as $id => $criterion ) {
			$override = isset( $current['criteria'][ $id ] ) ? $current['criteria'][ $id ] : array();
			$enabled  = isset( $override['enabled'] ) ? (bool) $override['enabled'] : true;
			$weight   = isset( $override['weight'] ) ? (int) $override['weight'] : (int) $criterion['weight'];

			$settings[] = array(
				/* translators: %s: criterion label, e.g. "Featured image". */
				'name'    => sprintf( __( 'Enable: %s', 'listing-health-score' ), $criterion['label'] ),
				'id'      => "criteria[{$id}][enabled]",
				'type'    => 'checkbox',
				'value'   => $enabled ? '1' : '',
				'default' => '1',
			);
			$settings[] = array(
				/* translators: %s: criterion label, e.g. "Featured image". */
				'name'              => sprintf( __( '%s weight', 'listing-health-score' ), $criterion['label'] ),
				'desc'              => __( 'Points this criterion is worth when fully met.', 'listing-health-score' ),
				'id'                => "criteria[{$id}][weight]",
				'type'              => 'number',
				'value'             => $weight,
				'default'           => (int) $criterion['weight'],
				'desc_tip'          => true,
				'custom_attributes' => array( 'min' => 0 ),
			);
		}

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'lhs_criteria_options',
		);

		/**
		 * Filter the Health Score settings fields.
		 *
		 * @param array  $settings        Settings fields.
		 * @param string $current_section Current section.
		 */
		return apply_filters( 'lhs_settings_fields', $settings, $current_section );
	}

	/**
	 * Output the settings fields.
	 *
	 * GD's own field renderer (`GeoDir_Admin_Settings::output_fields()`) is
	 * reused for AyeCode-native markup, but each field carries a precomputed
	 * `value` read from our own `lhs_settings` option instead of GD's shared
	 * `geodir_settings` option.
	 */
	public function output() {
		global $current_section;

		GeoDir_Admin_Settings::output_fields( $this->get_settings( $current_section ) );
	}

	/**
	 * Save the settings fields into the `lhs_settings` option.
	 *
	 * Not routed through `GeoDir_Admin_Settings::save_fields()` because that
	 * always persists into GD's shared `geodir_settings` option; we need our
	 * own option so Health Score settings stay self-contained.
	 */
	public function save() {
		$data = array(
			'band_good'                 => $this->posted_int( 'band_good', 80, 0, 100 ),
			'band_ok'                   => $this->posted_int( 'band_ok', 50, 0, 100 ),
			'description_target_length' => $this->posted_int( 'description_target_length', 300 ),
			'photos_target_count'       => $this->posted_int( 'photos_target_count', 5 ),
			'reviews_target_count'      => $this->posted_int( 'reviews_target_count', 3 ),
			'social_target_count'       => $this->posted_int( 'social_target_count', 2 ),
			'freshness_max_days'        => $this->posted_int( 'freshness_max_days', 180 ),
			'criteria'                  => array(),
		);

		foreach ( LHS_Criteria::get_defaults() as $id => $criterion ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce ('geodirectory-settings') already verified by GeoDir_Admin_Settings::save() before geodir_settings_save_{id} fires.
			$enabled = ! empty( $_POST['criteria'][ $id ]['enabled'] );

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce ('geodirectory-settings') already verified by GeoDir_Admin_Settings::save() before geodir_settings_save_{id} fires.
			$weight = isset( $_POST['criteria'][ $id ]['weight'] ) ? absint( wp_unslash( $_POST['criteria'][ $id ]['weight'] ) ) : (int) $criterion['weight'];

			$data['criteria'][ $id ] = array(
				'enabled' => $enabled ? 1 : 0,
				'weight'  => max( 0, $weight ),
			);
		}

		LHS_Settings::update( $data );
	}

	/**
	 * Read and sanitize a posted integer field.
	 *
	 * @param string   $key      Field id / $_POST key.
	 * @param int      $fallback Fallback if not posted.
	 * @param int      $min      Minimum allowed value.
	 * @param int|null $max      Maximum allowed value, or null for none.
	 * @return int
	 */
	private function posted_int( $key, $fallback, $min = 0, $max = null ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce ('geodirectory-settings') already verified by GeoDir_Admin_Settings::save() before geodir_settings_save_{id} fires.
		if ( ! isset( $_POST[ $key ] ) ) {
			return $fallback;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce ('geodirectory-settings') already verified by GeoDir_Admin_Settings::save() before geodir_settings_save_{id} fires.
		$value = absint( wp_unslash( $_POST[ $key ] ) );

		if ( $value < $min ) {
			$value = $min;
		}
		if ( null !== $max && $value > $max ) {
			$value = $max;
		}

		return $value;
	}
}

return new LHS_Settings_Health_Score();
