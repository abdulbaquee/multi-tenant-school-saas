<?php

namespace Tests\Unit;

use App\Tenancy\TenantContext;
use App\Tenancy\TenantContextState;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

class TenantContextTest extends TestCase
{
    public function test_context_starts_unresolved_and_can_be_cleared(): void
    {
        $context = new TenantContext;

        $this->assertSame(TenantContextState::Unresolved, $context->state());
        $this->assertTrue($context->isUnresolved());
        $this->assertNull($context->schoolId());

        $context->setTenant(12);
        $context->clear();

        $this->assertTrue($context->isUnresolved());
        $this->assertNull($context->schoolId());
    }

    public function test_tenant_state_requires_a_positive_school_id(): void
    {
        $context = new TenantContext;

        $context->setTenant(42);

        $this->assertSame(TenantContextState::Tenant, $context->state());
        $this->assertTrue($context->isTenant());
        $this->assertSame(42, $context->tenantId());

        try {
            $context->setTenant(0);
            $this->fail('An invalid tenant id was accepted.');
        } catch (InvalidArgumentException) {
            $this->assertTrue($context->isUnresolved());
            $this->assertNull($context->schoolId());
        }
    }

    public function test_platform_state_is_explicit_and_has_no_school_id(): void
    {
        $context = new TenantContext;

        $context->setTenant(42);
        $context->setPlatform();

        $this->assertSame(TenantContextState::Platform, $context->state());
        $this->assertTrue($context->isPlatform());
        $this->assertNull($context->schoolId());
    }

    public function test_tenant_id_cannot_be_read_outside_tenant_state(): void
    {
        $context = new TenantContext;

        $this->expectException(LogicException::class);
        $context->tenantId();
    }
}
