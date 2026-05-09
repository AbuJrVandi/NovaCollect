<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->index('created_at');
        });

        Schema::table('forms', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('submissions', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('submissions', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('forms', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
        });
    }
};
