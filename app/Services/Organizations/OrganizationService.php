<?php

declare(strict_types=1);

namespace App\Services\Organizations;

use App\DTOs\Organizations\OrganizationData;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\PlatformRole;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Notifications\Organizations\OrganizationInvitationNotification;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class OrganizationService
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizations,
    ) {}

    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->organizations->paginateForUser($user, $filters);
    }

    public function create(OrganizationData $data, User $user): Organization
    {
        return DB::transaction(function () use ($data, $user): Organization {
            $organization = $this->organizations->create([
                'name' => $data->name,
                'slug' => $this->uniqueSlug($data->slug ?: $data->name),
                'description' => $data->description,
                'country' => $data->country,
                'timezone' => $data->timezone,
                'settings' => $data->settings,
                'owner_user_id' => $user->id,
            ]);

            OrganizationUser::query()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => MembershipRole::OWNER->value,
                'status' => MembershipStatus::ACTIVE->value,
                'joined_at' => now(),
            ]);

            $user->forceFill([
                'current_organization_id' => $organization->id,
            ])->save();

            return $organization->load(['owner', 'users']);
        });
    }

    public function update(Organization $organization, OrganizationData $data): Organization
    {
        return $this->organizations->update($organization, [
            'name' => $data->name,
            'slug' => $data->slug ? $this->uniqueSlug($data->slug, $organization->id) : $organization->slug,
            'description' => $data->description,
            'country' => $data->country,
            'timezone' => $data->timezone,
            'settings' => $data->settings,
        ]);
    }

    public function inviteUser(Organization $organization, string $email, string $role, User $actor): User
    {
        return DB::transaction(function () use ($organization, $email, $role, $actor): User {
            $wasNew = false;

            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => Str::before($email, '@'),
                    'password' => Str::password(20),
                    'status' => MembershipStatus::INVITED->value,
                ],
            );

            if ($user->wasRecentlyCreated) {
                $wasNew = true;
            }

            OrganizationUser::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => $role,
                    'status' => MembershipStatus::INVITED->value,
                    'invited_by' => $actor->id,
                ],
            );

            if (in_array($role, PlatformRole::values(), true)) {
                $user->syncRoles([$role]);
            }

            $user->notify(new OrganizationInvitationNotification($organization, $role));

            if ($wasNew) {
                Password::sendResetLink(['email' => $user->email]);
            }

            activity()
                ->performedOn($organization)
                ->causedBy($actor)
                ->event('invited')
                ->withProperties([
                    'invitee_email' => $email,
                    'role' => $role,
                ])
                ->log('User invited to organization.');

            return $user;
        });
    }

    public function delete(Organization $organization, User $actor): void
    {
        User::query()->where('current_organization_id', $organization->id)->update(['current_organization_id' => null]);

        $organization->delete();

        activity()
            ->causedBy($actor)
            ->event('deleted')
            ->log('Organization deleted.');
    }

    public function switchCurrentOrganization(User $user, Organization $organization): User
    {
        abort_unless($user->belongsToOrganization($organization->id) || $user->isSuperAdmin(), 403);

        $user->forceFill([
            'current_organization_id' => $organization->id,
        ])->save();

        OrganizationUser::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->update(['last_access_at' => now()]);

        return $user->refresh();
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'organization';
        $candidate = $base;
        $counter = 1;

        while (Organization::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = "{$base}-{$counter}";
            $counter++;
        }

        return $candidate;
    }
}
