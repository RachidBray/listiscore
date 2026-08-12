<?php
/**
 * Health Score settings tab.
 *
 * @package ListiScore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ListiScore_Settings_Health_Score class.
 */
class ListiScore_Settings_Health_Score extends GeoDir_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'health_score';
		$this->label = __( 'Health Score', 'listiscore' );

		parent::__construct();

		// Fired by GeoDir_Admin_Settings::output_fields() right before/after
		// the Criteria section's fields, since 'id' => 'listiscore_criteria_options'.
		add_action( 'geodir_settings_listiscore_criteria_options', array( $this, 'render_weight_total' ) );
		add_action( 'geodir_settings_listiscore_criteria_options_end', array( $this, 'render_weight_total_script' ) );
	}

	/**
	 * Get the settings fields for this tab.
	 *
	 * @param string $current_section Current section (unused, this tab has only one).
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		$current  = ListiScore_Settings::get_all();
		$criteria = ListiScore_Criteria::get_defaults();

		$settings   = array();
		$settings[] = array(
			'name' => __( 'Score Bands', 'listiscore' ),
			'type' => 'title',
			'desc' => __( 'Thresholds used to color-code the Health column and owner dashboards.', 'listiscore' ),
			'id'   => 'listiscore_band_options',
		);
		$settings[] = array(
			'name'              => __( '"Good" threshold', 'listiscore' ),
			'desc'              => __( 'Scores at or above this value are shown as good.', 'listiscore' ),
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
			'name'              => __( '"OK" threshold', 'listiscore' ),
			'desc'              => __( 'Scores at or above this value (and below "Good") are shown as OK. Anything lower is "poor".', 'listiscore' ),
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
			'id'   => 'listiscore_band_options',
		);

		$settings[] = array(
			'name' => __( 'Targets', 'listiscore' ),
			'type' => 'title',
			'desc' => __( 'Targets used to calculate partial credit for criteria that scale, such as description length or photo count.', 'listiscore' ),
			'id'   => 'listiscore_target_options',
		);
		$settings[] = array(
			'name'     => __( 'Description target length', 'listiscore' ),
			'desc'     => __( 'Character count that earns full credit for the description criterion.', 'listiscore' ),
			'id'       => 'description_target_length',
			'type'     => 'number',
			'value'    => $current['description_target_length'],
			'default'  => 300,
			'desc_tip' => true,
		);
		$settings[] = array(
			'name'     => __( 'Photos target count', 'listiscore' ),
			'desc'     => __( 'Number of gallery photos that earns full credit.', 'listiscore' ),
			'id'       => 'photos_target_count',
			'type'     => 'number',
			'value'    => $current['photos_target_count'],
			'default'  => 5,
			'desc_tip' => true,
		);
		$settings[] = array(
			'name'     => __( 'Reviews target count', 'listiscore' ),
			'desc'     => __( 'Number of reviews that earns full credit.', 'listiscore' ),
			'id'       => 'reviews_target_count',
			'type'     => 'number',
			'value'    => $current['reviews_target_count'],
			'default'  => 3,
			'desc_tip' => true,
		);
		$settings[] = array(
			'name'     => __( 'Social links target count', 'listiscore' ),
			'desc'     => __( 'Number of connected social profiles that earns full credit.', 'listiscore' ),
			'id'       => 'social_target_count',
			'type'     => 'number',
			'value'    => $current['social_target_count'],
			'default'  => 2,
			'desc_tip' => true,
		);
		$settings[] = array(
			'name'     => __( 'Freshness max days', 'listiscore' ),
			'desc'     => __( 'Days since the last update after which the freshness criterion decays to zero.', 'listiscore' ),
			'id'       => 'freshness_max_days',
			'type'     => 'number',
			'value'    => $current['freshness_max_days'],
			'default'  => 180,
			'desc_tip' => true,
		);
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'listiscore_target_options',
		);

		$settings[] = array(
			'name' => __( 'Criteria', 'listiscore' ),
			'type' => 'title',
			/* translators: %d: number that enabled criteria weights must add up to (always 100). */
			'desc' => sprintf( __( 'Enable or disable individual criteria and set what percentage of the score each is worth. Enabled criteria must add up to exactly %d.', 'listiscore' ), 100 ),
			'id'   => 'listiscore_criteria_options',
		);

		foreach ( $criteria as $id => $criterion ) {
			$override = isset( $current['criteria'][ $id ] ) ? $current['criteria'][ $id ] : array();
			$enabled  = isset( $override['enabled'] ) ? (bool) $override['enabled'] : true;
			$weight   = isset( $override['weight'] ) ? (int) $override['weight'] : (int) $criterion['weight'];

			$settings[] = array(
				/* translators: %s: criterion label, e.g. "Featured image". */
				'name'              => sprintf( __( 'Enable: %s', 'listiscore' ), $criterion['label'] ),
				'id'                => "criteria[{$id}][enabled]",
				'type'              => 'checkbox',
				'value'             => $enabled ? '1' : '',
				'default'           => '1',
				'custom_attributes' => array( 'data-listiscore-role' => 'enabled' ),
			);
			$settings[] = array(
				/* translators: %s: criterion label, e.g. "Featured image". */
				'name'              => sprintf( __( '%s (%% of score)', 'listiscore' ), $criterion['label'] ),
				'desc'              => __( 'Percentage of the total score this criterion is worth when fully met.', 'listiscore' ),
				'id'                => "criteria[{$id}][weight]",
				'type'              => 'number',
				'value'             => $weight,
				'default'           => (int) $criterion['weight'],
				'desc_tip'          => true,
				'custom_attributes' => array(
					'min'                  => 0,
					'max'                  => 100,
					'data-listiscore-role' => 'weight',
				),
			);
		}

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'listiscore_criteria_options',
		);

		/**
		 * Filter the Health Score settings fields.
		 *
		 * @param array  $settings        Settings fields.
		 * @param string $current_section Current section.
		 */
		return apply_filters( 'listiscore_settings_fields', $settings, $current_section );
	}

	/**
	 * Output the settings fields.
	 *
	 * GD's own field renderer (`GeoDir_Admin_Settings::output_fields()`) is
	 * reused for AyeCode-native markup, but each field carries a precomputed
	 * `value` read from our own `listiscore_settings` option instead of GD's shared
	 * `geodir_settings` option.
	 */
	public function output() {
		global $current_section;

		GeoDir_Admin_Settings::output_fields( $this->get_settings( $current_section ) );
	}

	/**
	 * Render the running "total weight of enabled criteria" indicator above
	 * the per-criterion fields. Static markup here; render_weight_total_script()
	 * fills in the live value once the fields below it exist in the DOM.
	 */
	public function render_weight_total() {
		?>
		<p id="listiscore-weight-total-wrap" style="font-weight: 600;">
			<?php esc_html_e( 'Total (enabled criteria):', 'listiscore' ); ?>
			<span id="listiscore-weight-total">&hellip;</span>%
		</p>
		<?php
	}

	/**
	 * Script that keeps the total indicator in sync as criteria are toggled
	 * or reweighted, colored using the same validated status hexes as the
	 * front-end widget (good/critical — see ListiScore_Widget_Health_Score).
	 *
	 * Placed at the *_end hook (after the fields render) so the elements it
	 * queries for already exist by the time this script tag is reached.
	 */
	public function render_weight_total_script() {
		wp_register_script( 'listiscore-admin-settings', false, array(), LISTISCORE_VERSION, true );
		wp_enqueue_script( 'listiscore-admin-settings' );

		wp_add_inline_script(
			'listiscore-admin-settings',
			'( function () {
				var wrap  = document.getElementById( "listiscore-weight-total-wrap" );
				var total = document.getElementById( "listiscore-weight-total" );
				if ( ! wrap || ! total ) {
					return;
				}

				var enabled = document.querySelectorAll( \'[data-listiscore-role="enabled"]\' );
				var weights = document.querySelectorAll( \'[data-listiscore-role="weight"]\' );

				function recalc() {
					var sum = 0;
					for ( var i = 0; i < weights.length; i++ ) {
						if ( ! enabled[ i ] || enabled[ i ].checked ) {
							sum += parseInt( weights[ i ].value, 10 ) || 0;
						}
					}
					total.textContent = sum;
					wrap.style.color = ( 100 === sum ) ? "#0ca30c" : "#d03b3b";
				}

				for ( var i = 0; i < weights.length; i++ ) {
					weights[ i ].addEventListener( "input", recalc );
				}
				for ( var i = 0; i < enabled.length; i++ ) {
					enabled[ i ].addEventListener( "change", recalc );
				}

				recalc();
			} )();'
		);
	}

	/**
	 * Save the settings fields into the `listiscore_settings` option.
	 *
	 * Not routed through `GeoDir_Admin_Settings::save_fields()` because that
	 * always persists into GD's shared `geodir_settings` option; we need our
	 * own option so Health Score settings stay self-contained.
	 *
	 * Rejects the entire save (nothing is persisted, including the band and
	 * target fields from the same submission) if enabled criteria don't add
	 * up to exactly 100 — a partial/inconsistent save would silently break
	 * the widget's percentage display, which assumes the total is 100.
	 *
	 * Setting `$geodir_settings_error` is GD's own native mechanism for this
	 * exact situation (`GeoDir_Admin_Settings::save()` checks it right after
	 * firing this action): it makes GD show our error via its own
	 * `show_messages()` — the same box "Your settings have been saved."
	 * would otherwise occupy — and, critically, *skips* that success message
	 * entirely. Without this, GD has no way to know our save rejected
	 * anything and tells the admin it saved regardless.
	 *
	 * Nonce + capability: this method only ever runs via the
	 * `geodir_settings_save_{$this->id}` action, which `GeoDir_Admin_Settings`
	 * fires from `output()` -- itself only reachable through the `gd-settings`
	 * submenu page, which WP core already gates on
	 * `admin_menu_capability( 'gd-settings' )` (`manage_options` by default)
	 * before `output()` ever runs. `GeoDir_Admin_Settings::save()` then
	 * verifies the `geodirectory-settings` nonce itself and `die()`s before
	 * this action fires at all. The explicit check below is deliberate
	 * defense-in-depth on top of that, not a substitute for it.
	 */
	public function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

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

		$total_weight = 0;

		foreach ( ListiScore_Criteria::get_defaults() as $id => $criterion ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce ('geodirectory-settings') already verified by GeoDir_Admin_Settings::save() before geodir_settings_save_{id} fires.
			$enabled = ! empty( $_POST['criteria'][ $id ]['enabled'] );

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce ('geodirectory-settings') already verified by GeoDir_Admin_Settings::save() before geodir_settings_save_{id} fires.
			$weight = isset( $_POST['criteria'][ $id ]['weight'] ) ? absint( wp_unslash( $_POST['criteria'][ $id ]['weight'] ) ) : (int) $criterion['weight'];
			$weight = max( 0, $weight );

			$data['criteria'][ $id ] = array(
				'enabled' => $enabled ? 1 : 0,
				'weight'  => $weight,
			);

			if ( $enabled ) {
				$total_weight += $weight;
			}
		}

		if ( 100 !== $total_weight ) {
			global $geodir_settings_error;

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- GeoDirectory core's own global, not ours to define; see the docblock above for why we set it.
			$geodir_settings_error = sprintf(
				/* translators: %d: the total the admin actually submitted. */
				__( 'Health Score settings were not saved: enabled criteria must add up to exactly 100%% of the score (currently %d%%). Adjust the weights and save again.', 'listiscore' ),
				$total_weight
			);

			return;
		}

		ListiScore_Settings::update( $data );
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

return new ListiScore_Settings_Health_Score();
