<?php
/**
 * Test_Unsplash
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Class Test_Unsplash
 *
 * @package Unsplash
 */
class Test_Unsplash extends \WP_UnitTestCase {

	/**
	 * Test _unsplash_php_version_error().
	 *
	 * @see _unsplash_php_version_error()
	 */
	public function test_unsplash_php_version_error() {
		ob_start();
		_unsplash_php_version_error();
		$buffer = ob_get_clean();
		$this->assertContains( '<div class="error">', $buffer );
	}

	/**
	 * Test _unsplash_php_version_text().
	 *
	 * @see _unsplash_php_version_text()
	 */
	public function test_unsplash_php_version_text() {
		$this->assertContains( 'Unsplash plugin error:', _unsplash_php_version_text() );
	}
}
