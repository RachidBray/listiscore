<?php
/**
 * Uninstall Listing Health Score.
 *
 * Deletes the `listiscore_settings` option, every `_listiscore_*` post meta key this
 * plugin has ever written, and clears the daily recalculation cron event.
 *
 * @package ListiScore
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'listiscore_settings' );

$listiscore_meta_keys = array( '_listiscore_score', '_listiscore_breakdown', '_listiscore_calculated_at', '_listiscore_settings_version' );

foreach ( $listiscore_meta_keys as $listiscore_meta_key ) {
	delete_post_meta_by_key( $listiscore_meta_key );
}

wp_clear_scheduled_hook( 'listiscore_daily_recalc' );
