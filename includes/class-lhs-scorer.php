<?php
/**
 * Scoring engine.
 *
 * Calculates a 0-100 score from the criteria registry, stores the total and
 * the per-criterion breakdown as post meta so dashboards never need to
 * recalculate on read.
 *
 * @package Listing_Health_Score
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LHS_Scorer class.
 */
class LHS_Scorer {

	const META_SCORE            = '_lhs_score';
	const META_BREAKDOWN        = '_lhs_breakdown';
	const META_UPDATED          = '_lhs_calculated_at';
	const META_SETTINGS_VERSION = '_lhs_settings_version';

	/**
	 * Calculate and persist the score for a listing.
	 *
	 * @param int $post_id Listing post ID.
	 * @return int|false Score 0-100, or false if not a GD listing.
	 */
	public static function calculate( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || ! self::is_gd_listing( $post_id ) ) {
			return false;
		}

		$gd_post = geodir_get_post_info( $post_id );
		if ( ! $gd_post ) {
			return false;
		}

		$criteria     = LHS_Criteria::get_all();
		$total_weight = 0;
		$earned       = 0;
		$breakdown    = array();

		foreach ( $criteria as $id => $criterion ) {
			$weight = max( 0, (int) $criterion['weight'] );
			if ( 0 === $weight || ! is_callable( $criterion['check'] ) ) {
				continue;
			}

			$fraction = (float) call_user_func( $criterion['check'], $gd_post, $post_id );
			$fraction = max( 0.0, min( 1.0, $fraction ) );

			$total_weight += $weight;
			$earned       += $weight * $fraction;

			$breakdown[ $id ] = array(
				'label'    => $criterion['label'],
				'weight'   => $weight,
				'fraction' => round( $fraction, 3 ),
				'points'   => round( $weight * $fraction, 1 ),
				'tip'      => ( $fraction < 1 && ! empty( $criterion['tip'] ) ) ? $criterion['tip'] : '',
			);
		}

		$score = $total_weight > 0 ? (int) round( ( $earned / $total_weight ) * 100 ) : 0;

		/**
		 * Filter the final score before it is saved.
		 *
		 * @param int   $score     Score 0-100.
		 * @param int   $post_id   Listing ID.
		 * @param array $breakdown Per-criterion breakdown.
		 */
		$score = (int) apply_filters( 'lhs_final_score', $score, $post_id, $breakdown );
		$score = max( 0, min( 100, $score ) );

		update_post_meta( $post_id, self::META_SCORE, $score );
		update_post_meta( $post_id, self::META_BREAKDOWN, $breakdown );
		update_post_meta( $post_id, self::META_UPDATED, time() );
		update_post_meta( $post_id, self::META_SETTINGS_VERSION, LHS_Settings::get_version() );

		/**
		 * Fires after a listing score has been recalculated.
		 *
		 * @param int   $post_id   Listing ID.
		 * @param int   $score     New score.
		 * @param array $breakdown Per-criterion breakdown.
		 */
		do_action( 'lhs_score_updated', $post_id, $score, $breakdown );

		return $score;
	}

	/**
	 * Get the stored score, calculating it lazily if missing or if it was
	 * calculated under settings that have since changed.
	 *
	 * @param int $post_id Listing post ID.
	 * @return int|false
	 */
	public static function get_score( $post_id ) {
		$score = get_post_meta( $post_id, self::META_SCORE, true );

		if ( '' === $score || self::is_stale( $post_id ) ) {
			return self::calculate( $post_id );
		}
		return (int) $score;
	}

	/**
	 * Whether a listing's stored score was calculated under an older
	 * version of the Health Score settings.
	 *
	 * @param int $post_id Listing post ID.
	 * @return bool
	 */
	public static function is_stale( $post_id ) {
		$stored_version = get_post_meta( $post_id, self::META_SETTINGS_VERSION, true );
		return LHS_Settings::get_version() !== (int) $stored_version;
	}

	/**
	 * Get the stored breakdown for owner recommendations.
	 *
	 * @param int $post_id Listing post ID.
	 * @return array
	 */
	public static function get_breakdown( $post_id ) {
		$breakdown = get_post_meta( $post_id, self::META_BREAKDOWN, true );
		return is_array( $breakdown ) ? $breakdown : array();
	}

	/**
	 * Get actionable tips sorted by potential point gain (biggest wins first).
	 *
	 * @param int $post_id Listing post ID.
	 * @return array[] Each item: label, tip, potential_points.
	 */
	public static function get_recommendations( $post_id ) {
		$breakdown = self::get_breakdown( $post_id );
		$tips      = array();

		foreach ( $breakdown as $id => $item ) {
			if ( empty( $item['tip'] ) ) {
				continue;
			}
			$tips[] = array(
				'id'               => $id,
				'label'            => $item['label'],
				'tip'              => $item['tip'],
				'potential_points' => round( $item['weight'] - $item['points'], 1 ),
			);
		}

		usort(
			$tips,
			function ( $a, $b ) {
				return $b['potential_points'] <=> $a['potential_points'];
			}
		);

		return $tips;
	}

	/**
	 * Whether a post is a GeoDirectory listing.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_gd_listing( $post_id ) {
		if ( ! function_exists( 'geodir_get_posttypes' ) ) {
			return false;
		}
		return in_array( get_post_type( $post_id ), geodir_get_posttypes(), true );
	}

	/**
	 * Health band for UI badges.
	 *
	 * @param int $score Score 0-100.
	 * @return string good|ok|poor
	 */
	public static function get_band( $score ) {
		/**
		 * Filter the "good" band threshold.
		 *
		 * @param int $threshold Minimum score for the "good" band.
		 */
		$good = (int) apply_filters( 'lhs_band_good_threshold', 80 );

		/**
		 * Filter the "ok" band threshold.
		 *
		 * @param int $threshold Minimum score for the "ok" band.
		 */
		$ok = (int) apply_filters( 'lhs_band_ok_threshold', 50 );

		if ( $score >= $good ) {
			return 'good';
		}
		if ( $score >= $ok ) {
			return 'ok';
		}
		return 'poor';
	}
}
