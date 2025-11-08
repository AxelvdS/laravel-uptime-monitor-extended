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
        Schema::create('monitor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['up', 'down', 'ssl_expired', 'ssl_expiring'])->default('up');
            $table->string('response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Store additional data like ping time, HTTP status, etc.
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['monitor_id', 'checked_at']);
            $table->index('status');
            $table->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_logs');
    }
};

