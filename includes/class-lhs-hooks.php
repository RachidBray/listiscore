<?php
/**
 * Recalculation triggers.
 *
 * Keeps scores in sync when listings change, reviews come in, listings get
 * claimed, and runs a small daily batch so freshness decay stays accurate.
 *
 * @package Listing_Health_Score
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LHS_Hooks class.
 */
class LHS_Hooks {

	/**
	 * Register all recalculation hooks.
	 */
	public static function init() {
		// Listing saved via GD forms (frontend or backend).
		add_action( 'geodir_post_saved', array( __CLASS__, 'on_gd_post_saved' ), 10, 3 );

		// Fallback for direct wp-admin saves / programmatic updates.
		add_action( 'save_post', array( __CLASS__, 'on_save_post' ), 20, 2 );

		// Reviews affect the score.
		add_action( 'wp_insert_comment', array( __CLASS__, 'on_comment_insert' ), 10, 2 );
		add_action( 'transition_comment_status', array( __CLASS__, 'on_comment_status' ), 10, 3 );

		// Claim Listing addon events (fires when a claim is approved).
		add_action( 'geodir_claim_post_approved', array( __CLASS__, 'recalc' ), 10, 1 );

		// Daily batch recalc for freshness decay.
		add_action( 'lhs_daily_recalc', array( __CLASS__, 'daily_batch' ) );
		if ( ! wp_next_scheduled( 'lhs_daily_recalc' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'lhs_daily_recalc' );
		}
	}

	/**
	 * Recalculate when a listing is saved via GD forms.
	 *
	 * @param array   $postarr Raw post array.
	 * @param object  $gd_post GD post object.
	 * @param WP_Post $post    WP post object.
	 */
	public static function on_gd_post_saved( $postarr, $gd_post, $post ) {
		if ( isset( $post->ID ) ) {
			self::recalc( $post->ID );
		}
	}

	/**
	 * Fallback recalculation for direct wp-admin saves / programmatic updates.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function on_save_post( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( 'publish' !== $post->post_status ) {
			return;
		}
		if ( ! LHS_Scorer::is_gd_listing( $post_id ) ) {
			return;
		}
		// Defer to shutdown so GD detail table writes are done first.
		add_action(
			'shutdown',
			function () use ( $post_id ) {
				LHS_Scorer::calculate( $post_id );
			}
		);
	}

	/**
	 * Recalculate when a review comment is inserted.
	 *
	 * @param int        $comment_id Comment ID.
	 * @param WP_Comment $comment    Comment object.
	 */
	public static function on_comment_insert( $comment_id, $comment ) {
		if ( isset( $comment->comment_post_ID ) ) {
			self::maybe_recalc_for_comment( $comment->comment_post_ID );
		}
	}

	/**
	 * Recalculate when a review comment's status changes (e.g. approved/spammed).
	 *
	 * @param string     $new_status New comment status.
	 * @param string     $old_status Old comment status.
	 * @param WP_Comment $comment    Comment object.
	 */
	public static function on_comment_status( $new_status, $old_status, $comment ) {
		if ( isset( $comment->comment_post_ID ) ) {
			self::maybe_recalc_for_comment( $comment->comment_post_ID );
		}
	}

	/**
	 * Recalculate for a comment's parent post, if it's a GD listing.
	 *
	 * @param int $post_id Post ID.
	 */
	protected static function maybe_recalc_for_comment( $post_id ) {
		if ( LHS_Scorer::is_gd_listing( $post_id ) ) {
			self::recalc( $post_id );
		}
	}

	/**
	 * Recalculate a single listing's score.
	 *
	 * @param int $post_id Listing post ID.
	 */
	public static function recalc( $post_id ) {
		LHS_Scorer::calculate( $post_id );
	}

	/**
	 * Recalculate the stalest listings once a day so time-based criteria
	 * (freshness) decay properly without needing a save event.
	 */
	public static function daily_batch() {
		$batch_size = (int) apply_filters( 'lhs_daily_batch_size', 200 );

		$query = new WP_Query(
			array(
				'post_type'      => geodir_get_posttypes(),
				'post_status'    => 'publish',
				'posts_per_page' => $batch_size,
				'fields'         => 'ids',
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
				'meta_key'       => LHS_Scorer::META_UPDATED, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- intentional: finds the stalest listings, batched via lhs_daily_batch_size.
				'no_found_rows'  => true,
			)
		);

		foreach ( $query->posts as $post_id ) {
			LHS_Scorer::calculate( $post_id );
		}
	}
}
