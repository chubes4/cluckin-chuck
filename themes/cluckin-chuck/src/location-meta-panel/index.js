/**
 * Wing Location Meta Panel - Block Editor Sidebar
 *
 * Provides a sidebar panel for managing wing_location post meta fields.
 */

import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	TextControl,
	TextareaControl,
	SelectControl,
	CheckboxControl,
	PanelRow,
	Button,
	Spinner,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const PRICE_OPTIONS = [
	{ label: __( 'Select...', 'cluckin-chuck' ), value: '' },
	{ label: __( '$ - Budget', 'cluckin-chuck' ), value: '$' },
	{ label: __( '$$ - Moderate', 'cluckin-chuck' ), value: '$$' },
	{ label: __( '$$$ - Upscale', 'cluckin-chuck' ), value: '$$$' },
	{ label: __( '$$$$ - Premium', 'cluckin-chuck' ), value: '$$$$' },
];

const LocationMetaPanel = () => {
	const [ isGeocoding, setIsGeocoding ] = useState( false );
	const [ geocodeError, setGeocodeError ] = useState( '' );

	const { postType, meta } = useSelect( ( select ) => {
		return {
			postType: select( 'core/editor' ).getCurrentPostType(),
			meta: select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {},
		};
	}, [] );

	const { editPost } = useDispatch( 'core/editor' );

	if ( postType !== 'wing_location' ) {
		return null;
	}

	const updateMeta = ( key, value ) => {
		editPost( { meta: { ...meta, [ key ]: value } } );
	};

	const handleGeocode = async () => {
		const address = meta.wing_address || '';
		if ( ! address.trim() ) {
			setGeocodeError( __( 'Please enter an address first', 'cluckin-chuck' ) );
			return;
		}

		setIsGeocoding( true );
		setGeocodeError( '' );

		try {
			const result = await apiFetch( {
				path: '/cluckin-chuck/v1/geocode',
				method: 'POST',
				data: { address },
			} );

			if ( result.lat && result.lng ) {
				editPost( {
					meta: {
						...meta,
						wing_latitude: result.lat,
						wing_longitude: result.lng,
					},
				} );
			} else {
				setGeocodeError( __( 'Could not find coordinates for this address', 'cluckin-chuck' ) );
			}
		} catch ( error ) {
			setGeocodeError( error.message || __( 'Geocoding failed', 'cluckin-chuck' ) );
		} finally {
			setIsGeocoding( false );
		}
	};

	return (
		<>
			<PluginDocumentSettingPanel
				name="wing-location-details"
				title={ __( 'Location Details', 'cluckin-chuck' ) }
				className="wing-location-meta-panel"
			>
				<PanelRow>
					<TextControl
						label={ __( 'Address', 'cluckin-chuck' ) }
						value={ meta.wing_address || '' }
						onChange={ ( value ) => updateMeta( 'wing_address', value ) }
					/>
				</PanelRow>

				<PanelRow className="geocode-row">
					<div className="geocode-controls">
						<TextControl
							label={ __( 'Latitude', 'cluckin-chuck' ) }
							type="number"
							step="0.000001"
							value={ meta.wing_latitude || '' }
							onChange={ ( value ) => updateMeta( 'wing_latitude', parseFloat( value ) || 0 ) }
						/>
						<TextControl
							label={ __( 'Longitude', 'cluckin-chuck' ) }
							type="number"
							step="0.000001"
							value={ meta.wing_longitude || '' }
							onChange={ ( value ) => updateMeta( 'wing_longitude', parseFloat( value ) || 0 ) }
						/>
						<Button
							variant="secondary"
							onClick={ handleGeocode }
							disabled={ isGeocoding }
							className="geocode-button"
						>
							{ isGeocoding ? <Spinner /> : __( 'Geocode Address', 'cluckin-chuck' ) }
						</Button>
						{ geocodeError && <p className="geocode-error">{ geocodeError }</p> }
					</div>
				</PanelRow>

				<PanelRow>
					<TextControl
						label={ __( 'Phone', 'cluckin-chuck' ) }
						type="tel"
						value={ meta.wing_phone || '' }
						onChange={ ( value ) => updateMeta( 'wing_phone', value ) }
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						label={ __( 'Website', 'cluckin-chuck' ) }
						type="url"
						value={ meta.wing_website || '' }
						onChange={ ( value ) => updateMeta( 'wing_website', value ) }
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						label={ __( 'Contact Email', 'cluckin-chuck' ) }
						type="email"
						value={ meta.wing_contact_email || '' }
						onChange={ ( value ) => updateMeta( 'wing_contact_email', value ) }
					/>
				</PanelRow>

				<PanelRow>
					<TextareaControl
						label={ __( 'Hours', 'cluckin-chuck' ) }
						value={ meta.wing_hours || '' }
						onChange={ ( value ) => updateMeta( 'wing_hours', value ) }
						rows={ 3 }
					/>
				</PanelRow>

				<PanelRow>
					<SelectControl
						label={ __( 'Price Range', 'cluckin-chuck' ) }
						value={ meta.wing_price_range || '' }
						options={ PRICE_OPTIONS }
						onChange={ ( value ) => updateMeta( 'wing_price_range', value ) }
					/>
				</PanelRow>
			</PluginDocumentSettingPanel>

			<PluginDocumentSettingPanel
				name="wing-location-services"
				title={ __( 'Services', 'cluckin-chuck' ) }
				className="wing-location-meta-panel"
			>
				<PanelRow>
					<CheckboxControl
						label={ __( 'Takeout Available', 'cluckin-chuck' ) }
						checked={ !! meta.wing_takeout }
						onChange={ ( value ) => updateMeta( 'wing_takeout', value ) }
					/>
				</PanelRow>

				<PanelRow>
					<CheckboxControl
						label={ __( 'Delivery Available', 'cluckin-chuck' ) }
						checked={ !! meta.wing_delivery }
						onChange={ ( value ) => updateMeta( 'wing_delivery', value ) }
					/>
				</PanelRow>

				<PanelRow>
					<CheckboxControl
						label={ __( 'Dine-in Available', 'cluckin-chuck' ) }
						checked={ !! meta.wing_dine_in }
						onChange={ ( value ) => updateMeta( 'wing_dine_in', value ) }
					/>
				</PanelRow>
			</PluginDocumentSettingPanel>

			<PluginDocumentSettingPanel
				name="wing-location-social"
				title={ __( 'Social Media', 'cluckin-chuck' ) }
				className="wing-location-meta-panel"
			>
				<PanelRow>
					<TextControl
						label={ __( 'Instagram URL', 'cluckin-chuck' ) }
						type="url"
						value={ meta.wing_instagram || '' }
						onChange={ ( value ) => updateMeta( 'wing_instagram', value ) }
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						label={ __( 'Facebook URL', 'cluckin-chuck' ) }
						type="url"
						value={ meta.wing_facebook || '' }
						onChange={ ( value ) => updateMeta( 'wing_facebook', value ) }
					/>
				</PanelRow>
			</PluginDocumentSettingPanel>

			<PluginDocumentSettingPanel
				name="wing-location-stats"
				title={ __( 'Review Stats', 'cluckin-chuck' ) }
				className="wing-location-meta-panel"
				initialOpen={ false }
			>
				<PanelRow>
					<TextControl
						label={ __( 'Average Rating', 'cluckin-chuck' ) }
						type="number"
						min="0"
						max="5"
						step="0.01"
						value={ meta.wing_average_rating || '' }
						onChange={ ( value ) => updateMeta( 'wing_average_rating', parseFloat( value ) || 0 ) }
						help={ __( 'Automatically calculated from reviews', 'cluckin-chuck' ) }
					/>
				</PanelRow>

				<PanelRow>
					<TextControl
						label={ __( 'Review Count', 'cluckin-chuck' ) }
						type="number"
						min="0"
						value={ meta.wing_review_count || '' }
						onChange={ ( value ) => updateMeta( 'wing_review_count', parseInt( value, 10 ) || 0 ) }
						help={ __( 'Automatically calculated from reviews', 'cluckin-chuck' ) }
					/>
				</PanelRow>
			</PluginDocumentSettingPanel>
		</>
	);
};

registerPlugin( 'wing-location-meta-panel', {
	render: LocationMetaPanel,
	icon: 'location',
} );
