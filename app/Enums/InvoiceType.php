<?php

namespace App\Enums;

enum InvoiceType: string
{
    case New = 'new';
    case Renewal = 'renewal';
}
