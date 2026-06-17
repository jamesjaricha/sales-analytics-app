<?php

namespace Tests\Unit;

use App\Enums\PaymentMethod;
use PHPUnit\Framework\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_each_case_has_a_human_label(): void
    {
        $this->assertSame('Cash', PaymentMethod::Cash->label());
        $this->assertSame('Cash @ Bank', PaymentMethod::Bank->label());
        $this->assertSame('Mobile Money', PaymentMethod::MobileMoney->label());
        $this->assertSame('Outstanding Debt (Credit)', PaymentMethod::Credit->label());
    }

    public function test_only_cash_enters_the_drawer(): void
    {
        $this->assertTrue(PaymentMethod::Cash->entersDrawer());
        $this->assertFalse(PaymentMethod::Bank->entersDrawer());
        $this->assertFalse(PaymentMethod::MobileMoney->entersDrawer());
        $this->assertFalse(PaymentMethod::Credit->entersDrawer());
    }

    public function test_credit_is_flagged_as_credit(): void
    {
        $this->assertTrue(PaymentMethod::Credit->isCredit());
        $this->assertFalse(PaymentMethod::Cash->isCredit());
    }

    public function test_options_returns_all_four_value_label_pairs(): void
    {
        $options = PaymentMethod::options();

        $this->assertCount(4, $options);
        $this->assertSame(['value' => 'cash', 'label' => 'Cash'], $options[0]);
        $this->assertSame(['value' => 'credit', 'label' => 'Outstanding Debt (Credit)'], $options[3]);
    }
}
