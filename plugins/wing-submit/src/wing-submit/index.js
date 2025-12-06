/**
 * Wing Submit Block - Editor Registration
 *
 * Registers the wing-map/wing-submit block for use in the WordPress editor.
 * Server-side rendering handles the frontend button output.
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';

registerBlockType( 'wing-map/wing-submit', {
	edit: Edit,
	save: () => null,
} );
