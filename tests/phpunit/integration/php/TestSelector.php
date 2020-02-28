<?php
/**
 * Tests for Selector class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

/**
 * Tests for the Selector class.
 *
 * @coversDefaultClass \XWP\Unsplash\Selector
 */
class TestSelector extends \WP_UnitTestCase {

	/**
	 * Selector instance.
	 *
	 * @var Selector
	 */
	public $selector;

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		global $unsplash;
		$this->selector = $unsplash['selector'];
	}

	/**
	 * Test constructor.
	 *
	 * @covers ::__construct()
	 */
	public function test_construct() {
		$this->assertEquals( true, did_action( 'plugins_loaded', [ $this->selector, 'init' ] ) );
	}

	/**
	 * Test init.
	 *
	 * @covers ::init()
	 */
	public function test_init() {
		$this->assertEquals( 10, has_action( 'wp_enqueue_media', [ $this->selector, 'enqueue_scripts' ] ) );
		$this->assertEquals( 10, has_action( 'enqueue_block_editor_assets', [ $this->selector, 'enqueue_editor_assets' ] ) );
	}
}
