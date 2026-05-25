<?php
/**
 * Integration tests for cluckin-chuck mode registration with Data Machine.
 *
 * Verifies that CluckinChuckMode::register_mode() correctly registers the
 * mode with DataMachine\Engine\AI\AgentModeRegistry and that the mode is
 * visible to PluginSettings::getAgentModes() — the same list rendered in
 * the DM admin settings UI.
 *
 * @package CluckinChuck\AgentKit\Tests
 */

use CluckinChuck\AgentKit\Mode\CluckinChuckMode;

class Test_Mode_Registration extends WP_UnitTestCase {

	public function test_data_machine_registry_class_is_available() {
		$this->assertTrue(
			class_exists( '\DataMachine\Engine\AI\AgentModeRegistry' ),
			'Data Machine must be loaded for mode registration tests. Check validation_dependencies.'
		);
	}

	public function test_mode_slug_constant() {
		$this->assertSame( 'cluckin-chuck', CluckinChuckMode::SLUG );
	}

	public function test_mode_appears_in_registered_modes_list() {
		$modes    = \DataMachine\Core\PluginSettings::getAgentModes();
		$mode_ids = array_column( $modes, 'id' );

		$this->assertContains(
			'cluckin-chuck',
			$mode_ids,
			'cluckin-chuck mode must be in the registered modes list.'
		);
	}

	public function test_mode_has_label_and_description() {
		$modes = \DataMachine\Core\PluginSettings::getAgentModes();

		$cluckin_chuck = null;
		foreach ( $modes as $mode ) {
			if ( 'cluckin-chuck' === $mode['id'] ) {
				$cluckin_chuck = $mode;
				break;
			}
		}

		$this->assertNotNull( $cluckin_chuck, 'cluckin-chuck mode entry not found.' );
		$this->assertNotEmpty( $cluckin_chuck['label'] ?? '' );
		$this->assertStringContainsString( "Cluckin", $cluckin_chuck['label'] );
		$this->assertNotEmpty( $cluckin_chuck['description'] ?? '' );
	}

	public function test_core_modes_still_registered_alongside() {
		$modes    = \DataMachine\Core\PluginSettings::getAgentModes();
		$mode_ids = array_column( $modes, 'id' );

		// Adding our mode must not displace or interfere with DM's core modes.
		$this->assertContains( 'chat',     $mode_ids );
		$this->assertContains( 'pipeline', $mode_ids );
		$this->assertContains( 'system',   $mode_ids );
	}

	public function test_default_model_constants() {
		// These constants drive the activation/upgrade seeder. Locking them
		// down prevents accidental model downgrades via silent edits.
		$this->assertSame( 'openai',       CluckinChuckMode::DEFAULT_PROVIDER );
		$this->assertSame( 'gpt-5.4-mini', CluckinChuckMode::DEFAULT_MODEL );
	}
}
