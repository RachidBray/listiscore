<?php
/**
 * Criteria registry.
 *
 * Each criterion returns a fraction 0..1 (how complete it is) plus an
 * optional tip shown to listing owners when the fraction is below 1.
 *
 * Third parties can add/remove/reweight criteria via the `lhs_criteria` filter.
 *
 * @package Listing_Health_Score
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LHS_Criteria class.
 */
class LHS_Criteria {

	/**
	 * Get all registered criteria with saved settings overrides applied.
	 *
	 * Criteria disabled via settings are omitted entirely; enabled ones have
	 * their weight replaced with the saved override, if any.
	 *
	 * @return array[] Each item: label, weight (int), check (callable), tip (string).
	 */
	public static function get_all() {
		$criteria  = self::get_defaults();
		$overrides = LHS_Settings::get( 'criteria', array() );

		foreach ( array_keys( $criteria ) as $id ) {
			if ( empty( $overrides[ $id ] ) ) {
				continue;
			}

			if ( isset( $overrides[ $id ]['enabled'] ) && ! $overrides[ $id ]['enabled'] ) {
				unset( $criteria[ $id ] );
				continue;
			}

			if ( isset( $overrides[ $id ]['weight'] ) ) {
				$criteria[ $id ]['weight'] = max( 0, (int) $overrides[ $id ]['weight'] );
			}
		}

		/**
		 * Filter the health score criteria.
		 *
		 * @param array[] $criteria Criteria definitions keyed by id.
		 */
		return apply_filters( 'lhs_criteria', $criteria );
	}

	/**
	 * Get the built-in criteria defaults, with no settings overrides applied.
	 *
	 * Used by the scoring merge above and by the settings page, which needs
	 * to list every criterion (including disabled ones) so they can be
	 * re-enabled.
	 *
	 * @return array[] Each item: label, weight (int), check (callable), tip (string).
	 */
	public static function get_defaults() {
		$criteria = array(
			'featured_image' => array(
				'label'  => __( 'Featured image', 'listing-health-score' ),
				'weight' => 10,
				'check'  => array( __CLASS__, 'check_featured_image' ),
				'tip'    => __( 'Add a featured image to make your listing stand out in search results.', 'listing-health-score' ),
			),
			'logo'           => array(
				'label'  => __( 'Logo', 'listing-health-score' ),
				'weight' => 5,
				'check'  => array( __CLASS__, 'check_logo' ),
				'tip'    => __( 'Upload your business logo for better brand recognition.', 'listing-health-score' ),
			),
			'description'    => array(
				'label'  => __( 'Description', 'listing-health-score' ),
				'weight' => 15,
				'check'  => array( __CLASS__, 'check_description' ),
				'tip'    => __( 'Write a detailed description (300+ characters) covering your services, story and what makes you unique.', 'listing-health-score' ),
			),
			'business_hours' => array(
				'label'  => __( 'Opening hours', 'listing-health-score' ),
				'weight' => 8,
				'check'  => array( __CLASS__, 'check_business_hours' ),
				'tip'    => __( 'Add your opening hours so visitors know when you are available.', 'listing-health-score' ),
			),
			'phone'          => array(
				'label'  => __( 'Phone number', 'listing-health-score' ),
				'weight' => 8,
				'check'  => array( __CLASS__, 'check_phone' ),
				'tip'    => __( 'Add a phone number so customers can reach you directly.', 'listing-health-score' ),
			),
			'email'          => array(
				'label'  => __( 'Email address', 'listing-health-score' ),
				'weight' => 5,
				'check'  => array( __CLASS__, 'check_email' ),
				'tip'    => __( 'Add a contact email address.', 'listing-health-score' ),
			),
			'website'        => array(
				'label'  => __( 'Website', 'listing-health-score' ),
				'weight' => 7,
				'check'  => array( __CLASS__, 'check_website' ),
				'tip'    => __( 'Link your website to drive traffic and build trust.', 'listing-health-score' ),
			),
			'social'         => array(
				'label'  => __( 'Social links', 'listing-health-score' ),
				'weight' => 6,
				'check'  => array( __CLASS__, 'check_social' ),
				'tip'    => __( 'Connect at least two social profiles (Facebook, Instagram, X, LinkedIn).', 'listing-health-score' ),
			),
			'photos'         => array(
				'label'  => __( 'Photo gallery', 'listing-health-score' ),
				'weight' => 10,
				'check'  => array( __CLASS__, 'check_photos' ),
				'tip'    => __( 'Upload at least 5 photos. Listings with galleries get significantly more engagement.', 'listing-health-score' ),
			),
			'reviews'        => array(
				'label'  => __( 'Reviews', 'listing-health-score' ),
				'weight' => 10,
				'check'  => array( __CLASS__, 'check_reviews' ),
				'tip'    => __( 'Encourage customers to leave reviews. Aim for at least 3.', 'listing-health-score' ),
			),
			'claimed'        => array(
				'label'  => __( 'Claimed listing', 'listing-health-score' ),
				'weight' => 8,
				'check'  => array( __CLASS__, 'check_claimed' ),
				'tip'    => __( 'Claim this listing to verify ownership and unlock full management.', 'listing-health-score' ),
			),
			'freshness'      => array(
				'label'  => __( 'Recently updated', 'listing-health-score' ),
				'weight' => 8,
				'check'  => array( __CLASS__, 'check_freshness' ),
				'tip'    => __( 'Update your listing regularly. Fresh listings rank better and build visitor trust.', 'listing-health-score' ),
			),
		);

		return $criteria;
	}

	// Checks. Each receives ( $gd_post, $post_id ) and returns float 0..1.

	/**
	 * Check for a featured image.
	 *
	 * @param object $gd_post GeoDirectory post object.
	 * @param int    $post_id Listing post ID.
	 * @return float
	 */
	public static function check_featured_image( $gd_post, $post_id ) {
		if ( ! empty( $gd_post->featured_image ) || has_post_thumbnail( $post_id ) ) {
			return 1.0;
		}
		return 0.0;
	}

	/**
	 * Check for a business logo.
	 *
	 * @param object $gd_post GeoDirectory post object.
	 * @param int    $post_id Listing post ID.
	 * @return float
	 */
	public static function check_logo( $gd_post, $post_id ) {
		if ( ! empty( $gd_post->logo ) ) {
			return 1.0;
		}
		// Fallback: check GD attachments table for a logo type image.
		if ( class_exists( 'GeoDir_Media' ) ) {
			$logo = GeoDir_Media::get_attachments_by_type( $post_id, 'logo', 1 );
			if ( ! empty( $logo ) ) {
				return 1.0;
			}
		}
		return 0.0;
	}

	/**
	 * Check description length against the target.
	 *
	 * @param object $gd_post GeoDirectory post object.
	 * @param int    $post_id Listing post ID.
	 * @return float
	 */
	public static function check_description( $gd_post, $post_id ) {
		$content = get_post_field( 'post_content', $post_id );
		$length  = mb_strlen( trim( wp_strip_all_tags( $content ) ) );

		$target = (int) apply_filters( 'lhs_description_target_length', 300 );

		if ( $length <= 0 ) {
			return 0.0;
		}
		return min( 1.0, $length / $target );
	}

	/**
	 * Check for opening hours.
	 *
	 * @param object $gd_post GeoDirectory post object.
	 * @return float
	 */
	public static function check_business_hours( $gd_post ) {
		return ! empty( $gd_post->business_hours ) ? 1.0 : 0.0;
	}

	/**
	 * Check for a phone number.
	 *
	 * @param object $gd_post GeoDirectory post object.
	 * @return float
	 */
	public static function check_phone( $gd_post ) {
		return ! empty( $gd_post->phone ) ? 1.0 : 0.0;
	}

	/**
	 * Check for a valid email address.
	 *
	 * @param object $gd_post GeoDirectory post object.
	 * @return float
	 */
	public static function check_email( $gd_post ) {
		return ( ! empty( $gd_post->email ) && is_email( $gd_post->email ) ) ? 1.0 : 0.0;
	}

	/**
	 * Check for a website URL.
	 *
	 * @param object $gd_post GeoDirectory post object.
	 * @return float
	 */
	public static function check_website( $gd_post ) {
		return ! empty( $gd_post->website ) ? 1.0 : 0.0;
	}

	/**
	 * Check social profile links against the target count.
	 *
	 * @param object $gd_post GeoDirectory post object.
	 * @return float
	 */
	public static function check_social( $gd_post ) {
		$fields = apply_filters(
			'lhs_social_fields',
			array( 'facebook', 'instagram', 'twitter', 'x', 'linkedin', 'youtube', 'tiktok', 'pinterest' )
		);

		$found = 0;
		foreach ( $fields as $field ) {
			if ( ! empty( $gd_post->{$field} ) ) {
				++$found;
			}
		}

		$target = (int) apply_filters( 'lhs_social_target_count', 2 );
		return min( 1.0, $found / max( 1, $target ) );
	}

	/**
	 * Check photo gallery count against the target.
	 *
	 * @param object $gd_post GeoDirectory post object.
	 * @param int    $post_id Listing post ID.
	 * @return float
	 */
	public static function check_photos( $gd_post, $post_id ) {
		$count = 0;

		if ( class_exists( 'GeoDir_Media' ) ) {
			$images = GeoDir_Media::get_attachments_by_type( $post_id, 'post_images' );
			$count  = is_array( $images ) ? count( $images ) : 0;
		} elseif ( function_exists( 'geodir_get_images' ) ) {
			$images = geodir_get_images( $post_id );
			$count  = is_array( $images ) ? count( $images ) : 0;
		}

		$target = (int) apply_filters( 'lhs_photos_target_count', 5 );
		return min( 1.0, $count / max( 1, $target ) );
	}

	/**
	 * Check review count against the target.
	 *
	 * @param object $gd_post GeoDirectory post object.
	 * @param int    $post_id Listing post ID.
	 * @return float
	 */
	public static function check_reviews( $gd_post, $post_id ) {
		$count = (int) get_comments_number( $post_id );

		$target = (int) apply_filters( 'lhs_reviews_target_count', 3 );
		return min( 1.0, $count / max( 1, $target ) );
	}

	/**
	 * Check whether the listing has been claimed.
	 *
	 * @param object $gd_post GeoDirectory post object.
	 * @return float
	 */
	public static function check_claimed( $gd_post ) {
		// GeoDirectory Claim Listing addon stores this on the detail table.
		if ( isset( $gd_post->claimed ) ) {
			return ! empty( $gd_post->claimed ) ? 1.0 : 0.0;
		}
		// Claim addon not installed: don't penalize. Returning 1 with the
		// criterion still listed keeps weights stable across installs.
		return 1.0;
	}

	/**
	 * Check freshness, decaying linearly from the last modified date.
	 *
	 * @param object $gd_post GeoDirectory post object.
	 * @param int    $post_id Listing post ID.
	 * @return float
	 */
	public static function check_freshness( $gd_post, $post_id ) {
		$modified = get_post_field( 'post_modified_gmt', $post_id );
		if ( ! $modified ) {
			return 0.0;
		}

		$days_old = ( time() - strtotime( $modified . ' UTC' ) ) / DAY_IN_SECONDS;
		$max_days = (int) apply_filters( 'lhs_freshness_max_days', 180 );

		if ( $days_old <= 0 ) {
			return 1.0;
		}
		// Linear decay: full score if updated today, zero at $max_days.
		return max( 0.0, 1.0 - ( $days_old / $max_days ) );
	}
}
