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
        Schema::create('federation_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('canvas_sous_axe_id')->nullable()->constrained('canvas_sous_axes')->nullOnDelete();
            $table->string('axe')->nullable();
            $table->string('axe_label')->nullable();
            $table->string('sous_axe_code')->nullable();
            $table->string('sous_axe_label')->nullable();
            $table->unsignedSmallInteger('year');
            $table->string('designation')->nullable();
            $table->decimal('montant', 14, 2)->nullable();
            $table->string('contribution_partenaires')->nullable();
            $table->date('date')->nullable();
            $table->string('observations')->nullable();
            $table->enum('status', ['brouillon', 'soumis', 'valide', 'rejete'])->default('brouillon');
            $table->string('rejection_reason')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('budget_line_id')->nullable()->constrained('budget_lines')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('federation_activities');
    }
};
