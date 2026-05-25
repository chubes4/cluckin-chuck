<?php
/**
 * Unit tests for SchemaHelper::to_json_schema().
 *
 * Pure-PHP tests against the bare-map → JSON Schema conversion that fixes
 * the OpenAI strict function-calling rejection on bare keyed maps.
 *
 * @package CluckinChuck\AgentKit\Tests
 */

use CluckinChuck\AgentKit\Tools\SchemaHelper;

class Test_Schema_Helper extends WP_UnitTestCase {

	public function test_empty_input_returns_object_with_no_required() {
		$schema = SchemaHelper::to_json_schema( array() );

		$this->assertSame( 'object', $schema['type'] );
		$this->assertSame( array(), $schema['properties'] );
		$this->assertArrayNotHasKey( 'required', $schema );
	}

	public function test_single_required_param_lifts_to_required_array() {
		$schema = SchemaHelper::to_json_schema( array(
			'post_id' => array(
				'type'        => 'integer',
				'required'    => true,
				'description' => 'A post id.',
			),
		) );

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'post_id', $schema['properties'] );
		$this->assertSame( 'integer', $schema['properties']['post_id']['type'] );
		$this->assertSame( 'A post id.', $schema['properties']['post_id']['description'] );
		$this->assertArrayNotHasKey( 'required', $schema['properties']['post_id'] );
		$this->assertSame( array( 'post_id' ), $schema['required'] );
	}

	public function test_optional_param_stays_out_of_required_array() {
		$schema = SchemaHelper::to_json_schema( array(
			'search' => array(
				'type'        => 'string',
				'required'    => false,
				'description' => 'Optional search.',
			),
		) );

		$this->assertArrayHasKey( 'search', $schema['properties'] );
		$this->assertArrayNotHasKey( 'required', $schema['properties']['search'] );
		$this->assertArrayNotHasKey( 'required', $schema, 'required[] should be omitted when no required props.' );
	}

	public function test_mixed_required_and_optional_params_split_correctly() {
		$schema = SchemaHelper::to_json_schema( array(
			'post_id'       => array( 'type' => 'integer', 'required' => true ),
			'reviewer_name' => array( 'type' => 'string',  'required' => false ),
			'rating'        => array( 'type' => 'integer', 'required' => true ),
		) );

		$this->assertCount( 3, $schema['properties'] );
		$this->assertEqualsCanonicalizing(
			array( 'post_id', 'rating' ),
			$schema['required'],
			'Both required params should land in required[].'
		);
	}

	public function test_enum_passes_through_untouched() {
		$schema = SchemaHelper::to_json_schema( array(
			'type' => array(
				'type'        => 'string',
				'required'    => false,
				'description' => 'Filter type.',
				'enum'        => array( 'all', 'locations', 'reviews' ),
			),
		) );

		$this->assertSame(
			array( 'all', 'locations', 'reviews' ),
			$schema['properties']['type']['enum']
		);
	}

	public function test_non_array_def_is_skipped() {
		$schema = SchemaHelper::to_json_schema( array(
			'valid'   => array( 'type' => 'string' ),
			'invalid' => 'not-an-array',
		) );

		$this->assertArrayHasKey( 'valid', $schema['properties'] );
		$this->assertArrayNotHasKey( 'invalid', $schema['properties'] );
	}

	public function test_non_string_key_is_skipped() {
		$schema = SchemaHelper::to_json_schema( array(
			0       => array( 'type' => 'string' ),
			'valid' => array( 'type' => 'string' ),
		) );

		$this->assertArrayHasKey( 'valid', $schema['properties'] );
		$this->assertCount( 1, $schema['properties'] );
	}

	public function test_empty_string_key_is_skipped() {
		$schema = SchemaHelper::to_json_schema( array(
			''      => array( 'type' => 'string' ),
			'valid' => array( 'type' => 'string' ),
		) );

		$this->assertArrayHasKey( 'valid', $schema['properties'] );
		$this->assertCount( 1, $schema['properties'] );
	}

	public function test_required_truthy_values_all_lift_to_required_array() {
		$schema = SchemaHelper::to_json_schema( array(
			'a' => array( 'type' => 'string', 'required' => true ),
			'b' => array( 'type' => 'string', 'required' => 1 ),
			'c' => array( 'type' => 'string', 'required' => 'yes' ),
		) );

		$this->assertEqualsCanonicalizing(
			array( 'a', 'b', 'c' ),
			$schema['required']
		);
	}
}
