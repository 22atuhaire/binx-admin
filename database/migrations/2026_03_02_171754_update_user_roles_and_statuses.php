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
        Schema::table('users', function (Blueprint $table) {
            // Update role ENUM: 'user', 'collector' -> 'admin', 'donor', 'collector'
            DB::statement("ALTER TABLE users CHANGE COLUMN role role ENUM('admin', 'donor', 'collector') NOT NULL DEFAULT 'donor'");
            
            // Update status ENUM: 'active', 'inactive', 'suspended' -> 'pending', 'active', 'blocked'
            DB::statement("ALTER TABLE users CHANGE COLUMN status status ENUM('pending', 'active', 'blocked') NOT NULL DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rollback role ENUM
            DB::statement("ALTER TABLE users CHANGE COLUMN role role ENUM('user', 'collector') NOT NULL DEFAULT 'user'");
            
            // Rollback status ENUM
            DB::statement("ALTER TABLE users CHANGE COLUMN status status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active'");
        });
    }
};
