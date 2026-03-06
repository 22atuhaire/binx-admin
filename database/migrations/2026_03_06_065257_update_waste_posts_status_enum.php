<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE waste_posts MODIFY COLUMN status ENUM('open', 'taken', 'completed', 'cancelled', 'expired') NOT NULL DEFAULT 'open'");
        } else {
            // For SQLite (used in testing), we need to recreate the table
            Schema::table('waste_posts', function (Blueprint $table) {
                $table->string('status_temp')->default('open');
            });

            DB::statement('UPDATE waste_posts SET status_temp = status');

            Schema::table('waste_posts', function (Blueprint $table) {
                $table->dropColumn('status');
            });

            Schema::table('waste_posts', function (Blueprint $table) {
                $table->renameColumn('status_temp', 'status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE waste_posts MODIFY COLUMN status ENUM('open', 'taken', 'completed') NOT NULL DEFAULT 'open'");
        } else {
            // For SQLite, the column is already a string, no change needed
            // The values are still valid
        }
    }
};
