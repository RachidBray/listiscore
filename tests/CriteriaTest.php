<?php
/**
 * Tests for LHS_Criteria's individual check_* fraction calculations.
 *
 * @package Listing_Health_Score
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Brain\Monkey\Functions;

/**
 * CriteriaTest class.
 */
class CriteriaTest extends LHS_TestCase {

	/**
	 * Description: no content earns zero credit.
	 */
	public function test_check_description_zero_chars_returns_zero() {
		Functions\when( 'get_post_field' )->justReturn( '' );

		$this->assertSame( 0.0, LHS_Criteria::check_description( (object) array(), 1 ) );
	}

	/**
	 * Description: half of the 300-char target earns half credit.
	 */
	public function test_check_description_half_of_target_returns_half() {
		Functions\when( 'get_post_field' )->justReturn( str_repeat( 'a', 150 ) );

		$this->assertSame( 0.5, LHS_Criteria::check_description( (object) array(), 1 ) );
	}

	/**
	 * Description: at the target earns full credit.
	 */
	public function test_check_description_at_target_returns_one() {
		Functions\when( 'get_post_field' )->justReturn( str_repeat( 'a', 300 ) );

		// assertEquals: PHP's `/` returns an int for an exact 300/300 division,
		// not specifically a float, even though the value is correct either way.
		$this->assertEquals( 1.0, LHS_Criteria::check_description( (object) array(), 1 ) );
	}

	/**
	 * Description: over the target caps at full credit, doesn't exceed 1.0.
	 */
	public function test_check_description_over_target_caps_at_one() {
		Functions\when( 'get_post_field' )->justReturn( str_repeat( 'a', 600 ) );

		$this->assertSame( 1.0, LHS_Criteria::check_description( (object) array(), 1 ) );
	}

	/**
	 * Photos: no images earns zero credit.
	 */
	public function test_check_photos_zero_returns_zero() {
		Functions\when( 'geodir_get_images' )->justReturn( array() );

		$this->assertEquals( 0.0, LHS_Criteria::check_photos( (object) array(), 1 ) );
	}

	/**
	 * Photos: partial count earns the matching fraction of the 5-photo target.
	 */
	public function test_check_photos_partial_returns_correct_fraction() {
		Functions\when( 'geodir_get_images' )->justReturn( array( 1, 2 ) );

		$this->assertSame( 0.4, LHS_Criteria::check_photos( (object) array(), 1 ) );
	}

	/**
	 * Photos: exactly at the target earns full credit.
	 */
	public function test_check_photos_at_target_returns_one() {
		Functions\when( 'geodir_get_images' )->justReturn( array( 1, 2, 3, 4, 5 ) );

		$this->assertEquals( 1.0, LHS_Criteria::check_photos( (object) array(), 1 ) );
	}

	/**
	 * Photos: over the target caps at 1.0, doesn't exceed it.
	 */
	public function test_check_photos_over_target_caps_at_one() {
		Functions\when( 'geodir_get_images' )->justReturn( array( 1, 2, 3, 4, 5, 6, 7, 8 ) );

		$this->assertSame( 1.0, LHS_Criteria::check_photos( (object) array(), 1 ) );
	}

	/**
	 * Reviews: zero reviews earns zero credit.
	 */
	public function test_check_reviews_zero_returns_zero() {
		Functions\when( 'get_comments_number' )->justReturn( 0 );

		$this->assertEquals( 0.0, LHS_Criteria::check_reviews( (object) array(), 1 ) );
	}

	/**
	 * Reviews: partial count earns the matching fraction of the 3-review target.
	 */
	public function test_check_reviews_partial_returns_correct_fraction() {
		Functions\when( 'get_comments_number' )->justReturn( 1 );

		$this->assertEqualsWithDelta( 1 / 3, LHS_Criteria::check_reviews( (object) array(), 1 ), 0.0001 );
	}

	/**
	 * Reviews: at the target earns full credit.
	 */
	public function test_check_reviews_at_target_returns_one() {
		Functions\when( 'get_comments_number' )->justReturn( 3 );

		$this->assertEquals( 1.0, LHS_Criteria::check_reviews( (object) array(), 1 ) );
	}

	/**
	 * Reviews: over the target caps at 1.0.
	 */
	public function test_check_reviews_over_target_caps_at_one() {
		Functions\when( 'get_comments_number' )->justReturn( 10 );

		$this->assertSame( 1.0, LHS_Criteria::check_reviews( (object) array(), 1 ) );
	}

	/**
	 * Social: no linked profiles earns zero credit.
	 */
	public function test_check_social_zero_returns_zero() {
		$this->assertEquals( 0.0, LHS_Criteria::check_social( (object) array() ) );
	}

	/**
	 * Social: one of the 2-profile target earns half credit.
	 */
	public function test_check_social_partial_returns_correct_fraction() {
		$gd_post = (object) array( 'facebook' => 'https://facebook.com/example' );

		$this->assertSame( 0.5, LHS_Criteria::check_social( $gd_post ) );
	}

	/**
	 * Social: at the target earns full credit.
	 */
	public function test_check_social_at_target_returns_one() {
		$gd_post = (object) array(
			'facebook'  => 'https://facebook.com/example',
			'instagram' => 'https://instagram.com/example',
		);

		$this->assertEquals( 1.0, LHS_Criteria::check_social( $gd_post ) );
	}

	/**
	 * Social: over the target (more profiles than required) caps at 1.0.
	 */
	public function test_check_social_over_target_caps_at_one() {
		$gd_post = (object) array(
			'facebook'  => 'https://facebook.com/example',
			'instagram' => 'https://instagram.com/example',
			'twitter'   => 'https://twitter.com/example',
			'linkedin'  => 'https://linkedin.com/example',
		);

		$this->assertSame( 1.0, LHS_Criteria::check_social( $gd_post ) );
	}

	/**
	 * Freshness: modified right now earns (approximately, allowing for the
	 * few microseconds between this line and the SUT's own time() call)
	 * full credit.
	 */
	public function test_check_freshness_modified_today_returns_approximately_one() {
		Functions\when( 'get_post_field' )->justReturn( gmdate( 'Y-m-d H:i:s' ) );

		$this->assertEqualsWithDelta( 1.0, LHS_Criteria::check_freshness( (object) array(), 1 ), 0.001 );
	}

	/**
	 * Freshness: modified exactly at max_days ago decays to zero.
	 */
	public function test_check_freshness_at_max_days_returns_zero() {
		Functions\when( 'get_post_field' )->justReturn( gmdate( 'Y-m-d H:i:s', time() - ( 180 * DAY_IN_SECONDS ) ) );

		$this->assertSame( 0.0, LHS_Criteria::check_freshness( (object) array(), 1 ) );
	}

	/**
	 * Freshness: halfway to max_days decays linearly to approximately 0.5.
	 */
	public function test_check_freshness_halfway_returns_approximately_half() {
		Functions\when( 'get_post_field' )->justReturn( gmdate( 'Y-m-d H:i:s', time() - ( 90 * DAY_IN_SECONDS ) ) );

		$this->assertEqualsWithDelta( 0.5, LHS_Criteria::check_freshness( (object) array(), 1 ), 0.001 );
	}

	/**
	 * Freshness: never modified (no date at all) earns zero credit.
	 */
	public function test_check_freshness_no_date_returns_zero() {
		Functions\when( 'get_post_field' )->justReturn( '' );

		$this->assertSame( 0.0, LHS_Criteria::check_freshness( (object) array(), 1 ) );
	}
}
