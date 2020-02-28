<?php
/**
 * Tests for Types class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

/**
 * Tests for the Types class.
 *
 * @coversDefaultClass \XWP\Unsplash\Types
 */
class TestTypes extends \WP_UnitTestCase {

	/**
	 * Types instance.
	 *
	 * @var Types
	 */
	public $types;

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		global $unsplash;
		$this->types = $unsplash['types'];
	}

	/**
	 * Verify initialization.
	 */
	public function test_initialize() {
		$this->assertEquals( true, did_action( 'plugins_loaded', [ $this->types, 'init' ] ) );
	}

	/**
	 * Test init.
	 *
	 * @covers ::init()
	 */
	public function test_init() {
		$this->assertEquals( 10, has_action( 'init', [ $this->types, 'register_taxonomy' ] ) );
		$this->assertEquals( 10, has_action( 'init', [ $this->types, 'register_meta' ] ) );
	}
}
