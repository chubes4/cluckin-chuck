/**
 * Wing Map Display Block - Editor Component
 *
 * Displays a placeholder in the editor. The actual map is rendered server-side.
 */

import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export default function Edit() {
	const blockProps = useBlockProps({
		className: 'wing-map-editor-placeholder',
	});

	return (
		<div {...blockProps}>
			<div className="wing-map-placeholder-content">
				<span className="dashicons dashicons-location-alt"></span>
				<h3>{__('Wing Map Display', 'wing-map-display')}</h3>
				<p>{__('An interactive map showing all wing locations will be displayed here on the frontend.', 'wing-map-display')}</p>
				<p className="description">
					{__('The map automatically displays all published wing locations that have reviews with coordinates.', 'wing-map-display')}
				</p>
			</div>
		</div>
	);
}
