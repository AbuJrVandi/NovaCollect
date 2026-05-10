<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array INDEX_TABLES = [
        'organizations' => [['created_at']],
        'projects' => [['created_at']],
        'tasks' => [['created_at'], ['assigned_to'], ['created_by']],
        'forms' => [['created_at']],
        'form_sections' => [['form_id']],
        'form_fields' => [['form_id'], ['form_section_id']],
        'submissions' => [['created_at'], ['form_id'], ['user_id']],
        'submission_files' => [['submission_id']],
        'project_members' => [['project_id'], ['user_id']],
        'report_exports' => [['created_at']],
        'scheduled_reports' => [['created_at']],
    ];

    public function up(): void
    {
        // ───────────────────────────────────────────
        // Multi-Tenant Isolation: organization_id on
        // resources that traverse through relationships
        // ───────────────────────────────────────────
        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('organization_id')
                ->after('uuid')
                ->constrained()
                ->cascadeOnDelete();
        });

        Schema::table('project_members', function (Blueprint $table): void {
            $table->foreignId('organization_id')
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });

        // ───────────────────────────────────────────
        // UUIDs on all child/pivot tables for
        // consistent API reference
        // ───────────────────────────────────────────
        Schema::table('organization_user', function (Blueprint $table): void {
            $table->uuid('uuid')->after('id')->unique();
        });

        Schema::table('project_members', function (Blueprint $table): void {
            $table->uuid('uuid')->after('id')->unique();
        });

        Schema::table('form_versions', function (Blueprint $table): void {
            $table->uuid('uuid')->after('id')->unique();
        });

        Schema::table('form_sections', function (Blueprint $table): void {
            $table->uuid('uuid')->after('id')->unique();
        });

        Schema::table('form_fields', function (Blueprint $table): void {
            $table->uuid('uuid')->after('id')->unique();
        });

        Schema::table('submission_files', function (Blueprint $table): void {
            $table->uuid('uuid')->after('id')->unique();
        });

        Schema::table('scheduled_reports', function (Blueprint $table): void {
            $table->uuid('uuid')->after('id')->unique();
        });

        // ───────────────────────────────────────────
        // Soft Deletes for data retention safety
        // ───────────────────────────────────────────
        Schema::table('tasks', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('form_versions', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('form_sections', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('form_fields', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('submission_files', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('report_exports', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('scheduled_reports', function (Blueprint $table): void {
            $table->softDeletes();
        });

        // ───────────────────────────────────────────
        // New columns for enhanced functionality
        // ───────────────────────────────────────────
        Schema::table('form_fields', function (Blueprint $table): void {
            $table->string('placeholder')->nullable()->after('help_text');
        });

        // ───────────────────────────────────────────
        // Performance indexes on frequently-queried
        // and foreign-key columns
        // ───────────────────────────────────────────
        foreach (self::INDEX_TABLES as $table => $columns) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($columns): void {
                foreach ($columns as $columnGroup) {
                    $indexName = $table.'_'.implode('_', $columnGroup).'_idx';
                    $tableBlueprint->index($columnGroup, $indexName);
                }
            });
        }
    }

    public function down(): void
    {
        // Remove indexes
        foreach (self::INDEX_TABLES as $table => $columns) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($columns): void {
                foreach ($columns as $columnGroup) {
                    $indexName = $table.'_'.implode('_', $columnGroup).'_idx';
                    $tableBlueprint->dropIndex($indexName);
                }
            });
        }

        // Remove new columns
        Schema::table('form_fields', function (Blueprint $table): void {
            $table->dropColumn('placeholder');
        });

        // Remove soft deletes
        foreach (['scheduled_reports', 'report_exports', 'submission_files', 'form_fields', 'form_sections', 'form_versions', 'tasks'] as $table) {
            Schema::table($table, function (Blueprint $tableBlueprint): void {
                $table->dropSoftDeletes();
            });
        }

        // Remove UUIDs
        foreach (['scheduled_reports', 'submission_files', 'form_fields', 'form_sections', 'form_versions', 'project_members', 'organization_user'] as $table) {
            Schema::table($table, function (Blueprint $tableBlueprint): void {
                $table->dropColumn('uuid');
            });
        }

        // Remove organization_id
        Schema::table('project_members', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
