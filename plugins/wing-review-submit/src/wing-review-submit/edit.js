/**
 * Wing Review Submit Block - Editor Component
 */

import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'wing-review-submit-editor-placeholder',
	} );

	return (
		<div { ...blockProps }>
			<div className="wing-review-submit-placeholder-content">
				<span className="dashicons dashicons-edit"></span>
				<h3>{ __( 'Wing Review Submit', 'wing-review-submit' ) }</h3>
				<p>
					{ __(
						'A submit button will appear here on the frontend.',
						'wing-review-submit'
					) }
				</p>
				<p className="description">
					{ __(
						'On wing location pages: submits reviews. On other pages: submits new locations.',
						'wing-review-submit'
					) }
				</p>
			</div>
		</div>
	);
}
