<?php
/**
 * Tests for Router class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

/**
 * Tests for the Router class.
 *
 * @coversDefaultClass \XWP\Unsplash\Router
 */
class TestRouter extends \WP_UnitTestCase {

	/**
	 * Router instance.
	 *
	 * @var Router
	 */
	public $router;

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		global $unsplash;
		$this->router = $unsplash['router'];
	}

	/**
	 * Test constructor.
	 *
	 * @covers ::__construct()
	 */
	public function test_construct() {
		$this->assertEquals( true, did_action( 'plugins_loaded', [ $this->router, 'init' ] ) );
	}

	/**
	 * Test init.
	 *
	 * @covers ::init()
	 */
	public function test_init() {
		$this->assertEquals( 10, has_action( 'admin_enqueue_scripts', [ $this->router, 'enqueue_scripts' ] ) );
		$this->assertEquals( 10, has_action( 'enqueue_block_editor_assets', [ $this->router, 'enqueue_editor_assets' ] ) );
	}
}
