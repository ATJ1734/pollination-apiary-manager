<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_required_field_missing_fails(): void
    {
        $v = new Validator(['name' => ''], ['name' => 'required']);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('name', $v->errors());
    }

    public function test_required_field_present_passes(): void
    {
        $v = new Validator(['name' => 'Clearwater Bend'], ['name' => 'required']);

        $this->assertTrue($v->passes());
    }

    public function test_nullable_skips_other_rules_when_empty(): void
    {
        $v = new Validator(['latitude' => ''], ['latitude' => 'nullable|numeric|between:-90,90']);

        $this->assertTrue($v->passes());
    }

    public function test_email_rule_rejects_invalid_address(): void
    {
        $v = new Validator(['email' => 'not-an-email'], ['email' => 'required|email']);

        $this->assertTrue($v->fails());
    }

    public function test_email_rule_accepts_valid_address(): void
    {
        $v = new Validator(['email' => 'grower@example.com'], ['email' => 'required|email']);

        $this->assertTrue($v->passes());
    }

    public function test_date_rule_rejects_malformed_date(): void
    {
        $v = new Validator(['inspected_on' => '2026-13-40'], ['inspected_on' => 'required|date']);

        $this->assertTrue($v->fails());
    }

    public function test_date_rule_accepts_valid_date(): void
    {
        $v = new Validator(['inspected_on' => '2026-11-05'], ['inspected_on' => 'required|date']);

        $this->assertTrue($v->passes());
    }

    public function test_between_rule_enforces_range(): void
    {
        $v = new Validator(['temperament' => 7], ['temperament' => 'required|integer|between:1,5']);

        $this->assertTrue($v->fails());
    }

    public function test_max_rule_enforces_string_length(): void
    {
        $v = new Validator(['name' => str_repeat('x', 121)], ['name' => 'required|max:120']);

        $this->assertTrue($v->fails());
    }

    public function test_in_rule_restricts_to_allowed_values(): void
    {
        $v = new Validator(['status' => 'retired'], ['status' => 'required|in:active,queenless,dead,sold,combined']);

        $this->assertTrue($v->fails());
    }
}
