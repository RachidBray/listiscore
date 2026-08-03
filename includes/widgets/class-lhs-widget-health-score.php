<?php
/**
 * Listing Health Score widget.
 *
 * A single Super Duper class gives us a widget, the `[lhs_health]` shortcode,
 * and a Gutenberg block for free.
 *
 * @package Listing_Health_Score
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LHS_Widget_Health_Score class.
 */
class LHS_Widget_Health_Score extends WP_Super_Duper {

	/**
	 * Register this widget with GeoDirectory.
	 *
	 * Hooked to `geodir_get_widgets`; GD's own `widgets_init` callback takes
	 * care of actually calling `register_widget()` on it.
	 *
	 * @param string[] $widgets Registered widget class names.
	 * @return string[]
	 */
	public static function register( $widgets ) {
		$widgets[] = __CLASS__;
		return $widgets;
	}

	/**
	 * Sets up the widget's name etc.
	 */
	public function __construct() {
		$options = array(
			'textdomain'     => 'listing-health-score',
			'block-icon'     => 'fas fa-heartbeat',
			'block-category' => 'geodirectory',
			'block-keywords' => "['health','score','geodir','listing']",
			'class_name'     => __CLASS__,
			'base_id'        => 'lhs_health',
			'name'           => __( 'GD > Listing Health Score', 'listing-health-score' ),
			'widget_ops'     => array(
				'classname'       => 'lhs-widget-health-score' . ( geodir_design_style() ? ' bsui' : '' ),
				'description'     => esc_html__( 'Displays the listing health score, band, and recommendations.', 'listing-health-score' ),
				'geodirectory'    => true,
				'gd_wgt_showhide' => 'show_on',
				'gd_wgt_restrict' => array( 'gd-detail' ),
			),
		);

		parent::__construct( $options );
	}

	/**
	 * Set widget arguments.
	 *
	 * @return array
	 */
	public function set_arguments() {
		return array(
			'title'                => array(
				'title'    => __( 'Title:', 'listing-health-score' ),
				'desc'     => __( 'Leave blank for no title.', 'listing-health-score' ),
				'type'     => 'text',
				'default'  => '',
				'desc_tip' => true,
				'advanced' => false,
			),
			'id'                   => array(
				'title'       => __( 'Post ID:', 'listing-health-score' ),
				'desc'        => __( 'Leave blank to use the current listing.', 'listing-health-score' ),
				'type'        => 'number',
				'placeholder' => __( 'Leave blank to use current post id.', 'listing-health-score' ),
				'default'     => '',
				'desc_tip'    => true,
				'advanced'    => true,
			),
			'show_recommendations' => array(
				'title'    => __( 'Show recommendations:', 'listing-health-score' ),
				'desc'     => __( 'Show the list of tips sorted by potential point gain.', 'listing-health-score' ),
				'type'     => 'checkbox',
				'value'    => '1',
				'default'  => 1,
				'desc_tip' => true,
				'advanced' => false,
			),
			'public'               => array(
				'title'    => __( 'Show score publicly:', 'listing-health-score' ),
				'desc'     => __( 'By default only the listing owner and admins can see the health score. Enable to show it to every visitor.', 'listing-health-score' ),
				'type'     => 'checkbox',
				'value'    => '1',
				'default'  => 0,
				'desc_tip' => true,
				'advanced' => false,
			),
		);
	}

	/**
	 * Outputs the health score on the front-end.
	 *
	 * @param array  $args        Widget arguments.
	 * @param array  $widget_args Widget args (unused).
	 * @param string $content     Shortcode content (unused).
	 * @return string
	 */
	public function output( $args = array(), $widget_args = array(), $content = '' ) {
		global $gd_post, $post;

		$defaults = array(
			'title'                => '',
			'id'                   => '',
			'show_recommendations' => 1,
			'public'               => 0,
		);
		$args     = wp_parse_args( $args, $defaults );

		$is_preview = $this->is_preview() || $this->is_block_content_call();

		if ( ! empty( $args['id'] ) ) {
			$post_id = absint( $args['id'] );
		} elseif ( ! empty( $gd_post->ID ) ) {
			$post_id = $gd_post->ID;
		} elseif ( ! empty( $post->ID ) ) {
			$post_id = $post->ID;
		} else {
			$post_id = 0;
		}

		if ( ! $post_id || ! LHS_Scorer::is_gd_listing( $post_id ) ) {
			return $is_preview ? $this->preview_placeholder( __( 'No listing found to preview.', 'listing-health-score' ) ) : '';
		}

		if ( ! $is_preview && ! self::current_user_can_view( $post_id, ! empty( $args['public'] ) ) ) {
			return '';
		}

		$score = LHS_Scorer::get_score( $post_id );
		if ( false === $score ) {
			return $is_preview ? $this->preview_placeholder( __( 'Not a scoreable listing.', 'listing-health-score' ) ) : '';
		}

		$band            = LHS_Scorer::get_band( $score );
		$recommendations = ! empty( $args['show_recommendations'] ) ? LHS_Scorer::get_recommendations( $post_id ) : array();

		ob_start();
		$this->render( $score, $band, $recommendations, $args['title'] );
		return ob_get_clean();
	}

	/**
	 * Render the widget markup, branching on the active design style.
	 *
	 * @param int     $score           Score 0-100.
	 * @param string  $band            good|ok|poor.
	 * @param array[] $recommendations Tips from LHS_Scorer::get_recommendations().
	 * @param string  $title           Optional widget title.
	 */
	private function render( $score, $band, $recommendations, $title ) {
		$color = $this->band_color( $band );

		if ( geodir_design_style() ) {
			$this->render_aui( $score, $band, $color, $recommendations, $title );
		} else {
			$this->render_legacy( $score, $band, $color, $recommendations, $title );
		}
	}

	/**
	 * Render using AyeCode UI (Bootstrap) markup.
	 *
	 * @param int     $score           Score 0-100.
	 * @param string  $band            good|ok|poor.
	 * @param string  $color           Bootstrap contextual color (success|warning|danger).
	 * @param array[] $recommendations Tips.
	 * @param string  $title           Optional widget title.
	 */
	private function render_aui( $score, $band, $color, $recommendations, $title ) {
		global $aui_bs5;

		$me_1        = $aui_bs5 ? 'me-1' : 'mr-1';
		$me_2        = $aui_bs5 ? 'me-2' : 'mr-2';
		$badge_color = $aui_bs5 ? 'text-bg-' . $color : 'badge-' . $color;
		$track_color = $this->band_track_color( $band );
		?>
		<div class="lhs-health-score">
			<?php if ( $title ) : ?>
				<h3 class="widget-title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>
			<div class="d-flex align-items-center mb-2">
				<?php
				$badge_args = array(
					'content' => $score . '/100',
					'class'   => 'badge ' . $badge_color . ' fs-6 ' . $me_2,
				);
				echo aui()->badge( $badge_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- aui()->badge() escapes its own output.
				?>
				<span class="text-muted text-capitalize"><?php echo esc_html( $this->band_label( $band ) ); ?></span>
			</div>
			<?php /* Track is a lighter step of the fill's own hue (not a neutral gray) so the band still reads across the whole bar, not just the filled portion. The percentage label is centered across the full bar (not just the fill) so it's never clipped by a small fill at low scores; dark ink reads clearly against both the pale track and every band's fill color. */ ?>
			<div class="progress mb-3" style="height: 18px; background-color: <?php echo esc_attr( $track_color ); ?>; position: relative;">
				<div
					class="progress-bar bg-<?php echo esc_attr( $color ); ?>"
					role="progressbar"
					style="width: <?php echo esc_attr( $score ); ?>%;"
					aria-valuenow="<?php echo esc_attr( $score ); ?>"
					aria-valuemin="0"
					aria-valuemax="100"
				></div>
				<span
					class="small fw-semibold"
					style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #0b0b0b; line-height: 1;"
				>
					<?php echo esc_html( $score ); ?>%
				</span>
			</div>
			<?php if ( ! empty( $recommendations ) ) : ?>
				<ul class="lhs-recommendations list-unstyled mb-0">
					<?php foreach ( $recommendations as $tip ) : ?>
						<li class="d-flex align-items-center justify-content-between mb-2">
							<span>
								<i class="fas fa-arrow-up text-success <?php echo esc_attr( $me_1 ); ?>" aria-hidden="true"></i>
								<?php echo esc_html( $tip['tip'] ); ?>
							</span>
							<span class="badge fw-normal" style="background-color: #f0efec; color: #666;">
								<?php
								/* translators: %s: potential score percentage gained, e.g. "3.5". */
								echo esc_html( sprintf( __( '+%s%%', 'listing-health-score' ), $tip['potential_percentage'] ) );
								?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render using plain markup for sites not using AyeCode UI / Bootstrap.
	 *
	 * @param int     $score           Score 0-100.
	 * @param string  $band            good|ok|poor.
	 * @param string  $color           Hex color for the band.
	 * @param array[] $recommendations Tips.
	 * @param string  $title           Optional widget title.
	 */
	private function render_legacy( $score, $band, $color, $recommendations, $title ) {
		$hex   = $this->band_hex_color( $band );
		$track = $this->band_track_color( $band );
		?>
		<div class="lhs-health-score lhs-health-score--legacy">
			<?php if ( $title ) : ?>
				<h3 class="widget-title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>
			<p style="display:flex;align-items:center;gap:8px;">
				<span
					class="lhs-badge lhs-badge--<?php echo esc_attr( $band ); ?>"
					style="background:<?php echo esc_attr( $hex ); ?>;color:<?php echo esc_attr( $this->band_badge_text_color( $band ) ); ?>;border-radius:999px;padding:2px 10px;font-weight:600;"
				>
					<?php echo esc_html( $score ); ?>/100
				</span>
				<span style="color:#666;"><?php echo esc_html( $this->band_label( $band ) ); ?></span>
			</p>
			<?php /* Track is a lighter step of the fill's own hue (not a neutral gray) so the band still reads across the whole bar, not just the filled portion. The percentage label is centered across the full bar (not just the fill) so it's never clipped by a small fill at low scores; dark ink reads clearly against both the pale track and every band's fill color. */ ?>
			<div
				role="progressbar"
				aria-valuenow="<?php echo esc_attr( $score ); ?>"
				aria-valuemin="0"
				aria-valuemax="100"
				style="background:<?php echo esc_attr( $track ); ?>;border-radius:999px;height:18px;overflow:hidden;margin-bottom:12px;position:relative;"
			>
				<div style="background:<?php echo esc_attr( $hex ); ?>;width:<?php echo esc_attr( $score ); ?>%;height:100%;border-radius:999px;"></div>
				<span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#0b0b0b;font-size:0.8em;font-weight:600;line-height:1;">
					<?php echo esc_html( $score ); ?>%
				</span>
			</div>
			<?php if ( ! empty( $recommendations ) ) : ?>
				<ul style="list-style:none;margin:0;padding:0;">
					<?php foreach ( $recommendations as $tip ) : ?>
						<li style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:4px 0;">
							<span>
								<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#0ca30c;margin-right:6px;"></span>
								<?php echo esc_html( $tip['tip'] ); ?>
							</span>
							<span style="background:#f0efec;color:#666;border-radius:999px;padding:1px 8px;font-size:0.85em;white-space:nowrap;">
								<?php
								/* translators: %s: potential score percentage gained, e.g. "3.5". */
								echo esc_html( sprintf( __( '+%s%%', 'listing-health-score' ), $tip['potential_percentage'] ) );
								?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Placeholder shown in the block editor / widget preview when there's
	 * nothing real to render.
	 *
	 * @param string $message Message to show.
	 * @return string
	 */
	private function preview_placeholder( $message ) {
		return '<div class="lhs-health-score lhs-health-score--placeholder">' . esc_html( $message ) . '</div>';
	}

	/**
	 * Whether the current user is allowed to see this listing's score.
	 *
	 * @param int  $post_id   Listing post ID.
	 * @param bool $is_public Whether the widget instance is set to show publicly.
	 * @return bool
	 */
	private static function current_user_can_view( $post_id, $is_public ) {
		if ( $is_public || current_user_can( 'manage_options' ) ) {
			return true;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		return (int) get_post_field( 'post_author', $post_id ) === $user_id;
	}

	/**
	 * Bootstrap contextual color for a band.
	 *
	 * @param string $band good|ok|poor.
	 * @return string success|warning|danger
	 */
	private function band_color( $band ) {
		$colors = array(
			'good' => 'success',
			'ok'   => 'warning',
			'poor' => 'danger',
		);

		return isset( $colors[ $band ] ) ? $colors[ $band ] : 'secondary';
	}

	/**
	 * Hex color for a band, for sites not using AyeCode UI / Bootstrap.
	 *
	 * A validated good/warning/critical status palette — status colors are
	 * always paired with the text label from band_label(), never used alone,
	 * since warning/critical don't clear 3:1 contrast on a light surface by
	 * themselves.
	 *
	 * @param string $band good|ok|poor.
	 * @return string
	 */
	private function band_hex_color( $band ) {
		$colors = array(
			'good' => '#0ca30c',
			'ok'   => '#fab219',
			'poor' => '#d03b3b',
		);

		return isset( $colors[ $band ] ) ? $colors[ $band ] : '#666';
	}

	/**
	 * Legible text color for the score badge's colored background.
	 *
	 * Computed from actual contrast ratios against each status hex (not
	 * assumed): white fails badly on the amber "ok" background (1.83:1) and
	 * is marginal on green (3.35:1), so those two get dark ink; red tests
	 * better with white (4.80:1) than dark (4.10:1).
	 *
	 * @param string $band good|ok|poor.
	 * @return string
	 */
	private function band_badge_text_color( $band ) {
		$colors = array(
			'good' => '#0b0b0b',
			'ok'   => '#0b0b0b',
			'poor' => '#ffffff',
		);

		return isset( $colors[ $band ] ) ? $colors[ $band ] : '#0b0b0b';
	}

	/**
	 * Track (unfilled) color for a band's progress meter.
	 *
	 * A lighter step of the fill's own hue, not a neutral gray, so the band
	 * still reads across the whole bar rather than just the filled portion.
	 *
	 * @param string $band good|ok|poor.
	 * @return string
	 */
	private function band_track_color( $band ) {
		$colors = array(
			'good' => '#dbf1db',
			'ok'   => '#fef3dc',
			'poor' => '#f8e2e2',
		);

		return isset( $colors[ $band ] ) ? $colors[ $band ] : '#eee';
	}

	/**
	 * Human readable label for a band.
	 *
	 * @param string $band good|ok|poor.
	 * @return string
	 */
	private function band_label( $band ) {
		$labels = array(
			'good' => __( 'Good', 'listing-health-score' ),
			'ok'   => __( 'Needs improvement', 'listing-health-score' ),
			'poor' => __( 'Poor', 'listing-health-score' ),
		);

		return isset( $labels[ $band ] ) ? $labels[ $band ] : '';
	}
}
