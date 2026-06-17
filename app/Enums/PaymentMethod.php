<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case MobileMoney = 'mobile_money';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Bank => 'Cash @ Bank',
            self::MobileMoney => 'Mobile Money',
            self::Credit => 'Outstanding Debt (Credit)',
        };
    }

    /**
     * Does this settlement physically enter the cash drawer?
     */
    public function entersDrawer(): bool
    {
        return $this === self::Cash;
    }

    /**
     * Is this a credit sale (money not yet collected)?
     */
    public function isCredit(): bool
    {
        return $this === self::Credit;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $method) => ['value' => $method->value, 'label' => $method->label()],
            self::cases(),
        );
    }
}
