<?php
/**
 * Plugin Name: Wing Review
 * Plugin URI: https://chubes.net
 * Description: Review block for wing locations with comment-to-block conversion on approval
 * Version: 0.1.2
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wing-review
 */

namespace WingReview;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WING_REVIEW_VERSION', '0.1.2' );
define( 'WING_REVIEW_PATH', plugin_dir_path( __FILE__ ) );
define( 'WING_REVIEW_URL', plugin_dir_url( __FILE__ ) );

/**
 * Get the theme's meta helper class if available
 */
function get_meta_helper() {
	if ( ! class_exists( '\\CluckinChuck\\Wing_Location_Meta' ) ) {
		return null;
	}
	return '\\CluckinChuck\\Wing_Location_Meta';
}

/**
 * Register the block
 */
function register_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type(
		WING_REVIEW_PATH . 'build/wing-review',
		array(
			'render_callback' => __NAMESPACE__ . '\\render_callback',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_block' );

/**
 * Hook into comment approval to convert to block
 */
add_action( 'wp_set_comment_status', __NAMESPACE__ . '\\convert_to_block', 10, 2 );

/**
 * Recalculate stats when wing_location post is saved (editor changes to wing-review blocks)
 */
add_action( 'save_post_wing_location', __NAMESPACE__ . '\\on_save_post', 10, 2 );

function on_save_post( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	recalculate_location_stats( $post_id );
}

/**
 * Render the wing review block on the frontend
 */
function render_callback( $attributes ) {
	$reviewer_name     = esc_html( $attributes['reviewerName'] ?? '' );
	$rating            = floatval( $attributes['rating'] ?? 0 );
	$sauce_rating      = floatval( $attributes['sauceRating'] ?? 0 );
	$crispiness_rating = floatval( $attributes['crispinessRating'] ?? 0 );
	$review_text       = wp_kses_post( $attributes['reviewText'] ?? '' );
	$timestamp         = esc_html( $attributes['timestamp'] ?? '' );
	$sauces_tried      = esc_html( $attributes['saucesTried'] ?? '' );
	$wing_count        = intval( $attributes['wingCount'] ?? 0 );
	$total_price       = floatval( $attributes['totalPrice'] ?? 0 );
	$price_per_wing    = $wing_count > 0 ? round( $total_price / $wing_count, 2 ) : 0;

	$full_stars  = str_repeat( '★', (int) round( $rating ) );
	$empty_stars = str_repeat( '☆', 5 - (int) round( $rating ) );

	ob_start();
	?>
	<div class="wing-review">
		<div class="wing-review-header">
			<span class="wing-review-rating"><?php echo esc_html( $full_stars . $empty_stars ); ?></span>
			<span class="wing-review-meta">
				<?php if ( $reviewer_name ) : ?>
					<span class="wing-reviewer-name"><?php echo $reviewer_name; ?></span>
				<?php endif; ?>
				<?php if ( $timestamp ) : ?>
					<span class="wing-review-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $timestamp ) ) ); ?></span>
				<?php endif; ?>
			</span>
		</div>

		<?php if ( $sauce_rating > 0 || $crispiness_rating > 0 ) : ?>
			<div class="wing-review-sub-ratings">
				<?php if ( $sauce_rating > 0 ) : ?>
					<span class="wing-sub-rating">
						<strong><?php esc_html_e( 'Sauce:', 'wing-review' ); ?></strong>
						<?php echo esc_html( str_repeat( '★', (int) round( $sauce_rating ) ) . str_repeat( '☆', 5 - (int) round( $sauce_rating ) ) ); ?>
					</span>
				<?php endif; ?>
				<?php if ( $crispiness_rating > 0 ) : ?>
					<span class="wing-sub-rating">
						<strong><?php esc_html_e( 'Crispiness:', 'wing-review' ); ?></strong>
						<?php echo esc_html( str_repeat( '★', (int) round( $crispiness_rating ) ) . str_repeat( '☆', 5 - (int) round( $crispiness_rating ) ) ); ?>
					</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $wing_count > 0 && $total_price > 0 ) : ?>
			<div class="wing-review-pricing">
				<span class="wing-pricing-detail">
					<strong><?php esc_html_e( 'Wings:', 'wing-review' ); ?></strong> <?php echo esc_html( $wing_count ); ?>
				</span>
				<span class="wing-pricing-detail">
					<strong><?php esc_html_e( 'Price:', 'wing-review' ); ?></strong> $<?php echo esc_html( number_format( $total_price, 2 ) ); ?>
				</span>
				<span class="wing-pricing-detail wing-ppw">
					<strong><?php esc_html_e( 'PPW:', 'wing-review' ); ?></strong> $<?php echo esc_html( number_format( $price_per_wing, 2 ) ); ?>
				</span>
			</div>
		<?php endif; ?>

		<?php if ( $sauces_tried ) : ?>
			<div class="wing-review-sauces">
				<strong><?php esc_html_e( 'Sauces Tried:', 'wing-review' ); ?></strong> <?php echo $sauces_tried; ?>
			</div>
		<?php endif; ?>

		<?php if ( $review_text ) : ?>
			<div class="wing-review-text"><?php echo $review_text; ?></div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Convert approved comment to wing-review block
 *
 * Triggered when a comment status changes to 'approve'.
 * Reads comment metadata, builds wing-review block, appends to post content,
 * recalculates aggregate stats, then deletes the original comment.
 */
function convert_to_block( $comment_id, $status ) {
	if ( 'approve' !== $status ) {
		return;
	}

	$comment = get_comment( $comment_id );
	if ( ! $comment || 'wing_location' !== get_post_type( $comment->comment_post_ID ) ) {
		return;
	}

	$post_id           = $comment->comment_post_ID;
	$rating            = get_comment_meta( $comment_id, 'wing_rating', true );
	$sauce_rating      = get_comment_meta( $comment_id, 'wing_sauce_rating', true );
	$crispiness_rating = get_comment_meta( $comment_id, 'wing_crispiness_rating', true );
	$sauces_tried      = get_comment_meta( $comment_id, 'wing_sauces_tried', true );
	$wing_count        = get_comment_meta( $comment_id, 'wing_count', true );
	$total_price       = get_comment_meta( $comment_id, 'wing_total_price', true );

	$block_content = serialize_block(
		array(
			'blockName' => 'wing-review/wing-review',
			'attrs'     => array(
				'reviewerName'     => $comment->comment_author,
				'reviewerEmail'    => $comment->comment_author_email,
				'rating'           => floatval( $rating ),
				'sauceRating'      => floatval( $sauce_rating ),
				'crispinessRating' => floatval( $crispiness_rating ),
				'reviewText'       => $comment->comment_content,
				'timestamp'        => $comment->comment_date,
				'saucesTried'      => sanitize_text_field( $sauces_tried ),
				'wingCount'        => intval( $wing_count ),
				'totalPrice'       => floatval( $total_price ),
			),
		)
	);

	$post        = get_post( $post_id );
	$new_content = $post->post_content . "\n\n" . $block_content;

	wp_update_post(
		array(
			'ID'           => $post->ID,
			'post_content' => $new_content,
		)
	);

	recalculate_location_stats( $post_id );

	wp_delete_comment( $comment_id, true );
}

/**
 * Recalculate average rating, review count, and PPW stats from wing-review blocks
 */
function recalculate_location_stats( $post_id ) {
	$meta_helper = get_meta_helper();
	if ( ! $meta_helper ) {
		return;
	}

	$post   = get_post( $post_id );
	$blocks = parse_blocks( $post->post_content );

	$wing_reviews = array_filter( $blocks, function( $block ) {
		return 'wing-review/wing-review' === ( $block['blockName'] ?? '' );
	} );

	$review_count = count( $wing_reviews );
	$total_rating = 0;
	$ppw_values   = array();

	foreach ( $wing_reviews as $review ) {
		$total_rating += floatval( $review['attrs']['rating'] ?? 0 );

		$wing_count  = intval( $review['attrs']['wingCount'] ?? 0 );
		$total_price = floatval( $review['attrs']['totalPrice'] ?? 0 );

		if ( $wing_count > 0 && $total_price > 0 ) {
			$ppw_values[] = $total_price / $wing_count;
		}
	}

	$average_rating = $review_count > 0 ? round( $total_rating / $review_count, 2 ) : 0;
	$min_ppw        = ! empty( $ppw_values ) ? round( min( $ppw_values ), 2 ) : 0;
	$max_ppw        = ! empty( $ppw_values ) ? round( max( $ppw_values ), 2 ) : 0;
	$average_ppw    = ! empty( $ppw_values ) ? round( array_sum( $ppw_values ) / count( $ppw_values ), 2 ) : 0;

	$meta_helper::update_location_meta( $post_id, array(
		'wing_average_rating' => $average_rating,
		'wing_review_count'   => $review_count,
		'wing_average_ppw'    => $average_ppw,
		'wing_min_ppw'        => $min_ppw,
		'wing_max_ppw'        => $max_ppw,
	) );
}
