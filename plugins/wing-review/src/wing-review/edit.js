/**
 * Wing Review Block - Editor Component
 *
 * Provides inline canvas editing for review attributes.
 * Reviewer info auto-populated from post author.
 * Location data is managed separately via theme meta.
 */

import { useBlockProps } from '@wordpress/block-editor';
import { TextControl, TextareaControl } from '@wordpress/components';
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
		saucesTried = '',
		wingCount = 0,
		price = 0,
	} = attributes;

	const pricePerWing = wingCount > 0 ? (price / wingCount).toFixed(2) : '0.00';

	const { authorName, authorEmail } = useSelect((select) => {
		const { getEditedPostAttribute } = select('core/editor');
		const authorId = getEditedPostAttribute('author');
		const user = select('core').getUser(authorId);

		return {
			authorName: user?.name || '',
			authorEmail: user?.email || '',
		};
	}, []);

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

			<div className="wing-pricing-section">
				<h5>{__('Pricing', 'wing-review')}</h5>

				<div className="wing-form-row">
					<div className="wing-form-field">
						<label htmlFor={`wing-count-${clientId}`}>
							{__('Number of Wings', 'wing-review')} <span className="required">*</span>
						</label>
						<TextControl
							id={`wing-count-${clientId}`}
							type="number"
							min="1"
							step="1"
							value={wingCount}
							onChange={(val) => setAttributes({ wingCount: parseInt(val, 10) || 0 })}
						/>
					</div>
					<div className="wing-form-field">
						<label htmlFor={`wing-price-${clientId}`}>
							{__('Price Paid ($)', 'wing-review')} <span className="required">*</span>
						</label>
						<TextControl
							id={`wing-price-${clientId}`}
							type="number"
							min="0"
							step="0.01"
							value={price}
							onChange={(val) => setAttributes({ price: parseFloat(val) || 0 })}
						/>
					</div>
				</div>

				<div className="wing-ppw-display">
					<strong>{__('Price Per Wing:', 'wing-review')}</strong> ${pricePerWing}
				</div>
			</div>

			<div className="wing-sauces-section">
				<label htmlFor={`wing-sauces-${clientId}`}>
					{__('Sauces Tried', 'wing-review')}
				</label>
				<TextControl
					id={`wing-sauces-${clientId}`}
					value={saucesTried}
					onChange={(val) => setAttributes({ saucesTried: val })}
					placeholder={__('e.g., Buffalo, Garlic Parm, Mango Habanero', 'wing-review')}
				/>
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
		</div>
	);
}
