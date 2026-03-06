<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Sale = 'sale';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case Adjustment = 'adjustment';
    case Procurement = 'procurement';
}
