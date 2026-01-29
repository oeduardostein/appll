<?php

namespace Tests\Unit;

use Tests\TestCase;

class FormatPlateAsMercosulTest extends TestCase
{
    public function test_returns_null_when_no_plate_given(): void
    {
        $this->assertNull(format_plate_as_mercosul(null));
    }

    public function test_converts_old_plate_to_mercosul(): void
    {
        $this->assertSame('ABC1B34', format_plate_as_mercosul('ABC-1134'));
        $this->assertSame('XYZ4G89', format_plate_as_mercosul('xyz-4689'));
    }

    public function test_leaves_valid_mercosul_plate_untouched(): void
    {
        $this->assertSame('ABC1B34', format_plate_as_mercosul('ABC1B34'));
    }

    public function test_strips_non_alphanumeric_before_processing(): void
    {
        $this->assertSame('ABC1B34', format_plate_as_mercosul('a b c 1 1 3 4'));
    }

    public function test_returns_empty_string_when_only_non_alphanumeric_provided(): void
    {
        $this->assertSame('', format_plate_as_mercosul('---'));
    }
}
