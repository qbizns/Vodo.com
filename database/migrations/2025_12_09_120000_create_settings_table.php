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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('group')->default('general')->index();
            $table->timestamps();
        });

        // Insert default settings
        $defaults = [
            ['key' => 'app_name', 'value' => json_encode('VODO'), 'group' => 'general'],
            ['key' => 'company_name', 'value' => json_encode(''), 'group' => 'general'],
            ['key' => 'company_email', 'value' => json_encode(''), 'group' => 'general'],
            ['key' => 'company_phone', 'value' => json_encode(''), 'group' => 'general'],
            ['key' => 'timezone', 'value' => json_encode('UTC'), 'group' => 'general'],
            ['key' => 'date_format', 'value' => json_encode('Y-m-d'), 'group' => 'general'],
            ['key' => 'time_format', 'value' => json_encode('H:i'), 'group' => 'general'],
            ['key' => 'session_lifetime', 'value' => json_encode(120), 'group' => 'general'],
            ['key' => 'password_min_length', 'value' => json_encode(8), 'group' => 'general'],
            ['key' => 'require_2fa', 'value' => json_encode(false), 'group' => 'general'],
            ['key' => 'email_notifications', 'value' => json_encode(true), 'group' => 'general'],
            ['key' => 'notification_email', 'value' => json_encode(''), 'group' => 'general'],
        ];

        foreach ($defaults as $setting) {
            $setting['created_at'] = now();
            $setting['updated_at'] = now();
            \Illuminate\Support\Facades\DB::table('settings')->insert($setting);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
