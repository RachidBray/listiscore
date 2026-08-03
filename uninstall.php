<?php
/**
 * Uninstall Listing Health Score.
 *
 * Deletes the `lhs_settings` option, every `_lhs_*` post meta key this
 * plugin has ever written, and clears the daily recalculation cron event.
 *
 * @package Listing_Health_Score
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'lhs_settings' );

$meta_keys = array( '_lhs_score', '_lhs_breakdown', '_lhs_calculated_at', '_lhs_settings_version' );

foreach ( $meta_keys as $meta_key ) {
	delete_post_meta_by_key( $meta_key );
}

wp_clear_scheduled_hook( 'lhs_daily_recalc' );
