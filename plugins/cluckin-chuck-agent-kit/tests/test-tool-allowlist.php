<?php
/**
 * Integration tests for tool surface allowlisting under cluckin-chuck mode.
 *
 * The cluckin-chuck mode bundles the chat execution surface with a strict
 * 13-tool wing allowlist. Without the allowlist, including 'chat' in the
 * agent_modes array would expose 100+ admin chat tools (GitHub, social,
 * pipeline mgmt) to the public frontend chat.
 *
 * These tests lock down both the canonical allowlist and the effect of
 * the datamachine_resolved_tools filter that enforces it.
 *
 * @package CluckinChuck\AgentKit\Tests
 */

class Test_Tool_Allowlist extends WP_UnitTestCase {

	/**
	 * The canonical 13 wing tools — the public chat surface.
	 *
	 * Any change to this list should be deliberate and reviewed.
	 *
	 * @return array<int,string>
	 */
	private function expected_wing_tools(): array {
		return array(
			// Discovery (3).
			'list_wing_locations',
			'get_wing_location',
			'list_wing_reviews',

			// Geocoding (1).
			'geocode_address',
			'find_wing_restaurant',

			// Public submissions (2).
			'submit_wing_review',
			'submit_wing_location',

			// Admin moderation (7).
			'approve_wing_review',
			'reject_wing_review',
			'approve_wing_location',
			'reject_wing_location',
			'update_wing_location',
			'recalculate_wing_stats',
			'list_pending_submissions',
		);
	}

	public function test_resolved_tools_under_mode_match_allowlist_exactly() {
		if ( ! class_exists( '\DataMachine\Engine\AI\Tools\ToolPolicyResolver' ) ) {
			$this->markTestSkipped( 'Data Machine ToolPolicyResolver not available.' );
		}

		$resolver = new \DataMachine\Engine\AI\Tools\ToolPolicyResolver();
		$tools    = $resolver->resolve( array(
			'modes'    => array( 'cluckin-chuck', 'chat' ),
			'agent_id' => 0,
		) );

		$tool_names = array_keys( $tools );
		sort( $tool_names );

		$expected = $this->expected_wing_tools();
		sort( $expected );

		$this->assertSame(
			$expected,
			$tool_names,
			'Resolved tool surface under cluckin-chuck mode must match the wing allowlist exactly.'
		);
	}

	public function test_tool_count_is_thirteen() {
		if ( ! class_exists( '\DataMachine\Engine\AI\Tools\ToolPolicyResolver' ) ) {
			$this->markTestSkipped( 'Data Machine ToolPolicyResolver not available.' );
		}

		$resolver = new \DataMachine\Engine\AI\Tools\ToolPolicyResolver();
		$tools    = $resolver->resolve( array(
			'modes'    => array( 'cluckin-chuck', 'chat' ),
			'agent_id' => 0,
		) );

		$this->assertCount( 13, $tools );
	}

	public function test_chat_mode_alone_yields_broader_surface() {
		// Sanity check: without cluckin-chuck mode, the 'chat' surface lets
		// every admin chat tool through. This documents the protection the
		// cluckin-chuck mode actively provides.
		if ( ! class_exists( '\DataMachine\Engine\AI\Tools\ToolPolicyResolver' ) ) {
			$this->markTestSkipped( 'Data Machine ToolPolicyResolver not available.' );
		}

		$resolver  = new \DataMachine\Engine\AI\Tools\ToolPolicyResolver();
		$chat_only = $resolver->resolve( array(
			'modes'    => array( 'chat' ),
			'agent_id' => 0,
		) );

		$this->assertGreaterThan(
			13,
			count( $chat_only ),
			'chat-only mode must expose more than the 13 wing tools — otherwise the allowlist is doing nothing.'
		);
	}

	public function test_admin_tools_are_filtered_out_under_cluckin_chuck() {
		if ( ! class_exists( '\DataMachine\Engine\AI\Tools\ToolPolicyResolver' ) ) {
			$this->markTestSkipped( 'Data Machine ToolPolicyResolver not available.' );
		}

		$resolver = new \DataMachine\Engine\AI\Tools\ToolPolicyResolver();
		$tools    = $resolver->resolve( array(
			'modes'    => array( 'cluckin-chuck', 'chat' ),
			'agent_id' => 0,
		) );

		// A spot-check of admin chat tools that must NOT reach a public chat.
		$forbidden = array(
			'create_pipeline',
			'create_flow',
			'run_flow',
			'create_github_issue',
			'publish_twitter',
			'manage_jobs',
			'delete_pipeline',
		);

		foreach ( $forbidden as $tool ) {
			$this->assertArrayNotHasKey(
				$tool,
				$tools,
				"Admin tool '{$tool}' must NOT be exposed to the public cluckin-chuck mode."
			);
		}
	}

	public function test_filter_is_noop_when_mode_inactive() {
		// The restrict_tools_to_wing_surface filter must not strip tools
		// when cluckin-chuck mode is NOT in the active modes — otherwise
		// it would silently break other DM surfaces.
		$starter = array(
			'arbitrary_tool_a' => array( 'description' => 'a' ),
			'arbitrary_tool_b' => array( 'description' => 'b' ),
		);

		$result = apply_filters(
			'datamachine_resolved_tools',
			$starter,
			array( 'chat' ), // No cluckin-chuck.
			array()
		);

		$this->assertSame(
			$starter,
			$result,
			'Filter must not alter tools when cluckin-chuck mode is absent.'
		);
	}

	public function test_filter_strips_unknown_tools_when_mode_active() {
		$mixed = array(
			'list_wing_locations' => array( 'description' => 'wing tool' ),
			'create_github_issue' => array( 'description' => 'admin tool' ),
		);

		$result = apply_filters(
			'datamachine_resolved_tools',
			$mixed,
			array( 'cluckin-chuck', 'chat' ),
			array()
		);

		$this->assertArrayHasKey( 'list_wing_locations', $result );
		$this->assertArrayNotHasKey( 'create_github_issue', $result );
	}
}
