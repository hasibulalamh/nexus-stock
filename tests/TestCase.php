<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CSRF verification is a browser concern; feature tests exercise routing,
        // auth and validation. Disable it here so POST/PUT/DELETE requests are
        // not rejected with 419 before reaching the controller under test.
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
