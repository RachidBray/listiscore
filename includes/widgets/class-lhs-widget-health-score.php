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

		$me_3      = $aui_bs5 ? 'me-3' : 'mr-3';
		$remaining = 100 - $score;
		$tint      = $this->band_track_color( $band );

		if ( empty( $recommendations ) ) {
			$headline = __( "You're all set! 🎉", 'listing-health-score' );
			$subhead  = __( 'Your listing meets every criterion we check for.', 'listing-health-score' );
		} else {
			$headline = __( "You're only a few steps away! 🎉", 'listing-health-score' );
			$subhead  = __( 'Complete a few more steps to increase your visibility and earn more customer trust.', 'listing-health-score' );
		}
		?>
		<div class="lhs-health-score">
			<?php if ( $title ) : ?>
				<h3 class="widget-title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>

			<div class="text-center mb-4">
				<h4 class="mb-2"><?php echo esc_html( $headline ); ?></h4>
				<p class="text-muted mb-4"><?php echo esc_html( $subhead ); ?></p>

				<div class="progress mb-2" style="height: .75rem;">
					<div
						class="progress-bar progress-bar-striped progress-bar-animated bg-<?php echo esc_attr( $color ); ?>"
						role="progressbar"
						aria-label="<?php echo esc_attr( $this->band_label( $band ) ); ?>"
						style="width: <?php echo esc_attr( $score ); ?>%;"
						aria-valuenow="<?php echo esc_attr( $score ); ?>"
						aria-valuemin="0"
						aria-valuemax="100"
					></div>
				</div>

				<div class="d-flex justify-content-between small">
					<span class="fw-semibold text-<?php echo esc_attr( $color ); ?>">
						<?php
						/* translators: %d: score percentage complete. */
						echo esc_html( sprintf( __( '%d%% Complete', 'listing-health-score' ), $score ) );
						?>
					</span>
					<?php if ( $remaining > 0 ) : ?>
						<span class="text-muted">
							<?php
							/* translators: %d: percentage remaining to reach a full score. */
							echo esc_html( sprintf( __( 'Only %d%% left', 'listing-health-score' ), $remaining ) );
							?>
						</span>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! empty( $recommendations ) ) : ?>
				<div class="list-group list-group-flush gap-2">
					<?php
					foreach ( $recommendations as $i => $tip ) :
						// The biggest win (list is already sorted by potential_percentage
						// descending) gets a tinted, bordered card so it stands out; the
						// rest stay plain — draws the eye to the single most useful action.
						$is_first   = ( 0 === $i );
						$item_class = $is_first ? 'border border-' . $color : 'bg-light border-0';
						$item_style = $is_first ? 'background-color: ' . $tint . ';' : '';
						?>
						<div
							class="list-group-item d-flex justify-content-between align-items-center px-3 py-3 rounded-3 <?php echo esc_attr( $item_class ); ?>"
							style="<?php echo esc_attr( $item_style ); ?>"
						>
							<div class="d-flex align-items-start">
								<span class="text-success fw-bold fs-5 <?php echo esc_attr( $me_3 ); ?> mt-1" aria-hidden="true">&#8599;</span>
								<div>
									<div class="fw-semibold text-dark fs-6"><?php echo esc_html( $tip['label'] ); ?></div>
									<div class="text-muted small mt-1"><?php echo esc_html( $tip['tip'] ); ?></div>
								</div>
							</div>
							<span class="badge bg-white text-dark border px-3 py-2 rounded-2 fw-bold shadow-sm">
								<?php
								/* translators: %d: potential score percentage gained. */
								echo esc_html( sprintf( __( '+%d%%', 'listing-health-score' ), $tip['potential_percentage'] ) );
								?>
							</span>
						</div>
					<?php endforeach; ?>
				</div>
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
		$hex       = $this->band_hex_color( $band );
		$tint      = $this->band_track_color( $band );
		$remaining = 100 - $score;

		if ( empty( $recommendations ) ) {
			$headline = __( "You're all set! 🎉", 'listing-health-score' );
			$subhead  = __( 'Your listing meets every criterion we check for.', 'listing-health-score' );
		} else {
			$headline = __( "You're only a few steps away! 🎉", 'listing-health-score' );
			$subhead  = __( 'Complete a few more steps to increase your visibility and earn more customer trust.', 'listing-health-score' );
		}
		?>
		<div class="lhs-health-score lhs-health-score--legacy">
			<?php if ( $title ) : ?>
				<h3 class="widget-title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>

			<div style="text-align:center;margin-bottom:24px;">
				<h4 style="margin:0 0 8px;"><?php echo esc_html( $headline ); ?></h4>
				<p style="color:#666;margin:0 0 16px;"><?php echo esc_html( $subhead ); ?></p>

				<div
					role="progressbar"
					aria-label="<?php echo esc_attr( $this->band_label( $band ) ); ?>"
					aria-valuenow="<?php echo esc_attr( $score ); ?>"
					aria-valuemin="0"
					aria-valuemax="100"
					style="background:#eee;border-radius:999px;height:12px;overflow:hidden;margin-bottom:8px;"
				>
					<div style="background:<?php echo esc_attr( $hex ); ?>;width:<?php echo esc_attr( $score ); ?>%;height:100%;border-radius:999px;"></div>
				</div>

				<div style="display:flex;justify-content:space-between;font-size:0.85em;">
					<span style="font-weight:600;color:<?php echo esc_attr( $hex ); ?>;">
						<?php
						/* translators: %d: score percentage complete. */
						echo esc_html( sprintf( __( '%d%% Complete', 'listing-health-score' ), $score ) );
						?>
					</span>
					<?php if ( $remaining > 0 ) : ?>
						<span style="color:#666;">
							<?php
							/* translators: %d: percentage remaining to reach a full score. */
							echo esc_html( sprintf( __( 'Only %d%% left', 'listing-health-score' ), $remaining ) );
							?>
						</span>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! empty( $recommendations ) ) : ?>
				<div style="display:flex;flex-direction:column;gap:8px;">
					<?php
					foreach ( $recommendations as $i => $tip ) :
						// The biggest win (list is already sorted by potential_percentage
						// descending) gets a tinted, bordered card so it stands out.
						$is_first = ( 0 === $i );
						$item_bg  = $is_first ? $tint : '#f7f7f6';
						$border   = $is_first ? '1px solid ' . $hex : 'none';
						?>
						<div style="display:flex;justify-content:space-between;align-items:center;padding:12px;border-radius:8px;background:<?php echo esc_attr( $item_bg ); ?>;border:<?php echo esc_attr( $border ); ?>;">
							<div style="display:flex;align-items:flex-start;">
								<span style="color:#0ca30c;font-weight:700;font-size:1.1em;margin-right:12px;" aria-hidden="true">&#8599;</span>
								<div>
									<div style="font-weight:600;color:#0b0b0b;"><?php echo esc_html( $tip['label'] ); ?></div>
									<div style="color:#666;font-size:0.9em;margin-top:2px;"><?php echo esc_html( $tip['tip'] ); ?></div>
								</div>
							</div>
							<span style="background:#fff;color:#0b0b0b;border:1px solid #ddd;padding:4px 10px;border-radius:6px;font-weight:700;white-space:nowrap;">
								<?php
								/* translators: %d: potential score percentage gained. */
								echo esc_html( sprintf( __( '+%d%%', 'listing-health-score' ), $tip['potential_percentage'] ) );
								?>
							</span>
						</div>
					<?php endforeach; ?>
				</div>
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
	 * Tint color for a band — a lighter step of the fill's own hue, not a
	 * neutral gray. Used as the highlight background for the first (biggest
	 * win) recommendation card, paired with a solid `border-{color}` in AUI
	 * mode or `$hex` border in legacy mode.
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
