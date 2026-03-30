<?php
/**
 * Wing Review Abilities API — review management abilities.
 *
 * Registers four abilities:
 *   - cluckin-chuck/approve-review
 *   - cluckin-chuck/reject-review
 *   - cluckin-chuck/recalculate-stats
 *   - cluckin-chuck/list-reviews
 *
 * @package WingReview
 * @since 0.2.0
 */

namespace WingReview;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Review_Abilities {

	/**
	 * Prevent duplicate registration.
	 *
	 * @var bool
	 */
	private static bool $registered = false;

	/**
	 * Boot abilities registration.
	 */
	public function __construct() {
		if ( ! class_exists( 'WP_Ability' ) ) {
			return;
		}

		if ( self::$registered ) {
			return;
		}

		self::$registered = true;

		$this->register_abilities();
	}

	/**
	 * Register all review abilities.
	 */
	private function register_abilities(): void {
		$register = function () {
			$this->register_approve_review();
			$this->register_reject_review();
			$this->register_recalculate_stats();
			$this->register_list_reviews();
		};

		if ( doing_action( 'wp_abilities_api_init' ) ) {
			$register();
		} elseif ( ! did_action( 'wp_abilities_api_init' ) ) {
			add_action( 'wp_abilities_api_init', $register );
		}
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/approve-review
	// ------------------------------------------------------------------

	private function register_approve_review(): void {
		wp_register_ability(
			'cluckin-chuck/approve-review',
			array(
				'label'               => __( 'Approve Review', 'wing-review' ),
				'description'         => __( 'Approve a pending wing review comment, converting it to a block in the location\'s content and recalculating aggregate stats.', 'wing-review' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'comment_id' ),
					'properties' => array(
						'comment_id' => array(
							'type'        => 'integer',
							'description' => __( 'The pending review comment ID to approve.', 'wing-review' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'      => array( 'type' => 'boolean' ),
						'post_id'      => array( 'type' => 'integer' ),
						'reviewer'     => array( 'type' => 'string' ),
						'rating'       => array( 'type' => 'number' ),
						'review_count' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_approve_review' ),
				'permission_callback' => function () {
					return current_user_can( 'moderate_comments' ) || ( defined( 'WP_CLI' ) && WP_CLI );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'destructive' => true ),
				),
			)
		);
	}

	/**
	 * Execute: approve-review.
	 *
	 * Approves a pending comment, triggering the existing convert_to_block() hook
	 * which handles block conversion, stat recalculation, and comment deletion.
	 *
	 * @param array $input { comment_id: int }.
	 * @return array|\WP_Error
	 */
	public function execute_approve_review( array $input ) {
		$comment_id = absint( $input['comment_id'] ?? 0 );
		$comment    = get_comment( $comment_id );

		if ( ! $comment ) {
			return new \WP_Error(
				'not_found',
				__( 'Comment not found.', 'wing-review' ),
				array( 'status' => 404 )
			);
		}

		$post = get_post( $comment->comment_post_ID );

		if ( ! $post || 'wing_location' !== $post->post_type ) {
			return new \WP_Error(
				'invalid_post_type',
				__( 'Comment is not on a wing location post.', 'wing-review' ),
				array( 'status' => 400 )
			);
		}

		$rating = get_comment_meta( $comment_id, 'wing_rating', true );

		if ( empty( $rating ) ) {
			return new \WP_Error(
				'not_a_review',
				__( 'Comment does not have wing review metadata.', 'wing-review' ),
				array( 'status' => 400 )
			);
		}

		// Approve the comment — this fires wp_set_comment_status which triggers
		// convert_to_block() via the existing hook.
		$result = wp_set_comment_status( $comment_id, 'approve' );

		if ( ! $result ) {
			return new \WP_Error(
				'approval_failed',
				__( 'Failed to approve comment.', 'wing-review' ),
				array( 'status' => 500 )
			);
		}

		// Read back updated stats.
		$meta_helper = get_meta_helper();
		$meta        = $meta_helper ? $meta_helper::get_location_meta( $post->ID ) : array();

		return array(
			'success'      => true,
			'post_id'      => $post->ID,
			'reviewer'     => $comment->comment_author,
			'rating'       => floatval( $rating ),
			'review_count' => intval( $meta['wing_review_count'] ?? 0 ),
		);
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/reject-review
	// ------------------------------------------------------------------

	private function register_reject_review(): void {
		wp_register_ability(
			'cluckin-chuck/reject-review',
			array(
				'label'               => __( 'Reject Review', 'wing-review' ),
				'description'         => __( 'Reject a pending wing review comment by trashing it.', 'wing-review' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'comment_id' ),
					'properties' => array(
						'comment_id' => array(
							'type'        => 'integer',
							'description' => __( 'The pending review comment ID to reject.', 'wing-review' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'comment_id' => array( 'type' => 'integer' ),
						'post_id'    => array( 'type' => 'integer' ),
						'reviewer'   => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_reject_review' ),
				'permission_callback' => function () {
					return current_user_can( 'moderate_comments' ) || ( defined( 'WP_CLI' ) && WP_CLI );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'destructive' => true ),
				),
			)
		);
	}

	/**
	 * Execute: reject-review.
	 *
	 * Trashes a pending review comment.
	 *
	 * @param array $input { comment_id: int }.
	 * @return array|\WP_Error
	 */
	public function execute_reject_review( array $input ) {
		$comment_id = absint( $input['comment_id'] ?? 0 );
		$comment    = get_comment( $comment_id );

		if ( ! $comment ) {
			return new \WP_Error(
				'not_found',
				__( 'Comment not found.', 'wing-review' ),
				array( 'status' => 404 )
			);
		}

		$post = get_post( $comment->comment_post_ID );

		if ( ! $post || 'wing_location' !== $post->post_type ) {
			return new \WP_Error(
				'invalid_post_type',
				__( 'Comment is not on a wing location post.', 'wing-review' ),
				array( 'status' => 400 )
			);
		}

		$rating = get_comment_meta( $comment_id, 'wing_rating', true );

		if ( empty( $rating ) ) {
			return new \WP_Error(
				'not_a_review',
				__( 'Comment does not have wing review metadata.', 'wing-review' ),
				array( 'status' => 400 )
			);
		}

		$result = wp_trash_comment( $comment_id );

		if ( ! $result ) {
			return new \WP_Error(
				'rejection_failed',
				__( 'Failed to trash comment.', 'wing-review' ),
				array( 'status' => 500 )
			);
		}

		return array(
			'success'    => true,
			'comment_id' => $comment_id,
			'post_id'    => $post->ID,
			'reviewer'   => $comment->comment_author,
		);
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/recalculate-stats
	// ------------------------------------------------------------------

	private function register_recalculate_stats(): void {
		wp_register_ability(
			'cluckin-chuck/recalculate-stats',
			array(
				'label'               => __( 'Recalculate Stats', 'wing-review' ),
				'description'         => __( 'Recalculate aggregate rating, review count, and price-per-wing stats for a wing location from its review blocks.', 'wing-review' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The wing_location post ID.', 'wing-review' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'        => array( 'type' => 'boolean' ),
						'post_id'        => array( 'type' => 'integer' ),
						'review_count'   => array( 'type' => 'integer' ),
						'average_rating' => array( 'type' => 'number' ),
						'average_ppw'    => array( 'type' => 'number' ),
						'min_ppw'        => array( 'type' => 'number' ),
						'max_ppw'        => array( 'type' => 'number' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_recalculate_stats' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' ) || ( defined( 'WP_CLI' ) && WP_CLI );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'idempotent' => true ),
				),
			)
		);
	}

	/**
	 * Execute: recalculate-stats.
	 *
	 * @param array $input { post_id: int }.
	 * @return array|\WP_Error
	 */
	public function execute_recalculate_stats( array $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post || 'wing_location' !== $post->post_type ) {
			return new \WP_Error(
				'not_found',
				__( 'Wing location not found.', 'wing-review' ),
				array( 'status' => 404 )
			);
		}

		recalculate_location_stats( $post_id );

		$meta_helper = get_meta_helper();
		$meta        = $meta_helper ? $meta_helper::get_location_meta( $post_id ) : array();

		return array(
			'success'        => true,
			'post_id'        => $post_id,
			'review_count'   => intval( $meta['wing_review_count'] ?? 0 ),
			'average_rating' => floatval( $meta['wing_average_rating'] ?? 0 ),
			'average_ppw'    => floatval( $meta['wing_average_ppw'] ?? 0 ),
			'min_ppw'        => floatval( $meta['wing_min_ppw'] ?? 0 ),
			'max_ppw'        => floatval( $meta['wing_max_ppw'] ?? 0 ),
		);
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/list-reviews
	// ------------------------------------------------------------------

	private function register_list_reviews(): void {
		wp_register_ability(
			'cluckin-chuck/list-reviews',
			array(
				'label'               => __( 'List Reviews', 'wing-review' ),
				'description'         => __( 'List all reviews for a wing location by parsing review blocks from the post content.', 'wing-review' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The wing_location post ID.', 'wing-review' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
						'reviews' => array( 'type' => 'array' ),
						'count'   => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_reviews' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' ) || ( defined( 'WP_CLI' ) && WP_CLI );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'readonly' => true ),
				),
			)
		);
	}

	/**
	 * Execute: list-reviews.
	 *
	 * Parses wing-review blocks from post content and returns structured data.
	 *
	 * @param array $input { post_id: int }.
	 * @return array|\WP_Error
	 */
	public function execute_list_reviews( array $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post || 'wing_location' !== $post->post_type ) {
			return new \WP_Error(
				'not_found',
				__( 'Wing location not found.', 'wing-review' ),
				array( 'status' => 404 )
			);
		}

		$blocks  = parse_blocks( $post->post_content );
		$reviews = array();

		foreach ( $blocks as $block ) {
			if ( 'wing-review/wing-review' !== ( $block['blockName'] ?? '' ) ) {
				continue;
			}

			$attrs      = $block['attrs'] ?? array();
			$wing_count = intval( $attrs['wingCount'] ?? 0 );
			$total_price = floatval( $attrs['totalPrice'] ?? 0 );

			$reviews[] = array(
				'reviewer_name'     => $attrs['reviewerName'] ?? '',
				'rating'            => floatval( $attrs['rating'] ?? 0 ),
				'sauce_rating'      => floatval( $attrs['sauceRating'] ?? 0 ),
				'crispiness_rating' => floatval( $attrs['crispinessRating'] ?? 0 ),
				'review_text'       => $attrs['reviewText'] ?? '',
				'timestamp'         => $attrs['timestamp'] ?? '',
				'sauces_tried'      => $attrs['saucesTried'] ?? '',
				'wing_count'        => $wing_count,
				'total_price'       => $total_price,
				'ppw'               => $wing_count > 0 ? round( $total_price / $wing_count, 2 ) : 0,
			);
		}

		return array(
			'post_id' => $post_id,
			'reviews' => $reviews,
			'count'   => count( $reviews ),
		);
	}
}
