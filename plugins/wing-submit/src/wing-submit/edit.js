/**
 * Wing Submit Block - Editor Component
 *
 * Displays a placeholder in the editor. The actual button and modal are rendered server-side.
 */

import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'wing-submit-editor-placeholder',
	} );

	return (
		<div { ...blockProps }>
			<div className="wing-submit-placeholder-content">
				<span className="dashicons dashicons-edit"></span>
				<h3>{ __( 'Wing Location Submit', 'wing-submit' ) }</h3>
				<p>
					{ __(
						'A submit button will appear here on the frontend.',
						'wing-submit'
					) }
				</p>
				<p className="description">
					{ __(
						'Users can submit new wing locations or add reviews to existing locations via a modal form.',
						'wing-submit'
					) }
				</p>
			</div>
		</div>
	);
}
