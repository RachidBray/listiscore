<?php
/**
 * Admin list table column.
 *
 * Adds a sortable "Health" column with a colored badge to every
 * GeoDirectory CPT list table. Instant visual feedback for admins.
 *
 * @package ListiScore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ListiScore_Admin_Column class.
 */
class ListiScore_Admin_Column {

	/**
	 * Register the column, its sorting, and its styles on every GD CPT list table.
	 */
	public static function init() {
		foreach ( geodir_get_posttypes() as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( __CLASS__, 'add_column' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( __CLASS__, 'render_column' ), 10, 2 );
			add_filter( "manage_edit-{$post_type}_sortable_columns", array( __CLASS__, 'sortable_column' ) );
		}

		add_action( 'pre_get_posts', array( __CLASS__, 'handle_sorting' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'badge_styles' ) );
	}

	/**
	 * Add the Health column.
	 *
	 * @param string[] $columns List table columns.
	 * @return string[]
	 */
	public static function add_column( $columns ) {
		$columns['listiscore_score'] = __( 'Health', 'listiscore' );
		return $columns;
	}

	/**
	 * Render the Health column content.
	 *
	 * @param string $column  Column key being rendered.
	 * @param int    $post_id Listing post ID.
	 */
	public static function render_column( $column, $post_id ) {
		if ( 'listiscore_score' !== $column ) {
			return;
		}

		$score = ListiScore_Scorer::get_score( $post_id );
		if ( false === $score ) {
			echo '&mdash;';
			return;
		}

		$band = ListiScore_Scorer::get_band( $score );
		printf(
			'<span class="listiscore-badge listiscore-badge--%1$s">%2$d</span>',
			esc_attr( $band ),
			(int) $score
		);
	}

	/**
	 * Mark the Health column as sortable.
	 *
	 * @param string[] $columns Sortable columns.
	 * @return string[]
	 */
	public static function sortable_column( $columns ) {
		$columns['listiscore_score'] = 'listiscore_score';
		return $columns;
	}

	/**
	 * Sort the list table by score when requested.
	 *
	 * @param WP_Query $query Main query.
	 */
	public static function handle_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( 'listiscore_score' !== $query->get( 'orderby' ) ) {
			return;
		}

		$query->set( 'meta_key', ListiScore_Scorer::META_SCORE ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- intentional: sorts the admin list table by our own score meta.
		$query->set( 'orderby', 'meta_value_num' );
	}

	/**
	 * Enqueue the badge color styles on GD CPT admin screens.
	 */
	public static function badge_styles() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, geodir_get_posttypes(), true ) ) {
			return;
		}

		wp_register_style( 'listiscore-admin-column', false, array(), LISTISCORE_VERSION );
		wp_enqueue_style( 'listiscore-admin-column' );

		wp_add_inline_style(
			'listiscore-admin-column',
			'.listiscore-badge {
				display: inline-block;
				min-width: 34px;
				padding: 2px 8px;
				border-radius: 12px;
				font-weight: 600;
				text-align: center;
				color: #fff;
			}
			.listiscore-badge--good { background: #00a32a; }
			.listiscore-badge--ok   { background: #dba617; }
			.listiscore-badge--poor { background: #d63638; }'
		);
	}
}
