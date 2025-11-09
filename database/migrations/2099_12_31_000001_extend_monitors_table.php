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
        // Check if the monitors table exists before trying to alter it
        if (!Schema::hasTable('monitors')) {
            throw new \RuntimeException(
                'The monitors table does not exist. Please run Spatie\'s Laravel Uptime Monitor migrations first.'
            );
        }

        Schema::table('monitors', function (Blueprint $table) {
            // Add monitor type (http, https, ping, tcp)
            if (!Schema::hasColumn('monitors', 'monitor_type')) {
                $table->string('monitor_type')->default('https')->after('url');
            }

            // Add per-monitor frequency in minutes
            if (!Schema::hasColumn('monitors', 'frequency_minutes')) {
                $table->integer('frequency_minutes')->nullable()->after('monitor_type');
            }

            // Add active/inactive toggle
            if (!Schema::hasColumn('monitors', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('frequency_minutes');
            }

            // Add last check timestamp for frequency tracking
            if (!Schema::hasColumn('monitors', 'last_check_at')) {
                $table->timestamp('last_check_at')->nullable()->after('is_active');
            }

            // Add ping-specific fields
            if (!Schema::hasColumn('monitors', 'ping_timeout')) {
                $table->integer('ping_timeout')->nullable()->after('last_check_at');
            }

            // Add name field for identifying the monitor
            if (!Schema::hasColumn('monitors', 'name')) {
                $table->string('name')->nullable()->after('ping_timeout');
            }

            // Add description field
            if (!Schema::hasColumn('monitors', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            // Add notes/description field
            if (!Schema::hasColumn('monitors', 'notes')) {
                $table->text('notes')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn([
                'monitor_type',
                'frequency_minutes',
                'is_active',
                'last_check_at',
                'ping_timeout',
                'name',
                'description',
                'notes',
            ]);
        });
    }
};

