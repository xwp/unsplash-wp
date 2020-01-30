<?php
/**
 * Tests for Router class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

/**
 * Tests for the Router class.
 */
class TestRouter extends \WP_UnitTestCase {

	/**
	 * Router instance.
	 *
	 * @var Plugin
	 */
	public $router;

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		$this->router = get_router_instance();
	}

	/**
	 * Test constructor.
	 *
	 * @covers Router::__construct()
	 */
	public function test_construct() {
		$this->assertEquals( true, did_action( 'plugins_loaded', [ $this->router, 'init' ] ) );
	}

	/**
	 * Test init.
	 *
	 * @covers Router::init()
	 */
	public function test_init() {
		$this->assertEquals( 10, has_action( 'admin_enqueue_scripts', [ $this->router, 'enqueue_scripts' ] ) );
		$this->assertEquals( 10, has_action( 'admin_enqueue_scripts', [ $this->router, 'enqueue_scripts' ] ) );
		$this->assertEquals( 10, has_action( 'enqueue_block_editor_assets', [ $this->router, 'enqueue_editor_assets' ] ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_query-unsplash', [ $this->router, 'wp_ajax_query_unsplash' ] ) );
		$this->assertEquals( false, has_action( 'wp_ajax_send-attachment-to-editor', 'wp_ajax_send_attachment_to_editor' ) );
		$this->assertEquals( 0, has_action( 'wp_ajax_send-attachment-to-editor', [ $this->router, 'wp_ajax_send_attachment_to_editor' ] ) );
		$this->assertEquals( 10, has_action( 'init', [ $this->router, 'register_taxonomy' ] ) );
		$this->assertEquals( 10, has_action( 'init', [ $this->router, 'register_meta' ] ) );
	}
}
