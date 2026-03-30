<?php
/**
 * Wing Review Submit Abilities API — submission abilities.
 *
 * Registers three abilities:
 *   - cluckin-chuck/submit-review
 *   - cluckin-chuck/submit-location
 *   - cluckin-chuck/list-pending
 *
 * Fires actions for side-effects (admin email notifications):
 *   - cluckin_chuck_review_submitted( int $comment_id, array $data, string $location_name )
 *   - cluckin_chuck_location_submitted( int $post_id, array $data )
 *
 * @package WingReviewSubmit
 * @since 0.2.0
 */

namespace WingReviewSubmit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Submit_Abilities {

	/**
	 * Prevent duplicate registration.
	 *
	 * @var bool
	 */
	private static bool $registered = false;

	/**
	 * Boot abilities registration and hook admin email to actions.
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
		$this->register_notification_hooks();
	}

	/**
	 * Register all submission abilities.
	 */
	private function register_abilities(): void {
		$register = function () {
			$this->register_submit_review();
			$this->register_submit_location();
			$this->register_list_pending();
		};

		if ( doing_action( 'wp_abilities_api_init' ) ) {
			$register();
		} elseif ( ! did_action( 'wp_abilities_api_init' ) ) {
			add_action( 'wp_abilities_api_init', $register );
		}
	}

	/**
	 * Hook admin email notifications to submission actions.
	 */
	private function register_notification_hooks(): void {
		add_action( 'cluckin_chuck_review_submitted', array( $this, 'notify_admin_review' ), 10, 3 );
		add_action( 'cluckin_chuck_location_submitted', array( $this, 'notify_admin_location' ), 10, 2 );
	}

	/**
	 * Send admin email when a review is submitted.
	 *
	 * @param int    $comment_id    The new comment ID.
	 * @param array  $data          Sanitized submission data.
	 * @param string $location_name The wing location title.
	 */
	public function notify_admin_review( int $comment_id, array $data, string $location_name ): void {
		$comment_id; // Intentionally unused — available for future extensions.

		$admin_email = get_option( 'admin_email' );
		$subject     = 'New Wing Review Submitted - ' . $location_name;
		$message     = "A new review has been submitted for {$location_name}.\n\n";
		$message    .= "Reviewer: {$data['reviewer_name']} ({$data['reviewer_email']})\n";
		$message    .= "Overall Rating: {$data['rating']}/5\n\n";
		$message    .= 'Moderate Comments: ' . admin_url( 'edit-comments.php?comment_status=moderated' );

		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Send admin email when a new location is submitted.
	 *
	 * @param int   $post_id The new pending post ID.
	 * @param array $data    Sanitized submission data.
	 */
	public function notify_admin_location( int $post_id, array $data ): void {
		$post_id; // Intentionally unused — available for future extensions.

		$admin_email = get_option( 'admin_email' );
		$subject     = 'New Wing Location Pending Review - ' . $data['location_name'];
		$message     = "A new wing location has been submitted and is pending review.\n\n";
		$message    .= "Location: {$data['location_name']}\n";
		$message    .= "Submitted by: {$data['reviewer_name']} ({$data['reviewer_email']})\n";
		$message    .= "Address: {$data['address']}\n";
		$message    .= "Overall Rating: {$data['rating']}/5\n\n";
		$message    .= 'Review Posts: ' . admin_url( 'edit.php?post_type=wing_location&post_status=pending' );

		wp_mail( $admin_email, $subject, $message );
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/submit-review
	// ------------------------------------------------------------------

	private function register_submit_review(): void {
		wp_register_ability(
			'cluckin-chuck/submit-review',
			array(
				'label'               => __( 'Submit Review', 'wing-review-submit' ),
				'description'         => __( 'Submit a new wing review for an existing location. Creates a pending comment awaiting moderation. Auto-approved when submitted by a user with moderation privileges.', 'wing-review-submit' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'reviewer_name', 'reviewer_email', 'rating', 'review_text' ),
					'properties' => array(
						'post_id'           => array(
							'type'        => 'integer',
							'description' => __( 'The wing_location post ID to review.', 'wing-review-submit' ),
						),
						'reviewer_name'     => array(
							'type'        => 'string',
							'description' => __( 'Reviewer\'s name.', 'wing-review-submit' ),
						),
						'reviewer_email'    => array(
							'type'        => 'string',
							'format'      => 'email',
							'description' => __( 'Reviewer\'s email.', 'wing-review-submit' ),
						),
						'rating'            => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 5,
							'description' => __( 'Overall rating (1-5).', 'wing-review-submit' ),
						),
						'review_text'       => array(
							'type'        => 'string',
							'description' => __( 'Review text content.', 'wing-review-submit' ),
						),
						'sauce_rating'      => array(
							'type'    => 'integer',
							'minimum' => 0,
							'maximum' => 5,
							'default' => 0,
						),
						'crispiness_rating' => array(
							'type'    => 'integer',
							'minimum' => 0,
							'maximum' => 5,
							'default' => 0,
						),
						'wing_count'        => array(
							'type'    => 'integer',
							'minimum' => 0,
							'default' => 0,
						),
						'total_price'       => array(
							'type'    => 'number',
							'minimum' => 0,
							'default' => 0,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'       => array( 'type' => 'boolean' ),
						'comment_id'    => array( 'type' => 'integer' ),
						'post_id'       => array( 'type' => 'integer' ),
						'auto_approved' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_submit_review' ),
				'permission_callback' => function () {
					// Public ability — rate-limiting is handled in execution.
					return true;
				},
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Execute: submit-review.
	 *
	 * @param array $input Submission data.
	 * @return array|\WP_Error
	 */
	public function execute_submit_review( array $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post || 'wing_location' !== $post->post_type ) {
			return new \WP_Error(
				'not_found',
				__( 'Wing location not found.', 'wing-review-submit' ),
				array( 'status' => 404 )
			);
		}

		$data = array(
			'reviewer_name'     => sanitize_text_field( $input['reviewer_name'] ?? '' ),
			'reviewer_email'    => sanitize_email( $input['reviewer_email'] ?? '' ),
			'rating'            => absint( $input['rating'] ?? 0 ),
			'sauce_rating'      => absint( $input['sauce_rating'] ?? 0 ),
			'crispiness_rating' => absint( $input['crispiness_rating'] ?? 0 ),
			'review_text'       => sanitize_textarea_field( $input['review_text'] ?? '' ),
			'wing_count'        => absint( $input['wing_count'] ?? 0 ),
			'total_price'       => floatval( $input['total_price'] ?? 0 ),
		);

		if ( empty( $data['reviewer_name'] ) || empty( $data['reviewer_email'] ) || empty( $data['review_text'] ) ) {
			return new \WP_Error(
				'missing_fields',
				__( 'Name, email, and review text are required.', 'wing-review-submit' ),
				array( 'status' => 400 )
			);
		}

		if ( $data['rating'] < 1 || $data['rating'] > 5 ) {
			return new \WP_Error(
				'invalid_rating',
				__( 'Rating must be between 1 and 5.', 'wing-review-submit' ),
				array( 'status' => 400 )
			);
		}

		$ppw = ( $data['wing_count'] > 0 && $data['total_price'] > 0 )
			? round( $data['total_price'] / $data['wing_count'], 2 )
			: 0;

		$data['ppw'] = $ppw;

		$comment_id = create_pending_review_comment( $post_id, $data );

		if ( is_wp_error( $comment_id ) ) {
			return $comment_id;
		}

		$auto_approved = false;

		// Auto-approve reviews submitted by users who can moderate comments.
		if ( current_user_can( 'moderate_comments' ) ) {
			wp_set_comment_status( $comment_id, 'approve' );
			$auto_approved = true;
		}

		return array(
			'success'       => true,
			'comment_id'    => $comment_id,
			'post_id'       => $post_id,
			'auto_approved' => $auto_approved,
		);
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/submit-location
	// ------------------------------------------------------------------

	private function register_submit_location(): void {
		wp_register_ability(
			'cluckin-chuck/submit-location',
			array(
				'label'               => __( 'Submit Location', 'wing-review-submit' ),
				'description'         => __( 'Submit a new wing location with an initial review. Creates a pending post awaiting admin approval. Auto-published when submitted by a user with publish privileges.', 'wing-review-submit' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'location_name', 'address', 'latitude', 'longitude', 'reviewer_name', 'reviewer_email', 'rating', 'review_text' ),
					'properties' => array(
						'location_name'     => array(
							'type'        => 'string',
							'description' => __( 'Name of the wing location.', 'wing-review-submit' ),
						),
						'address'           => array(
							'type'        => 'string',
							'description' => __( 'Street address.', 'wing-review-submit' ),
						),
						'latitude'          => array( 'type' => 'number' ),
						'longitude'         => array( 'type' => 'number' ),
						'website'           => array(
							'type'    => 'string',
							'default' => '',
						),
						'instagram'         => array(
							'type'    => 'string',
							'default' => '',
						),
						'reviewer_name'     => array( 'type' => 'string' ),
						'reviewer_email'    => array(
							'type'   => 'string',
							'format' => 'email',
						),
						'rating'            => array(
							'type'    => 'integer',
							'minimum' => 1,
							'maximum' => 5,
						),
						'review_text'       => array( 'type' => 'string' ),
						'sauce_rating'      => array(
							'type'    => 'integer',
							'minimum' => 0,
							'maximum' => 5,
							'default' => 0,
						),
						'crispiness_rating' => array(
							'type'    => 'integer',
							'minimum' => 0,
							'maximum' => 5,
							'default' => 0,
						),
						'wing_count'        => array(
							'type'    => 'integer',
							'minimum' => 0,
							'default' => 0,
						),
						'total_price'       => array(
							'type'    => 'number',
							'minimum' => 0,
							'default' => 0,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'        => array( 'type' => 'boolean' ),
						'post_id'        => array( 'type' => 'integer' ),
						'auto_published' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_submit_location' ),
				'permission_callback' => function () {
					return true;
				},
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Execute: submit-location.
	 *
	 * @param array $input Submission data.
	 * @return array|\WP_Error
	 */
	public function execute_submit_location( array $input ) {
		$data = array(
			'location_name'     => sanitize_text_field( $input['location_name'] ?? '' ),
			'address'           => sanitize_text_field( $input['address'] ?? '' ),
			'latitude'          => floatval( $input['latitude'] ?? 0 ),
			'longitude'         => floatval( $input['longitude'] ?? 0 ),
			'website'           => esc_url_raw( $input['website'] ?? '' ),
			'instagram'         => esc_url_raw( $input['instagram'] ?? '' ),
			'reviewer_name'     => sanitize_text_field( $input['reviewer_name'] ?? '' ),
			'reviewer_email'    => sanitize_email( $input['reviewer_email'] ?? '' ),
			'rating'            => absint( $input['rating'] ?? 0 ),
			'sauce_rating'      => absint( $input['sauce_rating'] ?? 0 ),
			'crispiness_rating' => absint( $input['crispiness_rating'] ?? 0 ),
			'review_text'       => sanitize_textarea_field( $input['review_text'] ?? '' ),
			'wing_count'        => absint( $input['wing_count'] ?? 0 ),
			'total_price'       => floatval( $input['total_price'] ?? 0 ),
		);

		$ppw         = ( $data['wing_count'] > 0 && $data['total_price'] > 0 )
			? round( $data['total_price'] / $data['wing_count'], 2 )
			: 0;
		$data['ppw'] = $ppw;

		// Validate required fields.
		$required = array( 'location_name', 'address', 'reviewer_name', 'reviewer_email', 'review_text' );
		foreach ( $required as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return new \WP_Error(
					'missing_field',
					/* translators: %s: field name */
					sprintf( __( 'Required field missing: %s', 'wing-review-submit' ), $field ),
					array( 'status' => 400 )
				);
			}
		}

		if ( $data['rating'] < 1 || $data['rating'] > 5 ) {
			return new \WP_Error( 'invalid_rating', __( 'Rating must be between 1 and 5.', 'wing-review-submit' ), array( 'status' => 400 ) );
		}

		if ( empty( $data['latitude'] ) || empty( $data['longitude'] ) ) {
			return new \WP_Error( 'missing_coordinates', __( 'Latitude and longitude are required.', 'wing-review-submit' ), array( 'status' => 400 ) );
		}

		$post_id = create_pending_location( $data );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$auto_published = false;

		// Auto-publish locations submitted by users who can publish posts.
		if ( current_user_can( 'publish_posts' ) ) {
			wp_publish_post( $post_id );
			$auto_published = true;
		}

		return array(
			'success'        => true,
			'post_id'        => $post_id,
			'auto_published' => $auto_published,
		);
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/list-pending
	// ------------------------------------------------------------------

	private function register_list_pending(): void {
		wp_register_ability(
			'cluckin-chuck/list-pending',
			array(
				'label'               => __( 'List Pending', 'wing-review-submit' ),
				'description'         => __( 'List pending wing location submissions and pending review comments awaiting moderation.', 'wing-review-submit' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'type' => array(
							'type'        => 'string',
							'enum'        => array( 'all', 'locations', 'reviews' ),
							'default'     => 'all',
							'description' => __( 'Filter by type: all, locations, or reviews.', 'wing-review-submit' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'pending_locations' => array( 'type' => 'array' ),
						'pending_reviews'   => array( 'type' => 'array' ),
						'total_locations'   => array( 'type' => 'integer' ),
						'total_reviews'     => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_pending' ),
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
	 * Execute: list-pending.
	 *
	 * @param array $input { type: 'all'|'locations'|'reviews' }.
	 * @return array
	 */
	public function execute_list_pending( array $input ): array {
		$type              = sanitize_text_field( $input['type'] ?? 'all' );
		$pending_locations = array();
		$pending_reviews   = array();

		// Pending locations.
		if ( 'all' === $type || 'locations' === $type ) {
			$location_query = new \WP_Query( array(
				'post_type'      => 'wing_location',
				'post_status'    => 'pending',
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
			) );

			foreach ( $location_query->posts as $post ) {
				$meta_helper = get_meta_helper();
				$meta        = $meta_helper ? $meta_helper::get_location_meta( $post->ID ) : array();

				$pending_locations[] = array(
					'post_id' => $post->ID,
					'title'   => $post->post_title,
					'date'    => $post->post_date,
					'address' => $meta['wing_address'] ?? '',
					'rating'  => $meta['wing_average_rating'] ?? 0,
				);
			}
		}

		// Pending review comments on wing_location posts.
		if ( 'all' === $type || 'reviews' === $type ) {
			$comments = get_comments( array(
				'post_type' => 'wing_location',
				'status'    => 'hold',
				'number'    => 50,
				'orderby'   => 'comment_date',
				'order'     => 'DESC',
			) );

			foreach ( $comments as $comment ) {
				$rating = get_comment_meta( $comment->comment_ID, 'wing_rating', true );

				// Only include comments with wing review metadata.
				if ( empty( $rating ) ) {
					continue;
				}

				$pending_reviews[] = array(
					'comment_id'    => $comment->comment_ID,
					'post_id'       => $comment->comment_post_ID,
					'location_name' => get_the_title( $comment->comment_post_ID ),
					'reviewer'      => $comment->comment_author,
					'email'         => $comment->comment_author_email,
					'rating'        => intval( $rating ),
					'excerpt'       => wp_trim_words( $comment->comment_content, 20 ),
					'date'          => $comment->comment_date,
				);
			}
		}

		return array(
			'pending_locations' => $pending_locations,
			'pending_reviews'   => $pending_reviews,
			'total_locations'   => count( $pending_locations ),
			'total_reviews'     => count( $pending_reviews ),
		);
	}
}
