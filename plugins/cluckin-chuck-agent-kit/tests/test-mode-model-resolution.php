<?php
/**
 * Integration tests for the cluckin-chuck mode's model cascade.
 *
 * Cluckin' Chuck mode bumps the public chat to gpt-5.4-mini without
 * touching the site-wide default (still gpt-5.4-nano for cheap stuff
 * like alt-text generation). This cascade is what makes "per-mode
 * model selection" the canonical pattern instead of overriding
 * agent_config.default_model directly.
 *
 * Tests use a real datamachine_settings option so we exercise the full
 * cascade through PluginSettings::resolveModelForAgentModes().
 *
 * @package CluckinChuck\AgentKit\Tests
 */

use CluckinChuck\AgentKit\Mode\CluckinChuckMode;

class Test_Mode_Model_Resolution extends WP_UnitTestCase {

	private array $saved_settings = array();

	protected function set_up(): void {
		parent::set_up();
		$this->saved_settings = get_option( 'datamachine_settings', array() );
	}

	protected function tear_down(): void {
		update_option( 'datamachine_settings', $this->saved_settings );
		if ( class_exists( '\DataMachine\Core\PluginSettings' ) ) {
			\DataMachine\Core\PluginSettings::clearCache();
		}
		parent::tear_down();
	}

	private function seed_settings( array $settings ): void {
		update_option( 'datamachine_settings', $settings );
		\DataMachine\Core\PluginSettings::clearCache();
	}

	public function test_resolution_picks_cluckin_chuck_model_over_global_default() {
		if ( ! class_exists( '\DataMachine\Core\PluginSettings' ) ) {
			$this->markTestSkipped( 'Data Machine PluginSettings not available.' );
		}

		$this->seed_settings( array(
			'default_provider' => 'openai',
			'default_model'    => 'gpt-5.4-nano',
			'mode_models'      => array(
				'cluckin-chuck' => array(
					'provider' => 'openai',
					'model'    => 'gpt-5.4-mini',
				),
			),
		) );

		$conf = \DataMachine\Core\PluginSettings::resolveModelForAgentModes(
			null,
			array( 'cluckin-chuck', 'chat' ),
			'chat'
		);

		$this->assertSame( 'openai',       $conf['provider'] );
		$this->assertSame( 'gpt-5.4-mini', $conf['model'] );
	}

	public function test_resolution_falls_through_to_default_when_mode_unset() {
		$this->seed_settings( array(
			'default_provider' => 'openai',
			'default_model'    => 'gpt-5.4-nano',
			// No mode_models[cluckin-chuck] override.
		) );

		$conf = \DataMachine\Core\PluginSettings::resolveModelForAgentModes(
			null,
			array( 'cluckin-chuck', 'chat' ),
			'chat'
		);

		$this->assertSame( 'gpt-5.4-nano', $conf['model'] );
	}

	public function test_chat_only_resolution_unaffected_by_cluckin_chuck_setting() {
		// Setting a cluckin-chuck-specific model must not leak into plain
		// chat resolution (e.g. admin Data Machine chat UI).
		$this->seed_settings( array(
			'default_provider' => 'openai',
			'default_model'    => 'gpt-5.4-nano',
			'mode_models'      => array(
				'cluckin-chuck' => array(
					'provider' => 'openai',
					'model'    => 'gpt-5.4-mini',
				),
			),
		) );

		$conf = \DataMachine\Core\PluginSettings::resolveModelForAgentModes(
			null,
			array( 'chat' ),
			'chat'
		);

		$this->assertSame( 'gpt-5.4-nano', $conf['model'], 'chat-only path must not see cluckin-chuck model.' );
	}

	public function test_mode_order_matters_first_complete_pair_wins() {
		// If both cluckin-chuck and chat have mode_models entries, the
		// first mode in the array order should win — that's how the agent
		// can opt into a stronger model via mode priority.
		$this->seed_settings( array(
			'default_provider' => 'openai',
			'default_model'    => 'gpt-5.4-nano',
			'mode_models'      => array(
				'cluckin-chuck' => array( 'provider' => 'openai', 'model' => 'gpt-5.4-mini' ),
				'chat'          => array( 'provider' => 'openai', 'model' => 'gpt-5.4-nano' ),
			),
		) );

		$conf = \DataMachine\Core\PluginSettings::resolveModelForAgentModes(
			null,
			array( 'cluckin-chuck', 'chat' ),
			'chat'
		);

		$this->assertSame( 'gpt-5.4-mini', $conf['model'] );
	}

	public function test_seed_default_model_writes_mode_models_when_unset() {
		$this->seed_settings( array(
			'default_provider' => 'openai',
			'default_model'    => 'gpt-5.4-nano',
		) );

		CluckinChuckMode::seed_default_model();

		$settings = get_option( 'datamachine_settings' );
		$this->assertSame( 'openai',       $settings['mode_models']['cluckin-chuck']['provider'] );
		$this->assertSame( 'gpt-5.4-mini', $settings['mode_models']['cluckin-chuck']['model'] );
	}

	public function test_seed_default_model_does_not_overwrite_user_choice() {
		$this->seed_settings( array(
			'default_provider' => 'openai',
			'default_model'    => 'gpt-5.4-nano',
			'mode_models'      => array(
				'cluckin-chuck' => array(
					'provider' => 'openai',
					'model'    => 'gpt-5.4',
				),
			),
		) );

		CluckinChuckMode::seed_default_model();

		$settings = get_option( 'datamachine_settings' );
		$this->assertSame(
			'gpt-5.4',
			$settings['mode_models']['cluckin-chuck']['model'],
			'Seeder must respect an existing user-configured model.'
		);
	}

	public function test_seed_default_model_does_not_touch_global_default() {
		// The seeder must not stomp on the site's global default_model —
		// that controls cheap stuff like system tasks (alt-text gen).
		$this->seed_settings( array(
			'default_provider' => 'openai',
			'default_model'    => 'gpt-5.4-nano',
		) );

		CluckinChuckMode::seed_default_model();

		$settings = get_option( 'datamachine_settings' );
		$this->assertSame( 'gpt-5.4-nano', $settings['default_model'] );
	}

	public function test_seed_default_model_handles_missing_settings_option() {
		delete_option( 'datamachine_settings' );

		CluckinChuckMode::seed_default_model();

		$settings = get_option( 'datamachine_settings' );
		$this->assertIsArray( $settings );
		$this->assertSame( 'gpt-5.4-mini', $settings['mode_models']['cluckin-chuck']['model'] );
	}
}
