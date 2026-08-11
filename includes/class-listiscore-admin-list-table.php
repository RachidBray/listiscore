<?php
/**
 * Admin list table tools: filter listings by health band, and bulk
 * recalculate scores for selected listings.
 *
 * Kept separate from ListiScore_Admin_Column, which only owns the Health column
 * itself — these two hooks are a distinct concern (query filtering / bulk
 * actions) even though they touch the same list tables.
 *
 * @package ListiScore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ListiScore_Admin_List_Table class.
 */
class ListiScore_Admin_List_Table {

	/**
	 * Bulk action slug.
	 *
	 * @var string
	 */
	const BULK_ACTION = 'listiscore_recalculate';

	/**
	 * Register hooks for every GD CPT list table.
	 */
	public static function init() {
		foreach ( geodir_get_posttypes() as $post_type ) {
			add_filter( "bulk_actions-edit-{$post_type}", array( __CLASS__, 'add_bulk_action' ) );
			add_filter( "handle_bulk_actions-edit-{$post_type}", array( __CLASS__, 'handle_bulk_action' ), 10, 3 );
		}

		add_action( 'restrict_manage_posts', array( __CLASS__, 'render_band_filter' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_by_band' ) );
		add_action( 'admin_notices', array( __CLASS__, 'bulk_action_notice' ) );
	}

	/**
	 * Render the health band filter dropdown above a GD CPT list table.
	 *
	 * @param string $post_type Post type of the current list table.
	 */
	public static function render_band_filter( $post_type ) {
		if ( ! in_array( $post_type, geodir_get_posttypes(), true ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view filter, matches WP core's own list table dropdowns (category, post status, etc), none of which use a nonce.
		$selected = isset( $_GET['listiscore_band'] ) ? sanitize_key( wp_unslash( $_GET['listiscore_band'] ) ) : '';

		$bands = self::bands();
		?>
		<select name="listiscore_band">
			<option value=""><?php esc_html_e( 'All health bands', 'listiscore' ); ?></option>
			<?php foreach ( $bands as $band => $label ) : ?>
				<option value="<?php echo esc_attr( $band ); ?>" <?php selected( $selected, $band ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Narrow the admin list table query to a health band, if one is selected.
	 *
	 * @param WP_Query $query Main query.
	 */
	public static function filter_by_band( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view filter, matches WP core's own list table dropdowns.
		$band = isset( $_GET['listiscore_band'] ) ? sanitize_key( wp_unslash( $_GET['listiscore_band'] ) ) : '';

		if ( ! isset( self::bands()[ $band ] ) ) {
			return;
		}

		if ( ! in_array( (string) $query->get( 'post_type' ), geodir_get_posttypes(), true ) ) {
			return;
		}

		/**
		 * Filter the "good" band threshold.
		 *
		 * @param int $threshold Minimum score for the "good" band.
		 */
		$good = (int) apply_filters( 'listiscore_band_good_threshold', 80 );

		/**
		 * Filter the "ok" band threshold.
		 *
		 * @param int $threshold Minimum score for the "ok" band.
		 */
		$ok = (int) apply_filters( 'listiscore_band_ok_threshold', 50 );

		switch ( $band ) {
			case 'good':
				$meta_query = array(
					'key'     => ListiScore_Scorer::META_SCORE,
					'value'   => $good,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				);
				break;
			case 'ok':
				$meta_query = array(
					'key'     => ListiScore_Scorer::META_SCORE,
					'value'   => array( $ok, max( $ok, $good - 1 ) ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				);
				break;
			default: // poor.
				$meta_query = array(
					'key'     => ListiScore_Scorer::META_SCORE,
					'value'   => $ok,
					'compare' => '<',
					'type'    => 'NUMERIC',
				);
				break;
		}

		$query->set( 'meta_query', array( $meta_query ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- intentional: this is exactly what the admin band filter is for.
	}

	/**
	 * Add the "Recalculate Health Score" bulk action.
	 *
	 * @param string[] $actions Existing bulk actions, keyed by slug.
	 * @return string[]
	 */
	public static function add_bulk_action( $actions ) {
		$actions[ self::BULK_ACTION ] = __( 'Recalculate Health Score', 'listiscore' );
		return $actions;
	}

	/**
	 * Recalculate the score for every selected listing the current user is
	 * actually allowed to edit.
	 *
	 * WP core's own bulk-action handling already verifies the request nonce
	 * and the current user's generic edit_posts capability before this ever
	 * runs, but that's a blanket "can edit posts of this type" check, not
	 * per-post -- it doesn't stop a lower-privileged user (e.g. one who can
	 * only edit their own listings) from including someone else's post ID in
	 * the bulk selection. Recalculating isn't destructive, but it does write
	 * post meta, so each post still needs its own capability check.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $action_name Bulk action name.
	 * @param int[]  $post_ids    Selected post IDs.
	 * @return string
	 */
	public static function handle_bulk_action( $redirect_to, $action_name, $post_ids ) {
		if ( self::BULK_ACTION !== $action_name ) {
			return $redirect_to;
		}

		$recalculated = 0;

		foreach ( $post_ids as $post_id ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}

			ListiScore_Scorer::calculate( $post_id );
			++$recalculated;
		}

		return add_query_arg( 'listiscore_recalculated', $recalculated, $redirect_to );
	}

	/**
	 * Show a confirmation notice after the bulk action runs.
	 */
	public static function bulk_action_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice trigger off a redirect query arg we set ourselves; matches WP core's own bulk-action notice pattern.
		if ( empty( $_GET['listiscore_recalculated'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice trigger, see above.
		$count = absint( wp_unslash( $_GET['listiscore_recalculated'] ) );

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of listings recalculated. */
					_n( 'Recalculated the health score for %d listing.', 'Recalculated the health score for %d listings.', $count, 'listiscore' ),
					$count
				)
			)
		);
	}

	/**
	 * Band slugs mapped to their filter dropdown label.
	 *
	 * @return array<string, string>
	 */
	private static function bands() {
		return array(
			'good' => __( 'Good', 'listiscore' ),
			'ok'   => __( 'Needs improvement', 'listiscore' ),
			'poor' => __( 'Poor', 'listiscore' ),
		);
	}
}
