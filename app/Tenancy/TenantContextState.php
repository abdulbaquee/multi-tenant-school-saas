<?php

namespace App\Tenancy;

enum TenantContextState: string
{
    case Unresolved = 'unresolved';
    case Tenant = 'tenant';
    case Platform = 'platform';
}
