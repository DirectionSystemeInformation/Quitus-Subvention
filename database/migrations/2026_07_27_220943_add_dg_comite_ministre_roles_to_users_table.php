<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('federation', 'dshn', 'admin', 'dg', 'comite_arbitrage', 'ministre') NOT NULL DEFAULT 'federation'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'dshn' WHERE role IN ('dg', 'comite_arbitrage', 'ministre')");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('federation', 'dshn', 'admin') NOT NULL DEFAULT 'federation'");
    }
};
