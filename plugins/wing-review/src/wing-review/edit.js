/**
 * Wing Review Block - Editor Component
 *
 * Provides inline canvas editing for all review and location attributes.
 * Reviewer info auto-populated from post author.
 * Location fields only visible for first wing-review block in post.
 */

import { useBlockProps } from '@wordpress/block-editor';
import { TextControl, TextareaControl, SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function Edit({ attributes, setAttributes, clientId }) {
	const blockProps = useBlockProps({
		className: 'wing-review-editor',
	});

	const {
		reviewerName = '',
		reviewerEmail = '',
		rating = 0,
		sauceRating = 0,
		crispinessRating = 0,
		reviewText = '',
		timestamp = '',
		address = '',
		latitude = 0,
		longitude = 0,
		phone = '',
		website = '',
		hours = '',
		priceRange = '',
		takeout = false,
		delivery = false,
		dineIn = false,
	} = attributes;

	const { authorName, authorEmail, isFirstBlock } = useSelect((select) => {
		const { getEditedPostAttribute } = select('core/editor');
		const authorId = getEditedPostAttribute('author');
		const user = select('core').getUser(authorId);

		const { getBlocks } = select('core/block-editor');
		const allBlocks = getBlocks();
		const wingReviews = allBlocks.filter(block => block.name === 'wing-map/wing-review');
		const isFirst = wingReviews.length > 0 && wingReviews[0].clientId === clientId;

		return {
			authorName: user?.name || '',
			authorEmail: user?.email || '',
			isFirstBlock: isFirst,
		};
	}, [clientId]);

	useEffect(() => {
		if (!reviewerName && authorName) {
			setAttributes({ reviewerName: authorName });
		}
		if (!reviewerEmail && authorEmail) {
			setAttributes({ reviewerEmail: authorEmail });
		}
	}, [authorName, authorEmail, reviewerName, reviewerEmail, setAttributes]);

	useEffect(() => {
		if (!timestamp) {
			setAttributes({ timestamp: new Date().toISOString() });
		}
	}, [timestamp, setAttributes]);

	const renderStars = (count) => {
		const filled = '★'.repeat(Math.max(0, Math.min(5, Math.round(count))));
		const empty = '☆'.repeat(Math.max(0, 5 - Math.round(count)));
		return filled + empty;
	};

	return (
		<div {...blockProps}>
			<div className="wing-review-editor-header">
				<h4>{__('Wing Review', 'wing-review')}</h4>
			</div>

			<div className="wing-review-author-section">
				<div className="wing-author-info">
					<strong>{__('Reviewer:', 'wing-review')}</strong> {reviewerName || __('(Auto-populated from post author)', 'wing-review')}
					{reviewerEmail && <span className="wing-author-email"> ({reviewerEmail})</span>}
				</div>
				<p className="wing-help-text">{__('Auto-populated from WordPress post author. No manual editing needed.', 'wing-review')}</p>
			</div>

			<div className="wing-ratings-section">
				<h5>{__('Ratings', 'wing-review')}</h5>

				<div className="wing-rating-field">
					<label htmlFor={`wing-overall-rating-${clientId}`}>
						{__('Overall Rating (1-5)', 'wing-review')} <span className="required">*</span>
					</label>
					<div className="wing-rating-input-group">
						<TextControl
							id={`wing-overall-rating-${clientId}`}
							type="number"
							min="0"
							max="5"
							step="0.5"
							value={rating}
							onChange={(val) => setAttributes({ rating: parseFloat(val) || 0 })}
						/>
						<span className="wing-star-preview">{renderStars(rating)}</span>
					</div>
				</div>

				<div className="wing-rating-field">
					<label htmlFor={`wing-sauce-rating-${clientId}`}>
						{__('Sauce Rating (0-5)', 'wing-review')}
					</label>
					<div className="wing-rating-input-group">
						<TextControl
							id={`wing-sauce-rating-${clientId}`}
							type="number"
							min="0"
							max="5"
							step="0.5"
							value={sauceRating}
							onChange={(val) => setAttributes({ sauceRating: parseFloat(val) || 0 })}
						/>
						<span className="wing-star-preview">{renderStars(sauceRating)}</span>
					</div>
				</div>

				<div className="wing-rating-field">
					<label htmlFor={`wing-crisp-rating-${clientId}`}>
						{__('Crispiness Rating (0-5)', 'wing-review')}
					</label>
					<div className="wing-rating-input-group">
						<TextControl
							id={`wing-crisp-rating-${clientId}`}
							type="number"
							min="0"
							max="5"
							step="0.5"
							value={crispinessRating}
							onChange={(val) => setAttributes({ crispinessRating: parseFloat(val) || 0 })}
						/>
						<span className="wing-star-preview">{renderStars(crispinessRating)}</span>
					</div>
				</div>
			</div>

			<div className="wing-review-text-section">
				<label htmlFor={`wing-review-text-${clientId}`}>
					{__('Review Text', 'wing-review')} <span className="required">*</span>
				</label>
				<TextareaControl
					id={`wing-review-text-${clientId}`}
					value={reviewText}
					onChange={(val) => setAttributes({ reviewText: val })}
					placeholder={__('Write your detailed review here...', 'wing-review')}
					rows={6}
				/>
			</div>

			{isFirstBlock && (
				<div className="wing-location-section">
					<h5>{__('Location Information', 'wing-review')}</h5>
					<p className="wing-help-text">
						{__('Location data only displays on the first review block. This data is shared across all reviews for this location.', 'wing-review')}
					</p>

					<TextControl
						label={__('Address', 'wing-review')}
						value={address}
						onChange={(val) => setAttributes({ address: val })}
						placeholder={__('123 Wing Street, City, State ZIP', 'wing-review')}
					/>

					<div className="wing-coordinates-readonly">
						<strong>{__('Coordinates:', 'wing-review')}</strong>
						{latitude && longitude ? (
							<span> {latitude.toFixed(6)}, {longitude.toFixed(6)}</span>
						) : (
							<span className="wing-no-coords"> {__('(Set via geocoding in submission form)', 'wing-review')}</span>
						)}
					</div>

					<div className="wing-contact-fields">
						<TextControl
							label={__('Phone', 'wing-review')}
							type="tel"
							value={phone}
							onChange={(val) => setAttributes({ phone: val })}
							placeholder={__('(555) 123-4567', 'wing-review')}
						/>

						<TextControl
							label={__('Website', 'wing-review')}
							type="url"
							value={website}
							onChange={(val) => setAttributes({ website: val })}
							placeholder={__('https://example.com', 'wing-review')}
						/>
					</div>

					<TextareaControl
						label={__('Hours', 'wing-review')}
						value={hours}
						onChange={(val) => setAttributes({ hours: val })}
						placeholder={__('Mon-Fri: 11am-10pm\nSat-Sun: 12pm-11pm', 'wing-review')}
						rows={3}
					/>

					<SelectControl
						label={__('Price Range', 'wing-review')}
						value={priceRange}
						options={[
							{ label: __('Not specified', 'wing-review'), value: '' },
							{ label: '$ - Budget', value: '$' },
							{ label: '$$ - Moderate', value: '$$' },
							{ label: '$$$ - Upscale', value: '$$$' },
							{ label: '$$$$ - Premium', value: '$$$$' },
						]}
						onChange={(val) => setAttributes({ priceRange: val })}
					/>

					<div className="wing-services-section">
						<label>{__('Services Available', 'wing-review')}</label>
						<div className="wing-services-toggles">
							<ToggleControl
								label={__('Takeout', 'wing-review')}
								checked={takeout}
								onChange={(val) => setAttributes({ takeout: val })}
							/>
							<ToggleControl
								label={__('Delivery', 'wing-review')}
								checked={delivery}
								onChange={(val) => setAttributes({ delivery: val })}
							/>
							<ToggleControl
								label={__('Dine-in', 'wing-review')}
								checked={dineIn}
								onChange={(val) => setAttributes({ dineIn: val })}
							/>
						</div>
					</div>
				</div>
			)}

			{!isFirstBlock && (
				<div className="wing-not-first-notice">
					<p>{__('Location information is managed in the first review block.', 'wing-review')}</p>
				</div>
			)}
		</div>
	);
}
