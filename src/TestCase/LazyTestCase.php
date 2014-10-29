<?php

namespace PrestaShop\TestCase;

use \PrestaShop\Shop;

/**
* A Lazy TestCase differs from a TestCase in that
* you don't get a new shop after each test.
*/

class LazyTestCase extends TestCase
{
    public function setUp()
    {
        $this->shop = static::getShop();
    }

    public function tearDown()
    {
        // Do nothing
    }
}
