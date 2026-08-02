<?php
/**
 * PHPUnit bootstrap.
 *
 * Deliberately does NOT load WordPress. Brain Monkey (via Patchwork) mocks
 * WP functions per test instead, so the suite stays fast and isolated from
 * any particular WP/GeoDirectory install.
 *
 * @package Listing_Health_Score
 */

// Defined first (not a require/include) so PHPCS's file-doc-comment sniff
// doesn't mistake the docblock above for documentation of the line below.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

require_once __DIR__ . '/../vendor/autoload.php';

/*
 * Patchwork (the runtime function-redefinition engine Brain Monkey sits on
 * top of) can only redefine functions defined in files that are `require`d
 * *after* it activates. Load it now, before any compatibility shim below is
 * defined, so Functions\when() can override those shims in tests.
 */
require_once __DIR__ . '/../vendor/brain/monkey/inc/patchwork-loader.php';

require_once __DIR__ . '/wp-stubs.php';

require_once __DIR__ . '/../includes/class-lhs-settings.php';
require_once __DIR__ . '/../includes/class-lhs-criteria.php';
require_once __DIR__ . '/../includes/class-lhs-scorer.php';

require_once __DIR__ . '/TestCase.php';
