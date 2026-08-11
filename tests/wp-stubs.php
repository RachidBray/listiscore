<?php
/**
 * Real (non-mocked) compatibility shims for pure-logic WP/GeoDirectory
 * helpers used by the code under test.
 *
 * This must be `require`d from bootstrap.php *after* Patchwork has been
 * loaded — Patchwork can only redefine functions that are defined in files
 * included after it activates, and Functions\when() overrides these in
 * individual tests.
 *
 * @package ListiScore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * Minimal real implementation of WP's array/object argument merger.
	 *
	 * @param array|object|string $args     Value to merge.
	 * @param array               $defaults Defaults array.
	 * @return array
	 */
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$parsed = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$parsed = $args;
		} else {
			parse_str( (string) $args, $parsed );
		}

		return array_merge( $defaults, $parsed );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Minimal real implementation of WP's tag stripper.
	 *
	 * @param string $text_to_strip Text to strip tags from.
	 * @return string
	 */
	function wp_strip_all_tags( $text_to_strip ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- this *is* the wp_strip_all_tags() shim; it has nothing to defer to.
		return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $text_to_strip ) ) );
	}
}

if ( ! function_exists( 'geodir_get_images' ) ) {
	/**
	 * Real stand-in for the GeoDirectory function used as a fallback path
	 * when the GeoDir_Media class isn't available. Tests override the
	 * return value per case via Functions\when() — this just needs to exist
	 * so function_exists() (a real, unmocked PHP check) finds it.
	 *
	 * @param int $post_id Listing post ID (unused; signature matches the real function).
	 * @return array
	 */
	function geodir_get_images( $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- signature matches the real GeoDirectory function.
		return array();
	}
}

if ( ! function_exists( 'geodir_get_posttypes' ) ) {
	/**
	 * Real stand-in for GeoDirectory's post type list, overridden per test
	 * via Functions\when() where a different set of post types is needed.
	 *
	 * @return string[]
	 */
	function geodir_get_posttypes() {
		return array( 'gd_place' );
	}
}
