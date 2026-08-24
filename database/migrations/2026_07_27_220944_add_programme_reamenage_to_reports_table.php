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
        DB::statement("ALTER TABLE reports MODIFY COLUMN type ENUM('rapport_activite', 'programme_budgetise', 'programme_reamenage') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DELETE FROM reports WHERE type = 'programme_reamenage'");
        DB::statement("ALTER TABLE reports MODIFY COLUMN type ENUM('rapport_activite', 'programme_budgetise') NOT NULL");
    }
};
