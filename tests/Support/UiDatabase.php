<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class UiDatabase
{
    // A portable test schema avoids the historical MySQL-only role migrations.
    public static function create(): void
    {
        $database = str_replace('\\', '/', DB::connection()->getDatabaseName());
        $temporaryPrefix = rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/').'/quitus-ui-';
        if (DB::getDriverName() !== 'sqlite' || ($database !== ':memory:' && ! str_starts_with($database, $temporaryPrefix))) {
            throw new \RuntimeException('UI fixtures require an isolated SQLite database.');
        }
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('federation');
            $table->string('status')->default('active');
            $table->string('federation_name')->nullable();
            $table->string('arrete_numero')->nullable();
            $table->date('arrete_date')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedSmallInteger('year');
            $table->enum('status', ['soumis', 'valide', 'rejete'])->default('soumis');
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_09_08_000001_allow_draft_status_on_reports_table.php'))->up();
        foreach ([
            '2026_07_21_120631_create_canvas_axes_table.php',
            '2026_07_21_120631_create_canvas_sous_axes_table.php',
            '2026_07_13_205820_create_budget_lines_table.php',
            '2026_07_27_220945_create_campaigns_table.php',
            '2026_07_27_220946_create_campaign_allocations_table.php',
            '2026_07_22_094249_create_activity_logs_table.php',
            '2026_09_07_133908_create_federation_activities_table.php',
            '2026_09_07_133909_create_federation_activity_documents_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->string('axe_label')->nullable();
            $table->foreignId('canvas_sous_axe_id')->nullable()->constrained('canvas_sous_axes')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('federation_activities')->nullOnDelete();
        });
    }
}
