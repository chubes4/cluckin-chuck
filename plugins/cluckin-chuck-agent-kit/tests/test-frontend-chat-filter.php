<?php
/**
 * Integration tests for the frontend-chat client_context filter.
 *
 * The cluckin-chuck mode is only useful if it actually gets opted-in
 * by the frontend chat widget. That happens in the
 * frontend_agent_chat_chat_input filter, which injects:
 *   - client_context.agent_modes = ['cluckin-chuck', 'chat']
 *   - client_context.user_authenticated (true/false)
 *   - identity fields when logged in
 *
 * These tests lock down that wiring so future filter edits don't
 * silently drop the mode opt-in.
 *
 * @package CluckinChuck\AgentKit\Tests
 */

class Test_Frontend_Chat_Filter extends WP_UnitTestCase {

	public function test_filter_injects_cluckin_chuck_mode() {
		$chat_input = array( 'message' => 'test' );
		$result     = apply_filters( 'frontend_agent_chat_chat_input', $chat_input );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'client_context', $result );
		$this->assertArrayHasKey( 'agent_modes', $result['client_context'] );
		$this->assertContains( 'cluckin-chuck', $result['client_context']['agent_modes'] );
	}

	public function test_filter_injects_chat_execution_surface() {
		// 'chat' must be present so ToolPolicyResolver treats this as an
		// interactive request — without it the resolver falls back to
		// 'pipeline' mode and gates the call behind a permission check.
		$chat_input = array( 'message' => 'test' );
		$result     = apply_filters( 'frontend_agent_chat_chat_input', $chat_input );

		$this->assertContains( 'chat', $result['client_context']['agent_modes'] );
	}

	public function test_filter_marks_anonymous_users_unauthenticated() {
		wp_set_current_user( 0 );

		$chat_input = array( 'message' => 'test' );
		$result     = apply_filters( 'frontend_agent_chat_chat_input', $chat_input );

		$this->assertFalse( $result['client_context']['user_authenticated'] );
		$this->assertArrayNotHasKey( 'user_display_name', $result['client_context'] );
		$this->assertArrayNotHasKey( 'user_role', $result['client_context'] );
	}

	public function test_filter_attaches_identity_for_logged_in_users() {
		$user_id = self::factory()->user->create( array(
			'role'         => 'administrator',
			'display_name' => 'Wing Tester',
		) );
		wp_set_current_user( $user_id );

		$chat_input = array( 'message' => 'test' );
		$result     = apply_filters( 'frontend_agent_chat_chat_input', $chat_input );

		$this->assertTrue( $result['client_context']['user_authenticated'] );
		$this->assertSame( 'Wing Tester', $result['client_context']['user_display_name'] );
		$this->assertStringContainsString( 'administrator', $result['client_context']['user_role'] );
	}

	public function test_filter_preserves_existing_client_context() {
		// Other filters or callers may pre-populate client_context; we must
		// merge into it rather than overwrite.
		$chat_input = array(
			'message'        => 'test',
			'client_context' => array(
				'custom_key' => 'custom_value',
				'page_id'    => 42,
			),
		);

		$result = apply_filters( 'frontend_agent_chat_chat_input', $chat_input );

		$this->assertSame( 'custom_value', $result['client_context']['custom_key'] );
		$this->assertSame( 42,             $result['client_context']['page_id'] );
		$this->assertContains( 'cluckin-chuck', $result['client_context']['agent_modes'] );
	}

	public function test_filter_handles_non_array_input_gracefully() {
		$result = apply_filters( 'frontend_agent_chat_chat_input', 'not-an-array' );

		// Should not fatal; whatever non-array value came in should pass
		// through unmodified (or at minimum not throw).
		$this->assertIsString( $result );
	}

	public function test_filter_sets_agent_modes_in_correct_order() {
		// Order matters: cluckin-chuck before chat. PluginSettings::
		// resolveModelForAgentModes() walks modes in order looking for the
		// first complete provider+model pair, so cluckin-chuck must come
		// first to take precedence over plain 'chat'.
		$chat_input = array( 'message' => 'test' );
		$result     = apply_filters( 'frontend_agent_chat_chat_input', $chat_input );

		$modes = $result['client_context']['agent_modes'];
		$cc_pos   = array_search( 'cluckin-chuck', $modes, true );
		$chat_pos = array_search( 'chat',          $modes, true );

		$this->assertNotFalse( $cc_pos );
		$this->assertNotFalse( $chat_pos );
		$this->assertLessThan( $chat_pos, $cc_pos, 'cluckin-chuck must precede chat in agent_modes.' );
	}

	public function test_frontend_chat_config_points_at_cluckinchuck_agent() {
		$config = apply_filters( 'frontend_agent_chat_config', array() );

		$this->assertSame( 'cluckinchuck', $config['agent_slug'] );
		$this->assertTrue( $config['enabled'] );
	}
}
