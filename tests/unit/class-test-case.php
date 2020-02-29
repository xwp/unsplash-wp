<?php
/**
 * Tests Case class.
 *
 * @package Unsplash
 */

namespace Unsplash;

use Mockery;
use WP_Mock;

/**
 * Tests for the Router class.
 */
class Test_Case extends WP_Mock\Tools\TestCase {

	use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

}
