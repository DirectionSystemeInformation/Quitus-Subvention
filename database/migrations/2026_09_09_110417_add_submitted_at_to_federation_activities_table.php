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
        Schema::table('federation_activities', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('status');
        });

        // Backfill: activities already sitting as "soumis" didn't record a
        // submission date before this column existed — use their creation
        // date as the best available approximation.
        DB::table('federation_activities')
            ->where('status', 'soumis')
            ->whereNull('submitted_at')
            ->update(['submitted_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('federation_activities', function (Blueprint $table) {
            $table->dropColumn('submitted_at');
        });
    }
};
