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
        // Check if the monitors table exists before trying to create foreign key
        if (!Schema::hasTable('monitors')) {
            throw new \RuntimeException(
                'The monitors table does not exist. Please run Spatie\'s Laravel Uptime Monitor migrations first.'
            );
        }

        Schema::create('monitors_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('monitor_id'); // Match Spatie's monitors.id type (int unsigned)
            $table->enum('status', ['up', 'down', 'ssl_issue', 'ssl_expiring'])->default('up');
            $table->string('response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Store additional data like ping time, HTTP status, etc.
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['monitor_id', 'checked_at']);
            $table->index('status');
            $table->index('checked_at');
        });

        // Create foreign key constraint separately to ensure proper type matching
        Schema::table('monitors_logs', function (Blueprint $table) {
            $table->foreign('monitor_id')
                ->references('id')
                ->on('monitors')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors_logs', function (Blueprint $table) {
            $table->dropForeign(['monitor_id']);
        });
        
        Schema::dropIfExists('monitors_logs');
    }
};

