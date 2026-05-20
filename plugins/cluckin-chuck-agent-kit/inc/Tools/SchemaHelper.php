<?php
/**
 * Schema Helper — convert bare parameter maps to JSON Schema.
 *
 * Data Machine's RequestBuilder::normalizeToolSchema() expects the
 * `parameters` field on a tool definition to already be a JSON Schema
 * `{ type: 'object', properties: {...}, required: [...] }` shape. Bare
 * parameter maps (Cluckin' Chuck's historical pattern) pass through
 * unchanged and end up as malformed function declarations when sent to
 * the OpenAI API — strict models like gpt-5.4-mini reject them with a
 * 400 "invalid schema" error.
 *
 * This helper converts the keyed map into proper JSON Schema while
 * preserving the readable per-method def style.
 *
 * @package CluckinChuck\AgentKit\Tools
 * @since 0.2.2
 */

namespace CluckinChuck\AgentKit\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SchemaHelper {

	/**
	 * Convert a keyed-map of parameters to a JSON Schema object.
	 *
	 * Input form (Cluckin' Chuck convention):
	 *   array(
	 *     'post_id' => array( 'type' => 'integer', 'required' => true, 'description' => '...' ),
	 *     'meta'    => array( 'type' => 'object',  'required' => true, 'description' => '...' ),
	 *   )
	 *
	 * Output form (JSON Schema, what wp-ai-client / OpenAI expect):
	 *   array(
	 *     'type'       => 'object',
	 *     'properties' => array(
	 *       'post_id' => array( 'type' => 'integer', 'description' => '...' ),
	 *       'meta'    => array( 'type' => 'object',  'description' => '...' ),
	 *     ),
	 *     'required'   => array( 'post_id', 'meta' ),
	 *   )
	 *
	 * The per-parameter `required` flag is stripped from the property
	 * definition and aggregated into the top-level `required` array.
	 * All other keys (type, description, enum, items, etc.) pass through.
	 *
	 * @param array<string, array<string, mixed>> $parameters Keyed map.
	 * @return array{type:string,properties:array<string,array<string,mixed>>,required?:array<int,string>}
	 */
	public static function to_json_schema( array $parameters ): array {
		$properties = array();
		$required   = array();

		foreach ( $parameters as $name => $def ) {
			if ( ! is_string( $name ) || '' === $name || ! is_array( $def ) ) {
				continue;
			}

			$is_required = ! empty( $def['required'] );
			unset( $def['required'] );

			$properties[ $name ] = $def;
			if ( $is_required ) {
				$required[] = $name;
			}
		}

		$schema = array(
			'type'       => 'object',
			'properties' => $properties,
		);

		if ( ! empty( $required ) ) {
			$schema['required'] = $required;
		}

		return $schema;
	}
}
