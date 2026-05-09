<?php

declare(strict_types=1);

use App\Enums\FormStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status')->default(FormStatus::DRAFT->value)->index();
            $table->unsignedInteger('current_version')->default(1);
            $table->json('schema')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('form_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->json('schema');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['form_id', 'version']);
        });

        Schema::create('form_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('form_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('type')->index();
            $table->boolean('is_required')->default(false);
            $table->json('validation_rules')->nullable();
            $table->json('options')->nullable();
            $table->json('conditional_logic')->nullable();
            $table->text('default_value')->nullable();
            $table->text('help_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['form_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('form_sections');
        Schema::dropIfExists('form_versions');
        Schema::dropIfExists('forms');
    }
};
