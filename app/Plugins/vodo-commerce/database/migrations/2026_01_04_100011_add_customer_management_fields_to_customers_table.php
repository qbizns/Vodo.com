<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commerce_customers', function (Blueprint $table) {
            $table->json('group_ids')->nullable()->after('meta');
            $table->boolean('is_banned')->default(false)->after('group_ids');
            $table->timestamp('banned_at')->nullable()->after('is_banned');
            $table->text('ban_reason')->nullable()->after('banned_at');
        });
    }

    public function down(): void
    {
        Schema::table('commerce_customers', function (Blueprint $table) {
            $table->dropColumn(['group_ids', 'is_banned', 'banned_at', 'ban_reason']);
        });
    }
};
