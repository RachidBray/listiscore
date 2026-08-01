<?php
/**
 * Admin list table column.
 *
 * Adds a sortable "Health" column with a colored badge to every
 * GeoDirectory CPT list table. Instant visual feedback for admins.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LHS_Admin_Column {

	public static function init() {
		foreach ( geodir_get_posttypes() as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( __CLASS__, 'add_column' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( __CLASS__, 'render_column' ), 10, 2 );
			add_filter( "manage_edit-{$post_type}_sortable_columns", array( __CLASS__, 'sortable_column' ) );
		}

		add_action( 'pre_get_posts', array( __CLASS__, 'handle_sorting' ) );
		add_action( 'admin_head', array( __CLASS__, 'badge_styles' ) );
	}

	public static function add_column( $columns ) {
		$columns['lhs_score'] = __( 'Health', 'listing-health-score' );
		return $columns;
	}

	public static function render_column( $column, $post_id ) {
		if ( 'lhs_score' !== $column ) {
			return;
		}

		$score = LHS_Scorer::get_score( $post_id );
		if ( false === $score ) {
			echo '&mdash;';
			return;
		}

		$band = LHS_Scorer::get_band( $score );
		printf(
			'<span class="lhs-badge lhs-badge--%1$s">%2$d</span>',
			esc_attr( $band ),
			(int) $score
		);
	}

	public static function sortable_column( $columns ) {
		$columns['lhs_score'] = 'lhs_score';
		return $columns;
	}

	public static function handle_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( 'lhs_score' !== $query->get( 'orderby' ) ) {
			return;
		}

		$query->set( 'meta_key', LHS_Scorer::META_SCORE );
		$query->set( 'orderby', 'meta_value_num' );
	}

	public static function badge_styles() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, geodir_get_posttypes(), true ) ) {
			return;
		}
		?>
		<style>
			.lhs-badge {
				display: inline-block;
				min-width: 34px;
				padding: 2px 8px;
				border-radius: 12px;
				font-weight: 600;
				text-align: center;
				color: #fff;
			}
			.lhs-badge--good { background: #00a32a; }
			.lhs-badge--ok   { background: #dba617; }
			.lhs-badge--poor { background: #d63638; }
		</style>
		<?php
	}
}
