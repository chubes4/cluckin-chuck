/**
 * Wing Map Display Block - Editor Registration
 *
 * Registers the wing-map-display/wing-map-display block for use in the WordPress editor.
 * Server-side rendering handles the frontend output.
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import './style.scss';
import './editor.scss';

registerBlockType('wing-map-display/wing-map-display', {
	edit: Edit,
	save: () => null,
});
