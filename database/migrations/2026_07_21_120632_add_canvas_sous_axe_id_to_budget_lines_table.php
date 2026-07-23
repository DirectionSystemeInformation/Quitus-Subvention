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
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->foreignId('canvas_sous_axe_id')->nullable()->after('report_id')
                ->constrained()->nullOnDelete();
            $table->string('axe_label')->nullable()->after('axe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('canvas_sous_axe_id');
            $table->dropColumn('axe_label');
        });
    }
};
