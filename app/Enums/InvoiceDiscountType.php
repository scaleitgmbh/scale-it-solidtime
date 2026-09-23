<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum InvoiceDiscountType: string
{
    use LaravelEnumHelper;

    case Percentage = 'percentage';
    case Fixed = 'fixed';
}
