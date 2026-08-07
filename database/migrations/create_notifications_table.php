<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the notifications table for storing in-app/database
     * notifications delivered through the In-App channel.
     *
     * The table uses polymorphic notifiable columns so any
     * application model (User, Parent, Teacher, Student, etc.)
     * can receive notifications.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('notifiable_type');
            $table->string('notifiable_id');
            $table->index(['notifiable_type', 'notifiable_id']);

            $table->string('title');
            $table->text('body')->nullable();

            $table->json('data')->nullable();

            $table->string('channel')->nullable();
            $table->string('provider')->nullable();

            $table->timestamp('read_at')->nullable();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

