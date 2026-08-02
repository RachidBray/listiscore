<?php
/**
 * Tests for LHS_Scorer: weighted score math, bands, and recommendations.
 *
 * @package Listing_Health_Score
 */

use Brain\Monkey\Functions;
use Brain\Monkey\Filters;

/**
 * ScorerTest class.
 */
class ScorerTest extends LHS_TestCase {

	/**
	 * Stub the calls `calculate()` makes to resolve and persist a listing,
	 * so tests can focus on the criteria/weight math itself.
	 *
	 * @param int $post_id Listing post ID.
	 */
	private function stub_valid_listing( $post_id = 1 ) {
		Functions\when( 'get_post_type' )->justReturn( 'gd_place' );
		Functions\when( 'geodir_get_post_info' )->justReturn( (object) array( 'ID' => $post_id ) );
		Functions\when( 'update_post_meta' )->justReturn( true );
		// LHS_Criteria::get_all() reads LHS_Settings (no overrides saved).
		Functions\when( 'get_option' )->justReturn( array() );
	}

	/**
	 * Build a minimal criterion definition with a fixed fraction.
	 *
	 * @param int    $weight   Criterion weight.
	 * @param float  $fraction Fixed fraction the check callback returns.
	 * @param string $tip     Tip text (unused by calculate() math, kept for realism).
	 * @return array
	 */
	private function criterion( $weight, $fraction, $tip = 'tip' ) {
		return array(
			'label'  => 'Label',
			'weight' => $weight,
			'check'  => function () use ( $fraction ) {
				return $fraction;
			},
			'tip'    => $tip,
		);
	}

	/**
	 * All criteria fully met scores exactly 100, regardless of weight split.
	 */
	public function test_calculate_all_criteria_full_returns_100() {
		$this->stub_valid_listing();
		Filters\expectApplied( 'lhs_criteria' )->andReturn(
			array(
				'a' => $this->criterion( 10, 1.0 ),
				'b' => $this->criterion( 20, 1.0 ),
			)
		);

		$this->assertSame( 100, LHS_Scorer::calculate( 1 ) );
	}

	/**
	 * All criteria at zero scores exactly 0.
	 */
	public function test_calculate_all_criteria_zero_returns_0() {
		$this->stub_valid_listing();
		Filters\expectApplied( 'lhs_criteria' )->andReturn(
			array(
				'a' => $this->criterion( 10, 0.0 ),
				'b' => $this->criterion( 20, 0.0 ),
			)
		);

		$this->assertSame( 0, LHS_Scorer::calculate( 1 ) );
	}

	/**
	 * Known fractions and weights produce the exact expected score:
	 * 50pts full + 25pts of 50 = 75/100 total weight earned = score 75.
	 */
	public function test_calculate_weighted_math_produces_exact_score() {
		$this->stub_valid_listing();
		Filters\expectApplied( 'lhs_criteria' )->andReturn(
			array(
				'a' => $this->criterion( 50, 1.0 ),
				'b' => $this->criterion( 50, 0.5 ),
			)
		);

		$this->assertSame( 75, LHS_Scorer::calculate( 1 ) );
	}

	/**
	 * Zero-weight and non-callable criteria are skipped entirely and don't
	 * corrupt the weighted total (only the one real criterion counts).
	 */
	public function test_calculate_skips_zero_weight_and_noncallable_criteria() {
		$this->stub_valid_listing();
		Filters\expectApplied( 'lhs_criteria' )->andReturn(
			array(
				'zero_weight' => $this->criterion( 0, 1.0 ),
				'noncallable' => array(
					'label'  => 'Not callable',
					'weight' => 10,
					'check'  => 'lhs_this_function_does_not_exist_anywhere',
					'tip'    => 'tip',
				),
				'real'        => $this->criterion( 10, 1.0 ),
			)
		);

		$this->assertSame( 100, LHS_Scorer::calculate( 1 ) );
	}

	/**
	 * The lhs_final_score filter can't push the stored score above 100.
	 */
	public function test_calculate_clamps_final_score_above_100() {
		$this->stub_valid_listing();
		Filters\expectApplied( 'lhs_criteria' )->andReturn( array( 'a' => $this->criterion( 10, 1.0 ) ) );
		Filters\expectApplied( 'lhs_final_score' )->andReturn( 999 );

		$this->assertSame( 100, LHS_Scorer::calculate( 1 ) );
	}

	/**
	 * The lhs_final_score filter can't push the stored score below 0.
	 */
	public function test_calculate_clamps_final_score_below_0() {
		$this->stub_valid_listing();
		Filters\expectApplied( 'lhs_criteria' )->andReturn( array( 'a' => $this->criterion( 10, 1.0 ) ) );
		Filters\expectApplied( 'lhs_final_score' )->andReturn( -50 );

		$this->assertSame( 0, LHS_Scorer::calculate( 1 ) );
	}

	/**
	 * Band boundaries: exact threshold values and the values just below them.
	 */
	public function test_get_band_boundaries() {
		$this->assertSame( 'good', LHS_Scorer::get_band( 80 ) );
		$this->assertSame( 'ok', LHS_Scorer::get_band( 79 ) );
		$this->assertSame( 'ok', LHS_Scorer::get_band( 50 ) );
		$this->assertSame( 'poor', LHS_Scorer::get_band( 49 ) );
	}

	/**
	 * Only criteria with a non-empty tip (i.e. not fully met) produce a
	 * recommendation, and they're sorted by potential point gain descending.
	 */
	public function test_get_recommendations_only_incomplete_sorted_by_potential_points() {
		Functions\when( 'get_post_meta' )->justReturn(
			array(
				'a' => array(
					'label'    => 'A',
					'weight'   => 10,
					'fraction' => 1.0,
					'points'   => 10.0,
					'tip'      => '', // Fully met: no tip, must be excluded.
				),
				'b' => array(
					'label'    => 'B',
					'weight'   => 20,
					'fraction' => 0.75,
					'points'   => 15.0,
					'tip'      => 'improve b',
				),
				'c' => array(
					'label'    => 'C',
					'weight'   => 10,
					'fraction' => 0.0,
					'points'   => 0.0,
					'tip'      => 'improve c',
				),
				'd' => array(
					'label'    => 'D',
					'weight'   => 30,
					'fraction' => 0.9,
					'points'   => 27.0,
					'tip'      => 'improve d',
				),
			)
		);

		$tips = LHS_Scorer::get_recommendations( 1 );

		$this->assertCount( 3, $tips );

		$this->assertSame( 'c', $tips[0]['id'] );
		$this->assertSame( 10.0, $tips[0]['potential_points'] );

		$this->assertSame( 'b', $tips[1]['id'] );
		$this->assertSame( 5.0, $tips[1]['potential_points'] );

		$this->assertSame( 'd', $tips[2]['id'] );
		$this->assertSame( 3.0, $tips[2]['potential_points'] );
	}
}
