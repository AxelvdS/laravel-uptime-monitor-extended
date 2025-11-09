<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update enum values in monitors_logs table
        // MySQL doesn't support direct enum modification, so we need to alter the column
        if (Schema::hasTable('monitors_logs')) {
            // For MySQL/MariaDB, we need to modify the enum
            DB::statement("ALTER TABLE `monitors_logs` MODIFY COLUMN `status` ENUM('up', 'down', 'ssl_issue', 'ssl_expiring') DEFAULT 'up'");
            
            // Update existing records
            DB::table('monitors_logs')
                ->where('status', 'ssl_expired')
                ->update(['status' => 'ssl_issue']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('monitors_logs')) {
            // Revert existing records
            DB::table('monitors_logs')
                ->where('status', 'ssl_issue')
                ->update(['status' => 'ssl_expired']);
            
            // Revert enum
            DB::statement("ALTER TABLE `monitors_logs` MODIFY COLUMN `status` ENUM('up', 'down', 'ssl_expired', 'ssl_expiring') DEFAULT 'up'");
        }
    }
};

