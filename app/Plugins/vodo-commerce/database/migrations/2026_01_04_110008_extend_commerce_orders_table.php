<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commerce_orders', function (Blueprint $table) {
            $table->text('cancel_reason')->nullable()->after('notes');
            $table->string('cancelled_by_type')->nullable()->after('cancel_reason');
            $table->unsignedBigInteger('cancelled_by_id')->nullable()->after('cancelled_by_type');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by_id');
            $table->decimal('refund_total', 15, 2)->default(0)->after('total');
            $table->boolean('has_refunds')->default(false)->after('refund_total');
            $table->boolean('is_exported')->default(false)->after('has_refunds');
            $table->timestamp('exported_at')->nullable()->after('is_exported');

            $table->index('cancelled_at');
            $table->index('is_exported');
        });
    }

    public function down(): void
    {
        Schema::table('commerce_orders', function (Blueprint $table) {
            $table->dropColumn([
                'cancel_reason',
                'cancelled_by_type',
                'cancelled_by_id',
                'cancelled_at',
                'refund_total',
                'has_refunds',
                'is_exported',
                'exported_at',
            ]);
        });
    }
};
