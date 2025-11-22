<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Validator\UuidValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UuidValidatorTest extends TestCase
{
    private UuidValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UuidValidator();
    }

    public function testValidateReturnsTrueForValidUuid(): void
    {
        $validUuid = Uuid::v4()->toRfc4122();
        $this->assertTrue($this->validator->validate($validUuid));
    }

    public function testValidateReturnsFalseForInvalidUuid(): void
    {
        $this->assertFalse($this->validator->validate('not-a-uuid'));
    }

    public function testValidateReturnsFalseForEmptyString(): void
    {
        $this->assertFalse($this->validator->validate(''));
    }
}
