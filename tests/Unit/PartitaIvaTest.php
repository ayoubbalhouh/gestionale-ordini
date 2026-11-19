<?php

namespace Tests\Unit;

use App\Rules\PartitaIva;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PartitaIvaTest extends TestCase
{
    private function fails(string $value): bool
    {
        $failed = false;
        (new PartitaIva)->validate('piva', $value, function () use (&$failed) { $failed = true; });

        return $failed;
    }

    public static function valid(): array
    {
        return [['12345678903'], ['01234567897'], ['06789012348']];
    }

    public static function invalid(): array
    {
        return [['12345678901'], ['1234567890'], ['123456789012'], ['ABCDEFGHILM'], ['']];
    }

    #[DataProvider('valid')]
    public function test_valid_numbers_pass(string $piva): void
    {
        $this->assertFalse($this->fails($piva));
    }

    #[DataProvider('invalid')]
    public function test_invalid_numbers_fail(string $piva): void
    {
        $this->assertTrue($this->fails($piva));
    }
}
