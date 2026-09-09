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
        DB::statement('ALTER TABLE federation_activities CHANGE date date_debut DATE NULL');
        DB::statement('ALTER TABLE federation_activities ADD date_fin DATE NULL AFTER date_debut');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE federation_activities DROP COLUMN date_fin');
        DB::statement('ALTER TABLE federation_activities CHANGE date_debut date DATE NULL');
    }
};
