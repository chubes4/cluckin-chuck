<?php
/**
 * Integration tests for the cluckin-chuck mode directive.
 *
 * The directive is what defines Chuck's behavior at runtime — the system
 * prompt overlay that fires via the datamachine_agent_mode_cluckin-chuck
 * filter. These tests lock down the key behavioral contracts so future
 * directive edits don't accidentally drop required wing-biz guidance.
 *
 * @package CluckinChuck\AgentKit\Tests
 */

class Test_Mode_Directive extends WP_UnitTestCase {

	/**
	 * Get the directive content as the AgentModeDirective would.
	 *
	 * @return string
	 */
	private function directive(): string {
		return (string) apply_filters( 'datamachine_agent_mode_cluckin-chuck', '', array() );
	}

	public function test_directive_filter_returns_non_empty_content() {
		$content = $this->directive();
		$this->assertNotEmpty( $content );
		$this->assertGreaterThan( 500, strlen( $content ), 'Directive must be substantial — wing biz logic is not trivial.' );
	}

	public function test_directive_identifies_as_public_wing_assistant() {
		$content = $this->directive();
		$this->assertStringContainsString( "Cluckin' Chuck Mode", $content );
		$this->assertStringContainsString( 'public-facing', $content );
	}

	public function test_directive_lists_all_wing_tool_categories() {
		$content = $this->directive();

		// Discovery.
		$this->assertStringContainsString( 'list_wing_locations', $content );
		$this->assertStringContainsString( 'get_wing_location',   $content );
		$this->assertStringContainsString( 'list_wing_reviews',   $content );

		// Geocoding.
		$this->assertStringContainsString( 'geocode_address', $content );

		// Submissions.
		$this->assertStringContainsString( 'submit_wing_review',   $content );
		$this->assertStringContainsString( 'submit_wing_location', $content );

		// Moderation.
		$this->assertStringContainsString( 'approve_wing_review',  $content );
		$this->assertStringContainsString( 'reject_wing_review',   $content );
		$this->assertStringContainsString( 'list_pending_submissions', $content );
	}

	public function test_directive_includes_rating_semantics() {
		$content = $this->directive();
		$this->assertStringContainsString( 'amazing',  $content );
		$this->assertStringContainsString( 'terrible', $content );
		// All five rating buckets must be present.
		$this->assertStringContainsString( '→ 5', $content );
		$this->assertStringContainsString( '→ 1', $content );
	}

	public function test_directive_tells_agent_not_to_ask_for_identity() {
		$content = $this->directive();
		// The critical UX rule: never ask the user for name/email. Reviewer identity
		// is auto-filled server-side; the chat tools don't even expose those fields.
		$this->assertStringContainsString( 'auto-filled', $content );
		$this->assertStringContainsString( 'Never ask the user for their name or email', $content );
	}

	public function test_directive_includes_geocode_before_submit_workflow() {
		$content = $this->directive();
		// Locations must be geocoded BEFORE submission — wrong order breaks data.
		$this->assertStringContainsString( 'geocode_address',     $content );
		$this->assertStringContainsString( 'submit_wing_location', $content );
		$this->assertMatchesRegularExpression(
			'/geocode_address.*submit_wing_location/s',
			$content,
			'Directive must instruct geocode_address before submit_wing_location.'
		);
	}

	public function test_directive_looks_up_restaurant_before_asking_for_address() {
		$content = $this->directive();
		$this->assertStringContainsString( 'find_wing_restaurant', $content );
		$this->assertStringContainsString( 'restaurant name plus any city/state context', $content );
		$this->assertStringContainsString( 'Mandatory next tool call', $content );
		$this->assertStringContainsString( 'Never claim geocoding is impossible without a street address', $content );
		$this->assertStringContainsString( 'Do not ask the user for a street address until', $content );
		$this->assertStringContainsString( 'formatted_address', $content );
	}

	public function test_directive_confirms_data_before_submitting() {
		$content = $this->directive();
		$this->assertStringContainsString( 'confirm', strtolower( $content ) );
		$this->assertStringContainsString( 'submit_wing_review', $content );
	}

	public function test_filter_payload_param_is_array() {
		$called_with = null;
		add_filter(
			'datamachine_agent_mode_cluckin-chuck',
			function ( $content, $payload ) use ( &$called_with ) {
				$called_with = $payload;
				return $content;
			},
			20,
			2
		);

		apply_filters( 'datamachine_agent_mode_cluckin-chuck', '', array( 'test_key' => 'test_value' ) );

		$this->assertIsArray( $called_with );
		$this->assertSame( 'test_value', $called_with['test_key'] );
	}
}
