/**
 * Wing Review Submit Block - Editor Registration
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';

registerBlockType( 'wing-review-submit/wing-review-submit', {
	edit: Edit,
	save: () => null,
} );
