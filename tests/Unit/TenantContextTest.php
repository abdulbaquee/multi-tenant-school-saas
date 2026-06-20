<?php

namespace Tests\Unit;

use App\Tenancy\TenantContext;
use App\Tenancy\TenantContextState;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

    public function test_temporary_tenant_execution_restores_every_previous_state(): void
    {
        $context = new TenantContext;

        $result = $context->runAsTenant(7, function () use ($context): string {
            $this->assertSame(7, $context->tenantId());

            return 'completed';
        });

        $this->assertSame('completed', $result);
        $this->assertTrue($context->isUnresolved());

        $context->setPlatform();
        $context->runAsTenant(8, fn (): int => $context->tenantId());
        $this->assertTrue($context->isPlatform());

        $context->setTenant(9);
        $context->runAsTenant(10, fn (): int => $context->tenantId());
        $this->assertTrue($context->isTenant());
        $this->assertSame(9, $context->tenantId());
    }

    public function test_temporary_tenant_execution_restores_context_after_an_exception(): void
    {
        $context = new TenantContext;
        $context->setPlatform();

        try {
            $context->runAsTenant(7, function (): never {
                throw new RuntimeException('Temporary tenant failure.');
            });
            $this->fail('The callback exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Temporary tenant failure.', $exception->getMessage());
        }

        $this->assertTrue($context->isPlatform());
        $this->assertNull($context->schoolId());
    }

    public function test_temporary_platform_execution_restores_context_and_exceptions(): void
    {
        $context = new TenantContext;
        $context->setTenant(42);

        $result = $context->runAsPlatform(function () use ($context): string {
            $this->assertTrue($context->isPlatform());

            return 'completed';
        });

        $this->assertSame('completed', $result);
        $this->assertSame(42, $context->tenantId());

        try {
            $context->runAsPlatform(function (): never {
                throw new RuntimeException('Temporary platform failure.');
            });
            $this->fail('The callback exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Temporary platform failure.', $exception->getMessage());
        }

        $this->assertSame(42, $context->tenantId());
    }
}
