/**
 * External dependencies
 */
import classnames from 'classnames';
import { get, filter, map, last, omit, pick, includes } from 'lodash';

/**
 * WordPress dependencies
 */
import {
	Button,
	ExternalLink,
	Placeholder,
	PanelBody,
	ResizableBox,
	TextareaControl,
	TextControl,
	ToolbarGroup,
} from '@wordpress/components';
import {
	BlockControls,
	InspectorControls,
	InspectorAdvancedControls,
	RichText,
	__experimentalImageSizeControl as ImageSizeControl,
	__experimentalImageURLInputUI as ImageURLInputUI,
} from '@wordpress/block-editor';
import { useViewportMatch } from '@wordpress/compose';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { getPath } from '@wordpress/url';
import {
	MIN_SIZE,
	LINK_DESTINATION_MEDIA,
	LINK_DESTINATION_ATTACHMENT,
	DEFAULT_SIZE_SLUG,
} from '@wordpress/block-library/build/image/constants';

/**
 * Internal dependencies
 */
import useImageSize from './image-size';
import icon from './icon';
import './editor.css';

const pickRelevantImageProps = image => {
	const imageProps = pick( image, [ 'alt', 'id', 'link', 'caption' ] );
	imageProps.url =
		get( image, [ 'sizes', 'large', 'url' ] ) ||
		get( image, [ 'media_details', 'sizes', 'large', 'source_url' ] ) ||
		image.url;
	return imageProps;
};

const getFilename = fileUrl => {
	const path = getPath( fileUrl );
	if ( path ) {
		return last( path.split( '/' ) );
	}
};

const ImageEdit = ( {
	attributes: {
		url,
		alt,
		caption,
		align,
		id,
		href,
		rel,
		linkClass,
		linkDestination,
		title,
		width,
		height,
		linkTarget,
		sizeSlug,
	},
	setAttributes,
	isSelected,
	className,
} ) => {
	const ref = useRef();
	const { image, maxWidth, isRTL, imageSizes } = useSelect(
		select => {
			const { getMedia } = select( 'core' );
			const { getSettings } = select( 'core/block-editor' );
			return {
				...pick( getSettings(), [
					'mediaUpload',
					'imageSizes',
					'isRTL',
					'maxWidth',
				] ),
				image: id && isSelected ? getMedia( id ) : null,
			};
		},
		[ id, isSelected ]
	);
	const { toggleSelection } = useDispatch( 'core/block-editor' );
	const isLargeViewport = useViewportMatch( 'medium' );
	const [ captionFocused, setCaptionFocused ] = useState( false );
	const isWideAligned = includes( [ 'wide', 'full' ], align );

	function onResizeStart() {
		toggleSelection( false );
	}

	function onResizeStop() {
		toggleSelection( true );
	}

	const frame = useRef( null );

	useEffect( () => {
		frame.current = new wp.media.view.MediaFrame.Unsplash();

		// When an image is selected in the media frame.
		frame.current.on( 'select', onSelect );
	}, [] );

	const onSelect = () => {
		// Get media attachment details from the frame state
		const selected = frame.current
			.state()
			.get( 'selection' )
			.toJSON();
		const media = selected[ 0 ];

		if ( ! media || ! media.url ) {
			setAttributes( {
				url: undefined,
				alt: undefined,
				id: undefined,
				title: undefined,
				caption: undefined,
			} );
		}

		let imageAttributes = pickRelevantImageProps( media );

		// If a caption text was meanwhile written by the user,
		// make sure the text is not overwritten by empty captions
		if ( caption && ! get( imageAttributes, [ 'caption' ] ) ) {
			imageAttributes = omit( imageAttributes, [ 'caption' ] );
		}

		let additionalAttributes;
		// Reset the dimension attributes if changing to a different image.
		if ( ! media.id || media.id !== id ) {
			additionalAttributes = {
				width: undefined,
				height: undefined,
				sizeSlug: DEFAULT_SIZE_SLUG,
			};
		} else {
			// Keep the same url when selecting the same file, so "Image Size" option is not changed.
			additionalAttributes = { url };
		}

		// Check if the image is linked to it's media.
		if ( linkDestination === LINK_DESTINATION_MEDIA ) {
			// Update the media link.
			imageAttributes.href = media.url;
		}

		// Check if the image is linked to the attachment page.
		if ( linkDestination === LINK_DESTINATION_ATTACHMENT ) {
			// Update the media link.
			imageAttributes.href = media.link;
		}

		setAttributes( {
			...imageAttributes,
			...additionalAttributes,
		} );
	};

	const onOpen = event => {
		event.preventDefault();
		frame.current.open();
	};

	function onSetHref( props ) {
		if ( props.href && ! props.linkDestination ) {
			props.linkDestination = '';
		}
		setAttributes( props );
	}

	function onSetTitle( value ) {
		// This is the HTML title attribute, separate from the media object
		// title.
		setAttributes( { title: value } );
	}

	function onFocusCaption() {
		if ( ! captionFocused ) {
			setCaptionFocused( true );
		}
	}

	function onImageClick() {
		if ( captionFocused ) {
			setCaptionFocused( false );
		}
	}

	function updateAlt( newAlt ) {
		setAttributes( { alt: newAlt } );
	}

	function updateImage( newSizeSlug ) {
		const newUrl = get( image, [
			'media_details',
			'sizes',
			newSizeSlug,
			'source_url',
		] );
		if ( ! newUrl ) {
			return null;
		}

		setAttributes( {
			url: newUrl,
			width: undefined,
			height: undefined,
			sizeSlug: newSizeSlug,
		} );
	}

	function getImageSizeOptions() {
		return map(
			filter( imageSizes, ( { slug } ) =>
				get( image, [ 'media_details', 'sizes', slug, 'source_url' ] )
			),
			( { name, slug } ) => ( { value: slug, label: name } )
		);
	}

	const controls = (
		<BlockControls>
			{ url && (
				<>
					<ToolbarGroup className="media-replace-">
						<Button onClick={ onOpen }>{ __( 'Replace', 'unsplash' ) }</Button>
					</ToolbarGroup>
					<ToolbarGroup>
						<ImageURLInputUI
							url={ href || '' }
							onChangeUrl={ onSetHref }
							linkDestination={ linkDestination }
							mediaUrl={ image && image.source_url }
							mediaLink={ image && image.link }
							linkTarget={ linkTarget }
							linkClass={ linkClass }
							rel={ rel }
						/>
					</ToolbarGroup>
				</>
			) }
		</BlockControls>
	);

	const {
		imageWidthWithinContainer,
		imageHeightWithinContainer,
		imageWidth,
		imageHeight,
	} = useImageSize( ref, url, [ align ] );

	if ( ! url ) {
		return (
			<>
				{ controls }
				<Placeholder
					icon={ icon }
					label={ __( 'Unsplash', 'unsplash' ) }
					instructions={ __(
						"Search and select from the internet's source of freely usable images",
						'unsplash'
					) }
					className={ 'placeholderClassName' }
				>
					<Button isPrimary onClick={ onOpen }>
						{ __( 'Search', 'unsplash' ) }
					</Button>
				</Placeholder>
			</>
		);
	}

	const classes = classnames( className, {
		'is-resized': !! width || !! height,
		'is-focused': isSelected,
		[ `size-${ sizeSlug }` ]: sizeSlug,
		[ `align${ align }` ]: align,
	} );

	const isResizable = ! isWideAligned && isLargeViewport;
	const imageSizeOptions = getImageSizeOptions();

	const inspectorControls = (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Image settings', 'unsplash' ) }>
					<TextareaControl
						label={ __( 'Alt text (alternative text)', 'unsplash' ) }
						value={ alt }
						onChange={ updateAlt }
						help={
							<>
								<ExternalLink href="https://www.w3.org/WAI/tutorials/images/decision-tree">
									{ __( 'Describe the purpose of the image', 'unsplash' ) }
								</ExternalLink>
								{ __(
									'Leave empty if the image is purely decorative.',
									'unsplash'
								) }
							</>
						}
					/>
					<ImageSizeControl
						onChangeImage={ updateImage }
						onChange={ value => setAttributes( value ) }
						slug={ sizeSlug }
						width={ width }
						height={ height }
						imageSizeOptions={ imageSizeOptions }
						isResizable={ isResizable }
						imageWidth={ imageWidth }
						imageHeight={ imageHeight }
					/>
				</PanelBody>
			</InspectorControls>
			<InspectorAdvancedControls>
				<TextControl
					label={ __( 'Title attribute', 'unsplash' ) }
					value={ title || '' }
					onChange={ onSetTitle }
					help={
						<>
							{ __(
								'Describe the role of this image on the page.',
								'unsplash'
							) }
							<ExternalLink href="https://www.w3.org/TR/html52/dom.html#the-title-attribute">
								{ __(
									'(Note: many devices and browsers do not display this text.)',
									'unsplash'
								) }
							</ExternalLink>
						</>
					}
				/>
			</InspectorAdvancedControls>
		</>
	);

	const filename = getFilename( url );
	let defaultedAlt;

	if ( alt ) {
		defaultedAlt = alt;
	} else if ( filename ) {
		defaultedAlt = sprintf(
			/* translators: %s: file name */
			__(
				'This image has an empty alt attribute; its file name is %s',
				'unsplash'
			),
			filename
		);
	} else {
		defaultedAlt = __( 'This image has an empty alt attribute', 'unsplash' );
	}

	let img = (
		// Disable reason: Image itself is not meant to be interactive, but
		// should direct focus to block.
		/* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
		<>
			{ inspectorControls }
			<img src={ url } alt={ defaultedAlt } onClick={ onImageClick } />
		</>
		/* eslint-enable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
	);

	if ( ! isResizable || ! imageWidthWithinContainer ) {
		img = <div style={ { width, height } }>{ img }</div>;
	} else {
		const currentWidth = width || imageWidthWithinContainer;
		const currentHeight = height || imageHeightWithinContainer;

		const ratio = imageWidth / imageHeight;
		const minWidth = imageWidth < imageHeight ? MIN_SIZE : MIN_SIZE * ratio;
		const minHeight = imageHeight < imageWidth ? MIN_SIZE : MIN_SIZE / ratio;

		// With the current implementation of ResizableBox, an image needs an
		// explicit pixel value for the max-width. In absence of being able to
		// set the content-width, this max-width is currently dictated by the
		// vanilla editor style. The following variable adds a buffer to this
		// vanilla style, so 3rd party themes have some wiggleroom. This does,
		// in most cases, allow you to scale the image beyond the width of the
		// main column, though not infinitely.
		// @todo It would be good to revisit this once a content-width variable
		// becomes available.
		const maxWidthBuffer = maxWidth * 2.5;

		let showRightHandle = false;
		let showLeftHandle = false;

		/* eslint-disable no-lonely-if */
		// See https://github.com/WordPress/gutenberg/issues/7584.
		if ( align === 'center' ) {
			// When the image is centered, show both handles.
			showRightHandle = true;
			showLeftHandle = true;
		} else if ( isRTL ) {
			// In RTL mode the image is on the right by default.
			// Show the right handle and hide the left handle only when it is
			// aligned left. Otherwise always show the left handle.
			if ( align === 'left' ) {
				showRightHandle = true;
			} else {
				showLeftHandle = true;
			}
		} else {
			// Show the left handle and hide the right handle only when the
			// image is aligned right. Otherwise always show the right handle.
			if ( align === 'right' ) {
				showLeftHandle = true;
			} else {
				showRightHandle = true;
			}
		}
		/* eslint-enable no-lonely-if */

		img = (
			<ResizableBox
				size={ { width, height } }
				showHandle={ isSelected }
				minWidth={ minWidth }
				maxWidth={ maxWidthBuffer }
				minHeight={ minHeight }
				maxHeight={ maxWidthBuffer / ratio }
				lockAspectRatio
				enable={ {
					top: false,
					right: showRightHandle,
					bottom: true,
					left: showLeftHandle,
				} }
				onResizeStart={ onResizeStart }
				onResizeStop={ ( event, direction, elt, delta ) => {
					onResizeStop();
					setAttributes( {
						width: parseInt( currentWidth + delta.width, 10 ),
						height: parseInt( currentHeight + delta.height, 10 ),
					} );
				} }
			>
				{ img }
			</ResizableBox>
		);
	}

	return (
		<>
			{ controls }
			<figure ref={ ref } className={ classes }>
				{ img }

				{ ( ! RichText.isEmpty( caption ) || isSelected ) && (
					<RichText
						className="blocks-gallery-caption"
						tagName="figcaption"
						placeholder={ __( 'Write caption…', 'unsplash' ) }
						value={ caption }
						unstableOnFocus={ onFocusCaption }
						onChange={ value => setAttributes( { caption: value } ) }
						isSelected={ captionFocused }
						inlineToolbar
					/>
				) }
			</figure>
		</>
	);
};

export default ImageEdit;
