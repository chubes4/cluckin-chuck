/**
 * Wing Location Details Block - Editor Registration
 *
 * Registers the wing-location-details/wing-location-details block.
 * Server-side rendering handles the frontend output.
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';

registerBlockType('wing-location-details/wing-location-details', {
	edit: Edit,
	save: () => null,
});
