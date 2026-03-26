<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('parent_po_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', [
                'draft',
                'pending_manager',
                'pending_director',
                'pending_finance',
                'approved',
                'rejected',
                'cancelled'
            ])->default('draft');
            $table->unsignedInteger('revision_number')->default(1);
            $table->boolean('is_latest')->default(true);
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'department_id']);
            $table->index(['created_by', 'status']);
            $table->index('is_latest');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
