<?php

declare(strict_types=1);

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('country', 2)->nullable()->index();
            $table->string('timezone')->default('UTC');
            $table->string('logo_path')->nullable();
            $table->string('status')->default(OrganizationStatus::ACTIVE->value)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('organization_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role')->default(MembershipRole::MEMBER->value)->index();
            $table->string('status')->default(MembershipStatus::INVITED->value)->index();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_access_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('current_organization_id')
                ->references('id')
                ->on('organizations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['current_organization_id']);
        });

        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
    }
};
