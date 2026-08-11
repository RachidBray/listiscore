<?php
/**
 * Shared PHPUnit + Brain Monkey base test case.
 *
 * Not named `*Test.php` on purpose so PHPUnit's directory suffix scan
 * (see phpunit.xml.dist) doesn't try to run this abstract class directly.
 *
 * @package ListiScore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PHPUnit\Framework\TestCase as PHPUnit_TestCase;

/**
 * ListiScore_TestCase class.
 */
abstract class ListiScore_TestCase extends PHPUnit_TestCase {

	/**
	 * Boots Brain Monkey's function/hook interception before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		Brain\Monkey\setUp();

		// Every criterion label/tip goes through __(), even in tests that
		// don't care about its output (e.g. ListiScore_Criteria::get_defaults() is
		// always built before a test's listiscore_criteria filter override replaces it).
		Brain\Monkey\Functions\stubTranslationFunctions();
	}

	/**
	 * Tears down Brain Monkey (and Mockery/Patchwork) after each test.
	 */
	protected function tearDown(): void {
		Brain\Monkey\tearDown();
		parent::tearDown();
	}
}
