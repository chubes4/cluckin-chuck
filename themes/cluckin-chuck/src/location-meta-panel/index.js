/**
 * Wing Location Meta Panel - Block Editor Sidebar
 *
 * Provides a sidebar panel for managing wing_location post meta fields.
 * Only 3 editable fields: Address, Website, Instagram.
 * All other data is auto-calculated from reviews.
 */

import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { TextControl, PanelRow } from '@wordpress/components';

const LocationMetaPanel = () => {
	const { postType, meta } = useSelect( ( select ) => {
		return {
			postType: select( 'core/editor' ).getCurrentPostType(),
			meta:
				select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {},
		};
	}, [] );

	const { editPost } = useDispatch( 'core/editor' );

	if ( postType !== 'wing_location' ) {
		return null;
	}

	const updateMeta = ( key, value ) => {
		editPost( { meta: { ...meta, [ key ]: value } } );
	};

	return (
		<PluginDocumentSettingPanel
			name="wing-location-details"
			title={ __( 'Location Details', 'cluckin-chuck' ) }
			className="wing-location-meta-panel"
		>
			<PanelRow>
				<TextControl
					label={ __( 'Address', 'cluckin-chuck' ) }
					value={ meta.wing_address || '' }
					onChange={ ( value ) =>
						updateMeta( 'wing_address', value )
					}
					help={ __(
						'Coordinates are auto-populated when you save',
						'cluckin-chuck'
					) }
				/>
			</PanelRow>

			<PanelRow>
				<TextControl
					label={ __( 'Website', 'cluckin-chuck' ) }
					type="url"
					value={ meta.wing_website || '' }
					onChange={ ( value ) =>
						updateMeta( 'wing_website', value )
					}
				/>
			</PanelRow>

			<PanelRow>
				<TextControl
					label={ __( 'Instagram URL', 'cluckin-chuck' ) }
					type="url"
					value={ meta.wing_instagram || '' }
					onChange={ ( value ) =>
						updateMeta( 'wing_instagram', value )
					}
				/>
			</PanelRow>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'wing-location-meta-panel', {
	render: LocationMetaPanel,
	icon: 'location',
} );
