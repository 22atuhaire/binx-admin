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
        Schema::table('waste_posts', function (Blueprint $table) {
            $table->foreignId('donor_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('collector_id')->nullable()->after('donor_id')->constrained('users')->nullOnDelete();
            $table->json('waste_types')->nullable()->after('category');
            $table->text('notes')->nullable()->after('description');
            $table->string('pickup_time')->nullable()->after('notes');
            $table->string('address')->nullable()->after('location');
            $table->text('instructions')->nullable()->after('address');
            $table->json('photos')->nullable()->after('image_path');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE waste_posts MODIFY COLUMN status ENUM('pending', 'open', 'taken', 'completed', 'cancelled', 'expired') NOT NULL DEFAULT 'open'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE waste_posts SET status = 'open' WHERE status = 'pending'");
            DB::statement("ALTER TABLE waste_posts MODIFY COLUMN status ENUM('open', 'taken', 'completed', 'cancelled', 'expired') NOT NULL DEFAULT 'open'");
        }

        Schema::table('waste_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collector_id');
            $table->dropConstrainedForeignId('donor_id');
            $table->dropColumn([
                'waste_types',
                'notes',
                'pickup_time',
                'address',
                'instructions',
                'photos',
            ]);
        });
    }
};
