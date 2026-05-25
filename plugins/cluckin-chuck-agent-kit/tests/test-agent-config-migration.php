<?php
/**
 * Integration tests for the agent_config migration that ran on the
 * 0.1.x → 0.3.0 upgrade.
 *
 * Pre-0.3.0 the cluckinchuck agent row carried the wing-business
 * system_prompt and a tool_policy allowlist in its agent_config JSON.
 * The cluckin-chuck mode now owns both, so the migration strips them
 * from the DB row on upgrade. Tests prove the migration is correct
 * and idempotent.
 *
 * @package CluckinChuck\AgentKit\Tests
 */

use CluckinChuck\AgentKit\Mode\CluckinChuckMode;

class Test_Agent_Config_Migration extends WP_UnitTestCase {

	protected function set_up(): void {
		parent::set_up();
		if ( ! class_exists( '\DataMachine\Core\Database\Agents\Agents' ) ) {
			$this->markTestSkipped( 'Data Machine Agents repo not available.' );
		}
	}

	/**
	 * Provision the cluckinchuck agent row in the legacy (pre-0.3.0) shape.
	 */
	private function seed_legacy_agent(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'datamachine_agents';

		$legacy_config = wp_json_encode( array(
			'system_prompt' => 'You are Cluckin\' Chuck (legacy embedded prompt).',
			'tool_policy'   => array(
				'mode'  => 'allow',
				'tools' => array( 'list_wing_locations', 'submit_wing_review' ),
			),
		) );

		$wpdb->insert(
			$table,
			array(
				'agent_slug'  => 'cluckinchuck',
				'agent_name'  => "Cluckin' Chuck",
				'owner_id'    => self::factory()->user->create( array( 'role' => 'administrator' ) ),
				'agent_config' => $legacy_config,
				'status'      => 'active',
			),
			array( '%s', '%s', '%d', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	private function delete_agent(): void {
		global $wpdb;
		$wpdb->delete(
			$wpdb->prefix . 'datamachine_agents',
			array( 'agent_slug' => 'cluckinchuck' ),
			array( '%s' )
		);
	}

	public function test_migration_strips_system_prompt() {
		$this->delete_agent();
		$this->seed_legacy_agent();

		CluckinChuckMode::migrate_agent_config();

		$row    = ( new \DataMachine\Core\Database\Agents\Agents() )->get_by_slug( 'cluckinchuck' );
		$config = is_array( $row['agent_config'] ?? null ) ? $row['agent_config'] : array();

		$this->assertArrayNotHasKey(
			'system_prompt',
			$config,
			'Migration must remove system_prompt from agent_config (mode owns it now).'
		);
	}

	public function test_migration_strips_tool_policy() {
		$this->delete_agent();
		$this->seed_legacy_agent();

		CluckinChuckMode::migrate_agent_config();

		$row    = ( new \DataMachine\Core\Database\Agents\Agents() )->get_by_slug( 'cluckinchuck' );
		$config = is_array( $row['agent_config'] ?? null ) ? $row['agent_config'] : array();

		$this->assertArrayNotHasKey(
			'tool_policy',
			$config,
			'Migration must remove tool_policy from agent_config (mode allowlist owns it now).'
		);
	}

	public function test_migration_preserves_other_agent_config_keys() {
		$this->delete_agent();

		// Seed with extra keys we should NOT touch.
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'datamachine_agents',
			array(
				'agent_slug'   => 'cluckinchuck',
				'agent_name'   => "Cluckin' Chuck",
				'owner_id'     => self::factory()->user->create( array( 'role' => 'administrator' ) ),
				'agent_config' => wp_json_encode( array(
					'system_prompt'    => 'legacy',
					'tool_policy'      => array( 'mode' => 'allow' ),
					'custom_setting'   => 'keep_me',
					'mode_models'      => array(
						'cluckin-chuck' => array( 'provider' => 'openai', 'model' => 'gpt-5.4' ),
					),
				) ),
				'status'       => 'active',
			),
			array( '%s', '%s', '%d', '%s', '%s' )
		);

		CluckinChuckMode::migrate_agent_config();

		$row    = ( new \DataMachine\Core\Database\Agents\Agents() )->get_by_slug( 'cluckinchuck' );
		$config = is_array( $row['agent_config'] ?? null ) ? $row['agent_config'] : array();

		$this->assertSame( 'keep_me', $config['custom_setting'] ?? null );
		$this->assertSame( 'gpt-5.4', $config['mode_models']['cluckin-chuck']['model'] ?? null );
	}

	public function test_migration_is_idempotent() {
		$this->delete_agent();
		$this->seed_legacy_agent();

		// First call does the work.
		CluckinChuckMode::migrate_agent_config();
		$first = ( new \DataMachine\Core\Database\Agents\Agents() )->get_by_slug( 'cluckinchuck' );

		// Second call should be a no-op.
		CluckinChuckMode::migrate_agent_config();
		$second = ( new \DataMachine\Core\Database\Agents\Agents() )->get_by_slug( 'cluckinchuck' );

		$this->assertSame( $first['agent_config'], $second['agent_config'] );
	}

	public function test_migration_is_safe_when_agent_does_not_exist() {
		$this->delete_agent();

		// Should not throw, should not error.
		CluckinChuckMode::migrate_agent_config();

		$row = ( new \DataMachine\Core\Database\Agents\Agents() )->get_by_slug( 'cluckinchuck' );
		$this->assertNull( $row );
	}
}
