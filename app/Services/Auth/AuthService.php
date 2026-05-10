<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\RegisterData;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(RegisterData $data, Request $request): array
    {
        return DB::transaction(function () use ($data, $request): array {
            $user = User::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'status' => MembershipStatus::ACTIVE->value,
            ]);

            $user->assignRole($data->role);

            $organization = Organization::query()->create([
                'name' => $data->organizationName ?: "{$data->name} Workspace",
                'slug' => $this->uniqueOrganizationSlug($data->organizationSlug ?: Str::slug($data->organizationName ?: "{$data->name} Workspace")),
                'owner_user_id' => $user->id,
                'status' => OrganizationStatus::ACTIVE->value,
            ]);

            $organization->memberships()->create([
                'user_id' => $user->id,
                'role' => MembershipRole::OWNER->value,
                'status' => MembershipStatus::ACTIVE->value,
                'joined_at' => now(),
            ]);

            $user->forceFill([
                'current_organization_id' => $organization->id,
            ])->save();

            if (app()->environment('local')) {
                $user->markEmailAsVerified();
            }

            event(new Registered($user));

            $token = $user->createToken($request->string('device_name')->toString() ?: 'web')->plainTextToken;

            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->event('registered')
                ->withProperties([
                    'organization_id' => $organization->id,
                    'ip' => $request->ip(),
                ])
                ->log('User registered.');

            return compact('user', 'organization', 'token');
        });
    }

    public function login(array $credentials, Request $request): array
    {
        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        /** @var User $user */
        $user = User::query()->where('email', $credentials['email'])->firstOrFail();

        if (! $user->hasVerifiedEmail()) {
            throw new AuthorizationException('Please verify your email address before signing in.');
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $token = $user->createToken($credentials['device_name'] ?? 'web')->plainTextToken;

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('login')
            ->withProperties(['ip' => $request->ip()])
            ->log('User logged in.');

        return compact('user', 'token');
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function refreshToken(User $user, string $deviceName = 'web'): string
    {
        $user->currentAccessToken()?->delete();

        return $user->createToken($deviceName)->plainTextToken;
    }

    public function sendPasswordResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(array $payload): string
    {
        return Password::reset(
            [
                'email' => $payload['email'],
                'password' => $payload['password'],
                'password_confirmation' => $payload['password'],
                'token' => $payload['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
            },
        );
    }

    public function updateProfile(User $user, array $attributes): User
    {
        $emailChanged = isset($attributes['email']) && $attributes['email'] !== $user->email;

        if (array_key_exists('password', $attributes) && $attributes['password'] === null) {
            unset($attributes['password']);
        }

        $user->fill($attributes);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return $user->refresh();
    }

    private function uniqueOrganizationSlug(string $slug): string
    {
        $base = Str::slug($slug) ?: 'workspace';
        $candidate = $base;
        $counter = 1;

        while (Organization::query()->where('slug', $candidate)->exists()) {
            $candidate = "{$base}-{$counter}";
            $counter++;
        }

        return $candidate;
    }
}
