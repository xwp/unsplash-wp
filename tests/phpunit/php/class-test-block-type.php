<?php
/**
 * Tests for Block_Type class.
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Tests for Block_Type class.
 *
 * @coversDefaultClass \Unsplash\Block_Type
 */
class Test_Block_Type extends \WP_UnitTestCase {
	/**
	 * Generated attachment ID.
	 *
	 * @var int
	 */
	protected static $attachment_id;

	/**
	 * Setup before any tests are to be run for this class.
	 *
	 * @param object $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$attachment_id = $factory->attachment->create_object(
			'canola.jpg',
			0,
			[
				'post_mime_type' => 'image/jpeg',
				'post_excerpt'   => 'A sample caption',
			]
		);

		update_post_meta( self::$attachment_id, 'original_url', 'https://images.unsplash.com/test.jpg' );
		update_post_meta( self::$attachment_id, 'original_link', 'https://www.unsplash.com/foo' );
	}

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		$this->block_type = get_plugin_instance()->block_type;
	}

	/**
	 * Test init.
	 *
	 * @see Block_Type::init()
	 */
	public function test_init() {
		$this->assertEquals( 10, has_action( 'init', [ $this->block_type, 'register_blocks' ] ) );
		$this->assertEquals( 10, has_action( 'enqueue_block_editor_assets', [ $this->block_type, 'register_block_editor_assets' ] ) );
	}

	/**
	 * Test register_blocks.
	 *
	 * @see Block_Type::register_blocks()
	 */
	public function test_register_blocks() {
		// Unregister the block if it's registered already.
		\unregister_block_type( 'unsplash/image' );

		$this->block_type->register_blocks();

		$blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

		// Assert the block is registered.
		$this->assertTrue( array_key_exists( 'unsplash/image', $blocks ) );

		$block = $blocks['unsplash/image'];

		$this->assertEquals( [ $this->block_type, 'render_image_block' ], $block->render_callback );
		$this->assertEquals( 'unsplash-block-editor-js', $block->editor_script );
		$this->assertEquals( 'unsplash-block-editor-css', $block->editor_style );

		$block_folder = get_plugin_instance()->dir_path . '/assets/js/blocks/image/';
		$block_json   = $block_folder . 'block.json';
		$metadata     = json_decode( file_get_contents( $block_json ), true ); // phpcs:ignore

		$this->assertEquals( $metadata['category'], $block->category );
		$this->assertEquals( $metadata['attributes'], $block->attributes );
		$this->assertEquals( $metadata['supports'], $block->supports );
	}

	/**
	 * Test register_blocks when block.json does not exist.
	 *
	 * @see Block_Type::register_blocks()
	 */
	public function test_register_blocks_no_file() {
		\unregister_block_type( 'unsplash/image' );

		$block_folder = get_plugin_instance()->dir_path . '/assets/js/blocks/image/';

		rename( $block_folder . 'block.json', $block_folder . 'block' ); // phpcs:ignore

		$this->block_type->register_blocks();

		$blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

		rename( $block_folder . 'block', $block_folder . 'block.json' ); // phpcs:ignore

		// Assert the block is not registered.
		$this->assertFalse( array_key_exists( 'unsplash/image', $blocks ) );
	}

	/**
	 * Test for register_block_editor_assets() method.
	 *
	 * @see Block_Type::register_block_editor_assets()
	 */
	public function test_register_block_editor_assets() {
		$this->block_type->register_block_editor_assets();
		$this->assertTrue( wp_script_is( 'unsplash-block-editor-js', 'registered' ) );
		$this->assertTrue( wp_style_is( 'unsplash-block-editor-css', 'registered' ) );
	}

	/**
	 * Test for render_image_block() method.
	 *
	 * @see Block_Type::render_image_block()
	 */
	public function test_render_image_block() {
		$default = '<div class="wp-block-unsplash-image wp-block-image aligncenter is-style-default"><figure class="aligncenter size-large"><img src="http://unsplash.com/example.jpg" alt="example image" class="wp-image-1000" title="an example image"/><figcaption>Photo by <a href="https://unsplash.com/@example" rel="nofollow">Example User</a> on <a href="https://unsplash.com/?utm_source=unsplash&#038;utm_medium=referral" rel="nofollow">Unsplash</a> </figcaption></figure></div>';

		$content = $this->block_type->render_image_block(
			[
				'id'         => self::$attachment_id,
				'sizeSlug'   => 'large',
				'alt'        => 'Alt',
				'title'      => 'Title',
				'linkClass'  => 'link-class',
				'linkTarget' => '_blank',
				'rel'        => 'noopener',
				'caption'    => 'Caption',
				'href'       => 'http://example.com',
				'align'      => 'center',
			],
			$default
		);

		$this->assertContains( 'https://images.unsplash.com/test.jpg', $content );
		$this->assertContains( '<a class="link-class" href="http://example.com" target="_blank" rel="noopener"', $content );
		$this->assertContains( 'class="aligncenter size-large"', $content );
		$this->assertContains( 'alt="Alt"', $content );
		$this->assertContains( 'title="Title"', $content );
		$this->assertContains( '<figcaption>Caption</figcaption>', $content );

		$content = $this->block_type->render_image_block(
			[
				'id' => null,
			],
			$default
		);
		$this->assertEquals( $default, $content );

		$content = $this->block_type->render_image_block(
			[
				'id'       => self::$attachment_id,
				'sizeSlug' => 'medium',
				'width'    => 720,
				'height'   => 480,
			],
			$default
		);
		$this->assertContains( 'class="wp-block-unsplash-image wp-block-image size-medium is-resized"', $content );

		// Assert we handle invalid attachment ID and sizeSlug default.
		$content = $this->block_type->render_image_block(
			[
				'id' => 1000,
			],
			$default
		);
		$this->assertEquals( '<figure class="wp-block-unsplash-image wp-block-image size-full"><img src="http://unsplash.com/example.jpg" alt="" class="wp-image-1000" width="100%" height="100%" title="" srcset="" sizes="" /></figure>', $content );

		// Assert we handle invalid attachment ID and invalid content.
		$content = $this->block_type->render_image_block(
			[
				'id' => 1000,
			],
			'<figure><img src="" /></figure>'
		);
		$this->assertEquals( '<figure><img src="" /></figure>', $content );
	}
}
