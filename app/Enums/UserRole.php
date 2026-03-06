<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case BranchManager = 'branch_manager';
    case StoreManager = 'store_manager';
}
