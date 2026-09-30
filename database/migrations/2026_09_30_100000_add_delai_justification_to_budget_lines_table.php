<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Date limite de justification d'une activité du programme réaménagé,
     * fixée par la DSHN à la délivrance du quitus et reprise sur celui-ci.
     */
    public function up(): void
    {
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->date('delai_justification')->nullable()->after('date');
        });
    }

    public function down(): void
    {
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->dropColumn('delai_justification');
        });
    }
};
