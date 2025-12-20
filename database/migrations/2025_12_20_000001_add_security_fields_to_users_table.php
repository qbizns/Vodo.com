<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Multi-tenancy
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->after('tenant_id');
            $table->foreignId('branch_id')->nullable()->after('company_id');

            // Account status
            $table->string('status', 20)->default('active')->after('password');
            $table->string('phone', 30)->nullable()->after('status');
            $table->string('avatar', 255)->nullable()->after('phone');
            $table->string('timezone', 50)->default('UTC')->after('avatar');
            $table->string('locale', 10)->default('en')->after('timezone');
            $table->json('settings')->nullable()->after('locale');

            // Two-factor authentication
            $table->boolean('two_factor_enabled')->default(false)->after('settings');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');

            // Login security
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('two_factor_secret');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->timestamp('last_login_at')->nullable()->after('locked_until');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            // Password security
            $table->timestamp('password_changed_at')->nullable()->after('last_login_ip');
            $table->boolean('must_change_password')->default(false)->after('password_changed_at');

            // Soft deletes
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('tenant_id');
            $table->index('company_id');
            $table->index(['status', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);

            $table->dropIndex(['status']);
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['status', 'tenant_id']);

            $table->dropColumn([
                'tenant_id',
                'company_id',
                'branch_id',
                'status',
                'phone',
                'avatar',
                'timezone',
                'locale',
                'settings',
                'two_factor_enabled',
                'two_factor_secret',
                'failed_login_attempts',
                'locked_until',
                'last_login_at',
                'last_login_ip',
                'password_changed_at',
                'must_change_password',
                'deleted_at',
            ]);
        });
    }
};
