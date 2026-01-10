<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_vendor_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('commerce_vendors')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('commerce_customers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('commerce_orders')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('commerce_vendor_messages')->cascadeOnDelete();

            // Message Content
            $table->string('subject');
            $table->text('body');
            $table->json('attachments')->nullable(); // File uploads

            // Sender Information
            $table->enum('sender_type', ['customer', 'vendor', 'admin'])->default('customer');
            $table->unsignedBigInteger('sender_id'); // Customer ID, Vendor ID, or User ID
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();

            // Read Status
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            // Priority & Status
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');

            // Category/Type
            $table->enum('category', ['general', 'order', 'product', 'shipping', 'return', 'complaint', 'other'])->default('general');

            // Internal Notes (only visible to vendor/admin)
            $table->text('internal_notes')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['vendor_id', 'status']);
            $table->index(['customer_id']);
            $table->index(['order_id']);
            $table->index(['parent_id']);
            $table->index(['is_read']);
            $table->index(['sender_type', 'sender_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_vendor_messages');
    }
};
