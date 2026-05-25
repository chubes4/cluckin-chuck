<?php
/**
 * Wing Review Submit Abilities API — submission abilities.
 *
 * Registers five abilities:
 *   - cluckin-chuck/submit-review
 *   - cluckin-chuck/submit-location
 *   - cluckin-chuck/approve-location
 *   - cluckin-chuck/reject-location
 *   - cluckin-chuck/list-pending
 *
 * Fires actions for side-effects (admin email notifications):
 *   - cluckin_chuck_review_submitted( int $comment_id, array $data, string $location_name, bool $auto_approved )
 *   - cluckin_chuck_location_submitted( int $post_id, array $data, bool $auto_published )
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
			$this->register_attach_location_image();
			$this->register_approve_location();
			$this->register_reject_location();
			$this->register_list_pending();
		};

		if ( doing_action( 'wp_abilities_api_init' ) ) {
			$register();
		} elseif ( ! did_action( 'wp_abilities_api_init' ) ) {
			add_action( 'wp_abilities_api_init', $register );
		}
	}

	/**
	 * Hook admin + submitter email notifications to submission actions.
	 *
	 * The `cluckin_chuck_*_submitted` actions fire from inside the execute_*
	 * callbacks AFTER the auto-approve/auto-publish branch runs, so the
	 * `$auto_*` boolean reflects the true post/comment state at hook time.
	 */
	private function register_notification_hooks(): void {
		add_action( 'cluckin_chuck_review_submitted',   array( $this, 'notify_admin_review' ),     10, 4 );
		add_action( 'cluckin_chuck_review_submitted',   array( $this, 'notify_submitter_review' ), 10, 4 );
		add_action( 'cluckin_chuck_location_submitted', array( $this, 'notify_admin_location' ),     10, 3 );
		add_action( 'cluckin_chuck_location_submitted', array( $this, 'notify_submitter_location' ), 10, 3 );
	}

	/**
	 * Send admin email when a review is submitted.
	 *
	 * Subject + body branch on whether the review was auto-approved so the
	 * admin doesn't get a "pending" email for a review that's already live.
	 *
	 * @param int    $comment_id    The new comment ID.
	 * @param array  $data          Sanitized submission data.
	 * @param string $location_name The wing location title.
	 * @param bool   $auto_approved Whether the review was auto-approved on submission.
	 */
	public function notify_admin_review( int $comment_id, array $data, string $location_name, bool $auto_approved = false ): void {
		$admin_email = get_option( 'admin_email' );

		if ( $auto_approved ) {
			$subject  = 'New Wing Review Published - ' . $location_name;
			$message  = "A new review for {$location_name} was auto-approved on submission.\n\n";
			$action_label = 'View Review';
			$action_url   = get_comment_link( $comment_id );
		} else {
			$subject  = 'New Wing Review Pending Moderation - ' . $location_name;
			$message  = "A new review has been submitted for {$location_name} and is pending moderation.\n\n";
			$action_label = 'Moderate Comments';
			$action_url   = admin_url( 'edit-comments.php?comment_status=moderated' );
		}

		$message .= "Reviewer: {$data['reviewer_name']} ({$data['reviewer_email']})\n";
		$message .= "Overall Rating: {$data['rating']}/5\n\n";
		$message .= "{$action_label}: {$action_url}";

		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Send confirmation email to the reviewer themselves.
	 *
	 * Bails when the reviewer email matches the site admin_email to avoid
	 * sending an admin two copies of the same notification.
	 *
	 * @param int    $comment_id    The new comment ID.
	 * @param array  $data          Sanitized submission data.
	 * @param string $location_name The wing location title.
	 * @param bool   $auto_approved Whether the review was auto-approved on submission.
	 */
	public function notify_submitter_review( int $comment_id, array $data, string $location_name, bool $auto_approved = false ): void {
		$comment_id; // Intentionally unused — available for future extensions.

		$reviewer_email = (string) ( $data['reviewer_email'] ?? '' );
		if ( '' === $reviewer_email || ! is_email( $reviewer_email ) ) {
			return;
		}
		if ( strcasecmp( $reviewer_email, (string) get_option( 'admin_email' ) ) === 0 ) {
			return;
		}

		if ( $auto_approved ) {
			$subject = "Your review of {$location_name} is live";
			$message = "Thanks for sharing your wings take! Your review of {$location_name} was published.\n\n";
		} else {
			$subject = "Thanks for reviewing {$location_name}";
			$message = "Thanks for sharing your wings take! Your review of {$location_name} has been received and is pending moderation.\n\n";
		}

		$message .= "Rating: {$data['rating']}/5\n";
		if ( ! empty( $data['review_text'] ) ) {
			$message .= "Your words: \"{$data['review_text']}\"\n\n";
		}

		wp_mail( $reviewer_email, $subject, $message );
	}

	/**
	 * Send admin email when a new location is submitted.
	 *
	 * Subject + body branch on whether the location was auto-published so
	 * the admin doesn't get a "pending review" email for a post that's
	 * already live.
	 *
	 * @param int   $post_id        The new post ID.
	 * @param array $data           Sanitized submission data.
	 * @param bool  $auto_published Whether the location was auto-published on submission.
	 */
	public function notify_admin_location( int $post_id, array $data, bool $auto_published = false ): void {
		$admin_email = get_option( 'admin_email' );

		if ( $auto_published ) {
			$subject      = 'New Wing Location Published - ' . $data['location_name'];
			$message      = "A new wing location was submitted and auto-published.\n\n";
			$action_label = 'View Location';
			$action_url   = get_permalink( $post_id );
		} else {
			$subject      = 'New Wing Location Pending Review - ' . $data['location_name'];
			$message      = "A new wing location has been submitted and is pending review.\n\n";
			$action_label = 'Review Pending Locations';
			$action_url   = admin_url( 'edit.php?post_type=wing_location&post_status=pending' );
		}

		$message .= "Location: {$data['location_name']}\n";
		$message .= "Submitted by: {$data['reviewer_name']} ({$data['reviewer_email']})\n";
		$message .= "Address: {$data['address']}\n";
		$message .= "Overall Rating: {$data['rating']}/5\n\n";
		$message .= "{$action_label}: {$action_url}";

		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Send confirmation email to the submitter themselves.
	 *
	 * Bails when the submitter email matches the site admin_email to avoid
	 * sending an admin two copies of the same notification.
	 *
	 * @param int   $post_id        The new post ID.
	 * @param array $data           Sanitized submission data.
	 * @param bool  $auto_published Whether the location was auto-published on submission.
	 */
	public function notify_submitter_location( int $post_id, array $data, bool $auto_published = false ): void {
		$reviewer_email = (string) ( $data['reviewer_email'] ?? '' );
		if ( '' === $reviewer_email || ! is_email( $reviewer_email ) ) {
			return;
		}
		if ( strcasecmp( $reviewer_email, (string) get_option( 'admin_email' ) ) === 0 ) {
			return;
		}

		if ( $auto_published ) {
			$subject = "Your wing spot is live: {$data['location_name']}";
			$message = "Your submission for {$data['location_name']} was published.\n\n";
			$message .= "View it: " . get_permalink( $post_id ) . "\n\n";
		} else {
			$subject = "Thanks for submitting {$data['location_name']}";
			$message = "Thanks for adding a new wing spot! {$data['location_name']} has been received and is pending review.\n";
			$message .= "We'll let you know when it goes live.\n\n";
		}

		$message .= "Address: {$data['address']}\n";
		$message .= "Rating: {$data['rating']}/5\n";

		wp_mail( $reviewer_email, $subject, $message );
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
					'required'   => array( 'post_id', 'rating', 'review_text' ),
					'properties' => array(
						'post_id'           => array(
							'type'        => 'integer',
							'description' => __( 'The wing_location post ID to review.', 'wing-review-submit' ),
						),
					'reviewer_name'     => array(
						'type'        => 'string',
						'description' => __( 'Reviewer\'s display name. Optional for logged-in users — auto-filled from account when omitted or empty. Required for anonymous submissions through the form block.', 'wing-review-submit' ),
					),
					'reviewer_email'    => array(
						'type'        => 'string',
						'description' => __( 'Reviewer\'s email address. Optional for logged-in users — auto-filled from account when omitted or empty. Required for anonymous submissions through the form block. Email format is validated post-autofill inside execute_callback so empty values don\'t block logged-in submissions.', 'wing-review-submit' ),
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

		// Auto-fill reviewer identity from the logged-in user. The ability's
		// input_schema deliberately does NOT enforce format=email or require
		// these fields, because the chat agent never passes them — it doesn't
		// even see them in its tool surface (see cluckin-chuck-agent-kit's
		// SubmitTools::get_submit_review_def). Validation runs below, after
		// the autofill has had a chance to populate values from wp_get_current_user().
		$current_user = wp_get_current_user();
		if ( $current_user->ID ) {
			if ( empty( $data['reviewer_name'] ) ) {
				$data['reviewer_name'] = $current_user->display_name;
			}
			if ( empty( $data['reviewer_email'] ) ) {
				$data['reviewer_email'] = $current_user->user_email;
			}
		}

		if ( empty( $data['reviewer_name'] ) || empty( $data['reviewer_email'] ) || empty( $data['review_text'] ) ) {
			return new \WP_Error(
				'missing_fields',
				__( 'Name, email, and review text are required. Log in or provide them manually.', 'wing-review-submit' ),
				array( 'status' => 400 )
			);
		}

		if ( ! is_email( $data['reviewer_email'] ) ) {
			return new \WP_Error(
				'invalid_email',
				__( 'Reviewer email is not a valid email address.', 'wing-review-submit' ),
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

		/**
		 * Fires after a wing review is submitted, AFTER auto-approval state is known.
		 *
		 * @since 0.2.0
		 *
		 * @param int    $comment_id    The new comment ID.
		 * @param array  $data          Sanitized submission data.
		 * @param string $location_name The wing location title.
		 * @param bool   $auto_approved Whether the review was auto-approved on submission.
		 */
		do_action( 'cluckin_chuck_review_submitted', $comment_id, $data, get_the_title( $post_id ), $auto_approved );

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
					'required'   => array( 'location_name', 'address', 'latitude', 'longitude', 'rating', 'review_text' ),
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
					'reviewer_name'     => array(
						'type'        => 'string',
						'description' => __( 'Reviewer\'s display name. Optional for logged-in users — auto-filled from account when omitted or empty. Required for anonymous submissions through the form block.', 'wing-review-submit' ),
					),
					'reviewer_email'    => array(
						'type'        => 'string',
						'description' => __( 'Reviewer\'s email address. Optional for logged-in users — auto-filled from account when omitted or empty. Required for anonymous submissions through the form block. Email format is validated post-autofill inside execute_callback so empty values don\'t block logged-in submissions.', 'wing-review-submit' ),
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

		// Auto-fill reviewer identity from the logged-in user. The ability's
		// input_schema deliberately does NOT enforce format=email or require
		// these fields, because the chat agent never passes them — it doesn't
		// even see them in its tool surface (see cluckin-chuck-agent-kit's
		// SubmitTools::get_submit_location_def). Validation runs below, after
		// the autofill has had a chance to populate values from wp_get_current_user().
		$current_user = wp_get_current_user();
		if ( $current_user->ID ) {
			if ( empty( $data['reviewer_name'] ) ) {
				$data['reviewer_name'] = $current_user->display_name;
			}
			if ( empty( $data['reviewer_email'] ) ) {
				$data['reviewer_email'] = $current_user->user_email;
			}
		}

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

		if ( ! is_email( $data['reviewer_email'] ) ) {
			return new \WP_Error(
				'invalid_email',
				__( 'Reviewer email is not a valid email address.', 'wing-review-submit' ),
				array( 'status' => 400 )
			);
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

		/**
		 * Fires after a new wing location is submitted, AFTER auto-publish state is known.
		 *
		 * @since 0.2.0
		 *
		 * @param int   $post_id        The new post ID.
		 * @param array $data           Sanitized submission data.
		 * @param bool  $auto_published Whether the location was auto-published on submission.
		 */
		do_action( 'cluckin_chuck_location_submitted', $post_id, $data, $auto_published );

		return array(
			'success'        => true,
			'post_id'        => $post_id,
			'auto_published' => $auto_published,
		);
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/attach-location-image
	// ------------------------------------------------------------------

	private function register_attach_location_image(): void {
		wp_register_ability(
			'cluckin-chuck/attach-location-image',
			array(
				'label'               => __( 'Attach Location Image', 'wing-review-submit' ),
				'description'         => __( 'Attach an uploaded media library image to a wing_location as its featured image. Pass the post_id and the media library attachment_id (e.g. from a chat image upload).', 'wing-review-submit' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'media_id' ),
					'properties' => array(
						'post_id'  => array(
							'type'        => 'integer',
							'description' => __( 'The wing_location post ID to attach the image to.', 'wing-review-submit' ),
						),
						'media_id' => array(
							'type'        => 'integer',
							'description' => __( 'The WordPress media library attachment ID to set as the featured image. Available on prior chat user messages via metadata.attachments[].media_id.', 'wing-review-submit' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'post_id'    => array( 'type' => 'integer' ),
						'media_id'   => array( 'type' => 'integer' ),
						'image_url'  => array( 'type' => 'string' ),
						'post_title' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_attach_location_image' ),
				'permission_callback' => function () {
					// Only users who can edit the wing_location post type. The
					// per-post check happens inside the callback because we
					// don't have the post_id yet at this layer.
					$cpt = get_post_type_object( 'wing_location' );
					$cap = $cpt && isset( $cpt->cap->edit_posts ) ? $cpt->cap->edit_posts : 'edit_posts';
					return current_user_can( $cap );
				},
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Execute: attach-location-image.
	 *
	 * Sets a media library attachment as the wing_location's featured image.
	 *
	 * @param array $input { post_id: int, media_id: int }.
	 * @return array|\WP_Error
	 */
	public function execute_attach_location_image( array $input ) {
		$post_id  = absint( $input['post_id'] ?? 0 );
		$media_id = absint( $input['media_id'] ?? 0 );

		if ( $post_id <= 0 || $media_id <= 0 ) {
			return new \WP_Error(
				'missing_field',
				__( 'Both post_id and media_id are required.', 'wing-review-submit' ),
				array( 'status' => 400 )
			);
		}

		$post = get_post( $post_id );
		if ( ! $post || 'wing_location' !== $post->post_type ) {
			return new \WP_Error(
				'not_found',
				__( 'Wing location not found.', 'wing-review-submit' ),
				array( 'status' => 404 )
			);
		}

		$attachment = get_post( $media_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return new \WP_Error(
				'attachment_not_found',
				__( 'Media library attachment not found.', 'wing-review-submit' ),
				array( 'status' => 404 )
			);
		}

		$mime = (string) get_post_mime_type( $media_id );
		if ( 0 !== strpos( $mime, 'image/' ) ) {
			return new \WP_Error(
				'invalid_attachment_type',
				sprintf(
					/* translators: %s: actual MIME type */
					__( 'Attachment must be an image. Got: %s', 'wing-review-submit' ),
					$mime
				),
				array( 'status' => 400 )
			);
		}

		// Per-post capability check — couldn't run in permission_callback
		// because post_id isn't available there.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error(
				'forbidden',
				__( 'You do not have permission to attach images to this location.', 'wing-review-submit' ),
				array( 'status' => 403 )
			);
		}

		set_post_thumbnail( $post_id, $media_id );

		return array(
			'success'    => true,
			'post_id'    => $post_id,
			'media_id'   => $media_id,
			'image_url'  => (string) wp_get_attachment_url( $media_id ),
			'post_title' => get_the_title( $post_id ),
		);
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/approve-location
	// ------------------------------------------------------------------

	private function register_approve_location(): void {
		wp_register_ability(
			'cluckin-chuck/approve-location',
			array(
				'label'               => __( 'Approve Location', 'wing-review-submit' ),
				'description'         => __( 'Publish a pending wing location submission.', 'wing-review-submit' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The pending wing_location post ID to publish.', 'wing-review-submit' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'post_id' => array( 'type' => 'integer' ),
						'title'   => array( 'type' => 'string' ),
						'url'     => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_approve_location' ),
				'permission_callback' => function () {
					return current_user_can( 'publish_posts' ) || ( defined( 'WP_CLI' ) && WP_CLI );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'destructive' => true ),
				),
			)
		);
	}

	/**
	 * Execute: approve-location.
	 *
	 * Publishes a pending wing_location post.
	 *
	 * @param array $input { post_id: int }.
	 * @return array|\WP_Error
	 */
	public function execute_approve_location( array $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post || 'wing_location' !== $post->post_type ) {
			return new \WP_Error(
				'not_found',
				__( 'Wing location not found.', 'wing-review-submit' ),
				array( 'status' => 404 )
			);
		}

		if ( 'pending' !== $post->post_status ) {
			return new \WP_Error(
				'not_pending',
				__( 'Location is not in pending status.', 'wing-review-submit' ),
				array( 'status' => 400 )
			);
		}

		wp_publish_post( $post_id );

		return array(
			'success' => true,
			'post_id' => $post_id,
			'title'   => get_the_title( $post_id ),
			'url'     => get_permalink( $post_id ),
		);
	}

	// ------------------------------------------------------------------
	// cluckin-chuck/reject-location
	// ------------------------------------------------------------------

	private function register_reject_location(): void {
		wp_register_ability(
			'cluckin-chuck/reject-location',
			array(
				'label'               => __( 'Reject Location', 'wing-review-submit' ),
				'description'         => __( 'Reject a pending wing location submission by trashing it.', 'wing-review-submit' ),
				'category'            => 'cluckin-chuck',
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The pending wing_location post ID to reject.', 'wing-review-submit' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'post_id' => array( 'type' => 'integer' ),
						'title'   => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_reject_location' ),
				'permission_callback' => function () {
					return current_user_can( 'publish_posts' ) || ( defined( 'WP_CLI' ) && WP_CLI );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'destructive' => true ),
				),
			)
		);
	}

	/**
	 * Execute: reject-location.
	 *
	 * Trashes a pending wing_location post.
	 *
	 * @param array $input { post_id: int }.
	 * @return array|\WP_Error
	 */
	public function execute_reject_location( array $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post || 'wing_location' !== $post->post_type ) {
			return new \WP_Error(
				'not_found',
				__( 'Wing location not found.', 'wing-review-submit' ),
				array( 'status' => 404 )
			);
		}

		if ( 'pending' !== $post->post_status ) {
			return new \WP_Error(
				'not_pending',
				__( 'Location is not in pending status.', 'wing-review-submit' ),
				array( 'status' => 400 )
			);
		}

		$title  = get_the_title( $post_id );
		$result = wp_trash_post( $post_id );

		if ( ! $result ) {
			return new \WP_Error(
				'rejection_failed',
				__( 'Failed to trash location.', 'wing-review-submit' ),
				array( 'status' => 500 )
			);
		}

		return array(
			'success' => true,
			'post_id' => $post_id,
			'title'   => $title,
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
