<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name', 100)->index('idx_fee_categories_name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active')->index('idx_fee_categories_status');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index('idx_fee_categories_deleted_at');

            $table->index('school_id', 'idx_fee_categories_school_id');
            $table->unique(['school_id', 'name'], 'uq_fee_categories_school_name');
            $table->foreign('school_id', 'fk_fee_categories_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
        });

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('fee_category_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('class_id');
            $table->decimal('amount', 10, 2)->default(0);
            $table->date('due_date')->nullable()->index('idx_fee_structures_due_date');
            $table->string('frequency', 30)->default('one_time')->index('idx_fee_structures_frequency');
            $table->string('status', 20)->default('active')->index('idx_fee_structures_status');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index('idx_fee_structures_deleted_at');

            $table->index('school_id', 'idx_fee_structures_school_id');
            $table->index('fee_category_id', 'idx_fee_structures_fee_category_id');
            $table->index('academic_year_id', 'idx_fee_structures_academic_year_id');
            $table->index('class_id', 'idx_fee_structures_class_id');
            $table->unique(
                ['school_id', 'fee_category_id', 'academic_year_id', 'class_id'],
                'uq_fee_structures_scope',
            );
            $table->foreign('school_id', 'fk_fee_structures_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('fee_category_id', 'fk_fee_structures_fee_category_id')
                ->references('id')
                ->on('fee_categories')
                ->restrictOnDelete();
            $table->foreign('academic_year_id', 'fk_fee_structures_academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->restrictOnDelete();
            $table->foreign('class_id', 'fk_fee_structures_class_id')
                ->references('id')
                ->on('classes')
                ->restrictOnDelete();
        });

        Schema::create('student_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('fee_structure_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('payable_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance_amount', 10, 2)->default(0)->index('idx_student_fees_balance_amount');
            $table->date('due_date')->nullable()->index('idx_student_fees_due_date');
            $table->string('status', 20)->default('pending')->index('idx_student_fees_status');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index('idx_student_fees_deleted_at');

            $table->index('school_id', 'idx_student_fees_school_id');
            $table->index('student_id', 'idx_student_fees_student_id');
            $table->index('fee_structure_id', 'idx_student_fees_fee_structure_id');
            $table->index('academic_year_id', 'idx_student_fees_academic_year_id');
            $table->unique(['student_id', 'fee_structure_id'], 'uq_student_fees_student_structure');
            $table->foreign('school_id', 'fk_student_fees_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('student_id', 'fk_student_fees_student_id')
                ->references('id')
                ->on('students')
                ->restrictOnDelete();
            $table->foreign('fee_structure_id', 'fk_student_fees_fee_structure_id')
                ->references('id')
                ->on('fee_structures')
                ->restrictOnDelete();
            $table->foreign('academic_year_id', 'fk_student_fees_academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->restrictOnDelete();
        });

        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_fee_id');
            $table->unsignedBigInteger('student_id');
            $table->string('receipt_no', 80)->index('idx_fee_payments_receipt_no');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->date('payment_date')->index('idx_fee_payments_payment_date');
            $table->string('payment_mode', 30)->default('cash')->index('idx_fee_payments_payment_mode');
            $table->string('status', 20)->default('completed')->index('idx_fee_payments_status');
            $table->unsignedBigInteger('received_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('school_id', 'idx_fee_payments_school_id');
            $table->index('student_fee_id', 'idx_fee_payments_student_fee_id');
            $table->index('student_id', 'idx_fee_payments_student_id');
            $table->index('received_by', 'idx_fee_payments_received_by');
            $table->unique(['school_id', 'receipt_no'], 'uq_fee_payments_school_receipt_no');
            $table->foreign('school_id', 'fk_fee_payments_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('student_fee_id', 'fk_fee_payments_student_fee_id')
                ->references('id')
                ->on('student_fees')
                ->restrictOnDelete();
            $table->foreign('student_id', 'fk_fee_payments_student_id')
                ->references('id')
                ->on('students')
                ->restrictOnDelete();
            $table->foreign('received_by', 'fk_fee_payments_received_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('fee_payment_id');
            $table->string('transaction_no', 100)->index('idx_payment_transactions_transaction_no');
            $table->string('gateway_reference', 150)->nullable()->index('idx_payment_transactions_gateway_reference');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('payment_mode', 30)->default('cash')->index('idx_payment_transactions_payment_mode');
            $table->string('status', 20)->default('completed')->index('idx_payment_transactions_status');
            $table->timestamp('processed_at')->nullable()->index('idx_payment_transactions_processed_at');
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index('school_id', 'idx_payment_transactions_school_id');
            $table->index('fee_payment_id', 'idx_payment_transactions_fee_payment_id');
            $table->unique(
                ['school_id', 'transaction_no'],
                'uq_payment_transactions_school_transaction_no',
            );
            $table->foreign('school_id', 'fk_payment_transactions_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('fee_payment_id', 'fk_payment_transactions_fee_payment_id')
                ->references('id')
                ->on('fee_payments')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('student_fees');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_categories');
    }
};
