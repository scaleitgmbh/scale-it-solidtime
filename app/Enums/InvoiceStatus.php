<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum InvoiceStatus: string
{
    use LaravelEnumHelper;

    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
}
