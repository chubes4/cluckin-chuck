/**
 * Wing Map Display Block - Editor Registration
 *
 * Registers the wing-map/map-display block for use in the WordPress editor.
 * Server-side rendering handles the frontend output.
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';

registerBlockType('wing-map/map-display', {
	edit: Edit,
	save: () => null,
});
