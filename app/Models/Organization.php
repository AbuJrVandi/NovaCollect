<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'country',
        'timezone',
        'logo_path',
        'status',
        'settings',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot(['id', 'role', 'status', 'invited_by', 'joined_at', 'last_access_at'])
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function forms(): HasMany
    {
        return $this->hasMany(Form::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }
}
