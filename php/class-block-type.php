<?php
/**
 * Bootstraps custom blocks.
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Block type class.
 */
class Block_Type {
	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	public $plugin;

	/**
	 * Block_Type constructor.
	 *
	 * @param Plugin $plugin Instance of the plugin abstraction.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initiate the class.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_blocks' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'register_block_editor_assets' ] );
	}

	/**
	 * Register our custom blocks.
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$blocks_dir    = $this->plugin->dir_path . '/assets/js/blocks/';
		$block_folders = [
			'image',
		];

		foreach ( $block_folders as $block_name ) {
			$block_json_file = $blocks_dir . $block_name . '/block.json';
			if ( ! file_exists( $block_json_file ) ) {
				continue;
			}

			$metadata = json_decode( file_get_contents( $block_json_file ), true ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
			if ( ! is_array( $metadata ) || ! $metadata['name'] ) {
				continue;
			}

			$metadata['editor_script'] = 'unsplash-block-editor-js';
			$metadata['editor_style']  = 'unsplash-block-editor-css';

			$callback = "render_{$block_name}_block";
			if ( method_exists( $this, $callback ) ) {
				$metadata['render_callback'] = [ $this, $callback ];
			}

			register_block_type( $metadata['name'], $metadata );
		}
	}

	/**
	 * Load Gutenberg assets.
	 */
	public function register_block_editor_assets() {
		// Register block editor assets.
		$asset_file   = $this->plugin->dir_path . '/assets/js/block-editor.asset.php';
		$asset        = is_readable( $asset_file ) ? require $asset_file : [];
		$version      = isset( $asset['version'] ) ? $asset['version'] : $this->plugin->asset_version();
		$dependencies = isset( $asset['dependencies'] ) ? $asset['dependencies'] : [];

		wp_register_script(
			'unsplash-block-editor-js',
			$this->plugin->asset_url( 'assets/js/block-editor.js' ),
			$dependencies,
			$version,
			false
		);

		wp_register_style(
			'unsplash-block-editor-css',
			$this->plugin->asset_url( 'assets/css/block-editor-compiled.css' ),
			[],
			$version
		);

		wp_styles()->add_data( 'unsplash-block-editor-css', 'rtl', 'replace' );
	}

	/**
	 * Render image block.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content    The block content.
	 *
	 * @return string
	 */
	public function render_image_block( $attributes, $content ) {
		if ( empty( $attributes['id'] ) ) {
			return $content;
		}

		$id        = absint( $attributes['id'] );
		$size_slug = empty( $attributes['sizeSlug'] ) ? 'full' : $attributes['sizeSlug'];
		$src       = '';
		$width     = '100%';
		$height    = '100%';

		remove_filter( 'wp_get_attachment_url', [ $this->plugin->hotlink, 'wp_get_attachment_url' ], 10 );
		remove_filter( 'image_downsize', [ $this->plugin->hotlink, 'image_downsize' ], 10 );

		$image = wp_get_attachment_image_src( $id, $size_slug );

		if ( ! empty( $image ) ) {
			list( $src, $width, $height ) = $image;
		} elseif ( preg_match( '/src="([^"]+)"/', $content, $matches ) ) {
			// Fallback and read the image source from saved block content.
			$src = $matches[1];
		}

		add_filter( 'wp_get_attachment_url', [ $this->plugin->hotlink, 'wp_get_attachment_url' ], 10, 2 );
		add_filter( 'image_downsize', [ $this->plugin->hotlink, 'image_downsize' ], 10, 3 );

		// Bail out if we still don't have a valid image src.
		if ( empty( $src ) ) {
			return $content;
		}

		$image_meta = wp_get_attachment_metadata( $id );

		if ( isset( $attributes['width'] ) && isset( $attributes['height'] ) && ! empty( absint( $attributes['width'] ) ) && ! empty( absint( $attributes['height'] ) ) ) {
			// width and height are set in the block, use those.
			$width  = absint( $attributes['width'] );
			$height = absint( $attributes['height'] );
		} elseif ( ! empty( $image_meta ) && ! empty( $image_meta['sizes'] ) ) {
			// If sizes are set in image_meta use those.
			foreach ( $image_meta['sizes'] as $size => $args ) {
				if ( $size === $size_slug ) {
					$width  = $args['width'];
					$height = $args['height'];
				}
			}
		}

		$unsplash_url = $this->plugin->hotlink->get_unsplash_url( $id );
		$cropped      = $this->plugin->hotlink->is_cropped_image( $id );
		if ( ! $unsplash_url || $cropped ) {
			$new_src = $src;
		} else {
			$new_src = $this->plugin->get_original_url_with_size( $unsplash_url, $width, $height );
		}

		$srcset = '';
		$sizes  = '';

		if ( is_array( $image_meta ) ) {
			$size_array = array( absint( $width ), absint( $height ) );
			$srcset     = wp_calculate_image_srcset( $size_array, $src, $image_meta, $id );
			$sizes      = wp_calculate_image_sizes( $size_array, $src, $image_meta, $id );
		}

		$image = sprintf(
			'<img src="%1$s" alt="%2$s" class="%3$s" width="%4$s" height="%5$s" title="%6$s" srcset="%7$s" sizes="%8$s" />',
			esc_url( $new_src ),
			esc_attr( isset( $attributes['alt'] ) ? $attributes['alt'] : '' ),
			esc_attr( 'wp-image-' . $id ),
			esc_attr( $width ),
			esc_attr( $height ),
			esc_attr( isset( $attributes['title'] ) ? $attributes['title'] : '' ),
			esc_attr( $srcset ),
			esc_attr( $sizes )
		);

		$figure = ! empty( $attributes['href'] )
			? sprintf(
				'<a class="%1$s" href="%2$s" target="%3$s" rel="%4$s">
					%5$s
				</a>',
				esc_attr( isset( $attributes['linkClass'] ) ? $attributes['linkClass'] : '' ),
				esc_url( $attributes['href'] ),
				esc_attr( isset( $attributes['linkTarget'] ) ? $attributes['linkTarget'] : '' ),
				esc_attr( isset( $attributes['rel'] ) ? $attributes['rel'] : '' ),
				$image
			)
			: $image;

		$caption = ! empty( $attributes['caption'] )
			? sprintf(
				'<figcaption>%1$s</figcaption>',
				wp_kses_post( $attributes['caption'] )
			)
			: '';

		$is_aligned = ! empty( $attributes['align'] ) && in_array( $attributes['align'], [ 'left', 'right', 'center' ], true );
		$classes    = ! empty( $attributes['className'] ) ? $attributes['className'] : '';

		$figure_class = $is_aligned ? [] : [ 'wp-block-unsplash-image', 'wp-block-image', $classes ];
		if ( ! empty( $attributes['align'] ) ) {
			$figure_class[] = 'align' . $attributes['align'];
		}
		if ( ! empty( $size_slug ) ) {
			$figure_class[] = 'size-' . $size_slug;
		}
		if ( ! empty( $attributes['width'] ) || ! empty( $attributes['height'] ) ) {
			$figure_class[] = 'is-resized';
		}

		$figure = sprintf(
			'<figure class="%1$s">%2$s</figure>',
			esc_attr( implode( ' ', array_filter( $figure_class ) ) ),
			$figure . $caption
		);

		if ( $is_aligned ) {
			return sprintf(
				'<div class="%1$s">%2$s</div>',
				esc_attr( implode( ' ', array_filter( [ 'wp-block-unsplash-image', 'wp-block-image', $classes ] ) ) ),
				$figure
			);
		}

		return $figure;
	}
}
