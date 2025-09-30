<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Livewire\Features\SupportTesting\Testable;

/**
 * @mixin MakesHttpRequests
 * @mixin Testable
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
