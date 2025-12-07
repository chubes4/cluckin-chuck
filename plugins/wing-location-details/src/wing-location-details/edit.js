/**
 * Wing Location Details Block - Editor Component
 *
 * Displays a placeholder in the editor indicating that location details
 * are managed via the sidebar meta box and rendered server-side.
 */

import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { store } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

export default function Edit() {
	const blockProps = useBlockProps({
		className: 'wing-location-details-editor',
	});

	return (
		<div {...blockProps}>
			<Placeholder
				icon={store}
				label={__('Wing Location Details', 'wing-location-details')}
				instructions={__(
					'This block displays location details (address, phone, hours, services, ratings) from the post metadata. Edit location info in the sidebar.',
					'wing-location-details'
				)}
			/>
		</div>
	);
}
