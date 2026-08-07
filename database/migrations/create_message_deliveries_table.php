<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the message_deliveries table.
 *
 * This migration creates the storage for all delivery history
 * records. Each row represents a single message delivered to
 * a single recipient through a specific provider.
 *
 * The table is designed to support:
 * - Delivery history queries
 * - Status tracking (queued -> sent/delivered/failed)
 * - Provider response storage
 * - Multi-tenant isolation (tenant_id, school_id)
 *
 * Who uses it:
 * - DatabaseDeliveryRecorder creates and updates records
 * - Administration interfaces query delivery history
 * - Diagnostics and reporting tools
 *
 * What it should NOT do:
 * - NOT store application logs (handled by AppLogger)
 * - NOT store provider credentials or settings
 * - NOT replace the application's own logging tables
 */
return new class extends Migration
{
    /**
     * The database connection name.
     *
     * @var string|null
     */
    protected $connection = null;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection($this->connection)->create(
            'message_deliveries',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();

                $table->string('tenant_id')->nullable();
                $table->string('school_id')->nullable();

                $table->string('channel');
                $table->string('provider');
                $table->string('recipient');
                $table->string('status');

                $table->string('provider_message_id')->nullable();
                $table->string('subject')->nullable();
                $table->json('metadata')->nullable();
                $table->text('error')->nullable();

                $table->datetime('queued_at')->nullable();
                $table->datetime('sent_at')->nullable();
                $table->datetime('delivered_at')->nullable();

                $table->timestamps();

                $table->index('tenant_id');
                $table->index('school_id');
                $table->index('channel');
                $table->index('status');
                $table->index('created_at');
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('message_deliveries');
    }
};
