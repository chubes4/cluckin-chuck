/**
 * Wing Review Block - Editor Registration
 *
 * Registers the wing-map/wing-review block for use in the WordPress editor.
 * This block is auto-generated from approved user reviews.
 * Server-side rendering handles the frontend output.
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';

registerBlockType('wing-map/wing-review', {
	edit: Edit,
	save: () => null,
});
