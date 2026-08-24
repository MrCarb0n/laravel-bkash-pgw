<?php

namespace Tiash\LaravelBkash\Tests\Unit;

use Tiash\LaravelBkash\Support\Amount;
use PHPUnit\Framework\TestCase;

class AmountTest extends TestCase
{
    /** The float "2300.0" from production logs must become "2300.00". */
    public function test_formats_float_without_trailing_zero_loss(): void
    {
        $this->assertSame('2300.00', Amount::format(2300.0));
    }

    public function test_formats_integers_strings_and_precision(): void
    {
        $this->assertSame('100.00', Amount::format(100));
        $this->assertSame('99.50', Amount::format('99.5'));
        $this->assertSame('0.01', Amount::format(0.01));
    }

    public function test_rejects_non_positive_amounts(): void
    {
        $this->assertFalse(Amount::validate(0));
        $this->assertFalse(Amount::validate(-5));
        $this->assertTrue(Amount::validate(1));
    }
}