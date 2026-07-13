<?php
/**
 * Integration tests proving every wing tool's parameter schema reaches the
 * LLM in proper JSON Schema shape.
 *
 * Pre-0.3.0 the tools declared `parameters` as a bare keyed map, which DM
 * passed through to OpenAI unchanged. Strict models (gpt-5.4-mini+) reject
 * that with a 400 "invalid schema for function" — the bug that took down
 * the live chat on first tool call.
 *
 * These tests resolve each tool through DM's normal resolution pipeline
 * and assert the resulting schema is valid for OpenAI function-calling.
 *
 * @package CluckinChuck\AgentKit\Tests
 */

class Test_Tool_Schemas extends WP_UnitTestCase {

	/** @var array<string,array<string,mixed>>|null */
	private ?array $resolved_tools = null;

	private function tools(): array {
		if ( null !== $this->resolved_tools ) {
			return $this->resolved_tools;
		}

		if ( ! class_exists( '\DataMachine\Engine\AI\Tools\ToolPolicyResolver' ) ) {
			$this->markTestSkipped( 'Data Machine ToolPolicyResolver not available.' );
		}

		$resolver = new \DataMachine\Engine\AI\Tools\ToolPolicyResolver();
		$this->resolved_tools = $resolver->resolve( array(
			'modes'    => array( 'cluckin-chuck', 'chat' ),
			'agent_id' => 0,
		) );

		return $this->resolved_tools;
	}

	/**
	 * Generic JSON Schema shape assertion for one tool.
	 */
	private function assertValidJsonSchemaShape( array $tool, string $tool_name ): void {
		$this->assertArrayHasKey( 'parameters', $tool, "{$tool_name} missing parameters." );

		$params = $tool['parameters'];

		// Top-level: must be { type: 'object', properties: {...} }.
		$this->assertIsArray( $params, "{$tool_name} parameters must be an array." );
		$this->assertSame( 'object', $params['type'] ?? null, "{$tool_name} parameters.type must be 'object'." );
		$this->assertArrayHasKey( 'properties', $params, "{$tool_name} parameters.properties missing." );
		$this->assertIsArray( $params['properties'], "{$tool_name} parameters.properties must be an array." );

		// No leaked legacy keys at the top level (i.e. properties used to be
		// at the root before the SchemaHelper conversion).
		$this->assertArrayNotHasKey(
			'required',
			$params['properties'],
			"{$tool_name} must not have a property literally named 'required'."
		);

		// If a top-level required[] exists, it must be a list of strings.
		if ( isset( $params['required'] ) ) {
			$this->assertIsArray( $params['required'], "{$tool_name} required must be an array." );
			foreach ( $params['required'] as $req ) {
				$this->assertIsString( $req, "{$tool_name} required[] entries must be strings." );
				$this->assertArrayHasKey(
					$req,
					$params['properties'],
					"{$tool_name} required[] entry '{$req}' must exist in properties."
				);
			}
		}

		// Per-property: no inline 'required' (must be lifted to top level).
		foreach ( $params['properties'] as $prop_name => $prop_def ) {
			$this->assertIsArray( $prop_def, "{$tool_name}.{$prop_name} def must be an array." );
			$this->assertArrayNotHasKey(
				'required',
				$prop_def,
				"{$tool_name}.{$prop_name} must not carry an inline 'required' key (lift to top-level required[])."
			);
			$this->assertArrayHasKey(
				'type',
				$prop_def,
				"{$tool_name}.{$prop_name} must declare a JSON Schema type."
			);
		}
	}

	public function test_list_wing_locations_schema_is_valid() {
		$tools = $this->tools();
		$this->assertArrayHasKey( 'list_wing_locations', $tools );
		$this->assertValidJsonSchemaShape( $tools['list_wing_locations'], 'list_wing_locations' );

		$params = $tools['list_wing_locations']['parameters'];
		$this->assertArrayHasKey( 'search',   $params['properties'] );
		$this->assertArrayHasKey( 'status',   $params['properties'] );
		$this->assertArrayHasKey( 'per_page', $params['properties'] );
		// status carries an enum — that's specifically where the original bug bit.
		$this->assertArrayHasKey( 'enum', $params['properties']['status'] );
		$this->assertSame(
			array( 'publish', 'pending', 'draft' ),
			$params['properties']['status']['enum']
		);
	}

	public function test_get_wing_location_requires_post_id() {
		$tools = $this->tools();
		$this->assertValidJsonSchemaShape( $tools['get_wing_location'], 'get_wing_location' );
		$this->assertSame( array( 'post_id' ), $tools['get_wing_location']['parameters']['required'] );
	}

	public function test_submit_wing_review_required_array_includes_post_id_and_rating() {
		$tools = $this->tools();
		$this->assertValidJsonSchemaShape( $tools['submit_wing_review'], 'submit_wing_review' );

		$required = $tools['submit_wing_review']['parameters']['required'] ?? array();
		$this->assertContains( 'post_id', $required );
		$this->assertContains( 'rating',  $required );

		// Reviewer identity is not exposed to the chat agent at all — the ability
		// auto-fills reviewer_name and reviewer_email server-side from the logged-in
		// user. Anonymous submissions go through the form block, not chat. See #7.
		$properties = $tools['submit_wing_review']['parameters']['properties'] ?? array();
		$this->assertArrayNotHasKey( 'reviewer_name',  $properties );
		$this->assertArrayNotHasKey( 'reviewer_email', $properties );
	}

	public function test_submit_wing_location_does_not_expose_reviewer_identity() {
		$tools = $this->tools();
		$this->assertValidJsonSchemaShape( $tools['submit_wing_location'], 'submit_wing_location' );

		// Same rule as submit_wing_review — reviewer identity is server-side only.
		$properties = $tools['submit_wing_location']['parameters']['properties'] ?? array();
		$this->assertArrayNotHasKey( 'reviewer_name',  $properties );
		$this->assertArrayNotHasKey( 'reviewer_email', $properties );
	}

	public function test_list_pending_submissions_enum_schema_is_valid() {
		// This was the exact tool that triggered the production 400.
		$tools = $this->tools();
		$this->assertArrayHasKey( 'list_pending_submissions', $tools );
		$this->assertValidJsonSchemaShape( $tools['list_pending_submissions'], 'list_pending_submissions' );

		$params = $tools['list_pending_submissions']['parameters'];
		$this->assertArrayHasKey( 'type', $params['properties'] );
		$this->assertSame(
			array( 'all', 'locations', 'reviews' ),
			$params['properties']['type']['enum']
		);
	}

	public function test_geocode_address_requires_address() {
		$tools = $this->tools();
		$this->assertValidJsonSchemaShape( $tools['geocode_address'], 'geocode_address' );
		$this->assertSame( array( 'address' ), $tools['geocode_address']['parameters']['required'] );
		$this->assertStringContainsString(
			'restaurant name',
			$tools['geocode_address']['parameters']['properties']['address']['description']
		);
	}

	public function test_every_wing_tool_has_valid_json_schema() {
		$tools = $this->tools();

		$wing_tools = array(
			'list_wing_locations', 'get_wing_location', 'update_wing_location',
			'geocode_address', 'approve_wing_location', 'reject_wing_location',
			'list_wing_reviews', 'approve_wing_review', 'reject_wing_review',
			'recalculate_wing_stats', 'list_pending_submissions',
			'submit_wing_review', 'submit_wing_location',
		);

		foreach ( $wing_tools as $name ) {
			$this->assertArrayHasKey( $name, $tools, "Wing tool {$name} did not resolve." );
			$this->assertValidJsonSchemaShape( $tools[ $name ], $name );
		}
	}

	public function test_tools_declare_modes_not_contexts() {
		// Catches the original latent bug: tools declared with 'contexts'
		// instead of 'modes' silently fail to register with the resolver.
		$raw = apply_filters( 'datamachine_tools', array() );

		$wing_tools = array(
			'list_wing_locations', 'submit_wing_review', 'list_pending_submissions',
		);

		foreach ( $wing_tools as $name ) {
			$this->assertArrayHasKey( $name, $raw, "Wing tool {$name} not registered." );
			$this->assertArrayHasKey(
				'modes',
				$raw[ $name ],
				"Wing tool {$name} must declare 'modes' (NOT 'contexts')."
			);
			$this->assertArrayNotHasKey(
				'contexts',
				$raw[ $name ],
				"Wing tool {$name} must not use the legacy 'contexts' key — DM ignores it silently."
			);
			$this->assertContains( 'cluckin-chuck', $raw[ $name ]['modes'] );
			$this->assertContains( 'chat', $raw[ $name ]['modes'] );
		}
	}
}
