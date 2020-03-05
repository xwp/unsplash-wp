<?php
/**
 * Tests for Utils class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

/**
 * Tests for the Utils class.
 *
 * @coversDefaultClass \XWP\Unsplash\Utils
 */
class TestUtils extends \WP_UnitTestCase {
	/**
	 * Utils instance.
	 *
	 * @var Utils
	 */
	public $utils;

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		global $unsplash;
		$this->utils = $unsplash['utils'];
	}


	/**
	 * /**
	 * Test get_original_url_with_size.
	 *
	 * @covers ::get_original_url_with_size()
	 * @dataProvider data_various_params
	 *
	 * @param string $url Original URL of unsplash asset.
	 * @param int    $width Width of image.
	 * @param int    $height Height of image.
	 * @param array  $attr Other attributes to be passed to the URL.
	 * @param string $expected Expected value.
	 */
	public function test_get_original_url_with_size( $url, $width, $height, $attr, $expected ) {
		$this->assertSame( $this->utils->get_original_url_with_size( $url, $width, $height, $attr ), $expected );
	}

	/**
	 * Data provider for test_get_original_url_with_size.
	 *
	 * @return array
	 */
	public function data_various_params() {
		return [
			[ 'http://www.example.com/test.jpg', 222, 444, [], 'http://www.example.com/test.jpg?w=222&h=444' ],
			[ 'http://www.example.com/test.jpg', 100, 100, [], 'http://www.example.com/test.jpg?w=100&h=100' ],
			[ 'http://www.example.com/test.jpg', -1, -1, [], 'http://www.example.com/test.jpg?w=1&h=1' ],
			[ 'http://www.example.com/test.jpg', 'invalid', 'invalid', [], 'http://www.example.com/test.jpg?w=0&h=0' ],
			[ 'http://www.example.com/test.jpg', 100, 100, [ 'crop' => true ], 'http://www.example.com/test.jpg?w=100&h=100&crop=1' ],
			[ 'http://www.example.com/test.jpg?crop=1', 100, 100, [], 'http://www.example.com/test.jpg?crop=1&w=100&h=100' ],
		];
	}

}
