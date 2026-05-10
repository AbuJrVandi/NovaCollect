<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\DTOs\Auth\RegisterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    #[OA\Post(
        path: '/auth/register',
        operationId: 'register',
        summary: 'Register a new user',
        description: 'Creates a new user account with an organization. Returns user data, organization UUID, and Bearer token.',
        tags: ['Authentication'],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'User registered successfully',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'message', type: 'string', example: 'Registration successful.'),
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                new OA\Property(property: 'organization_uuid', type: 'string', example: '550e8400-e29b-41d4-a716-446655440000'),
                new OA\Property(property: 'token', type: 'string', example: '1|abc123def456...'),
            ], type: 'object'),
        ])
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'message', type: 'string', example: 'Validation failed.'),
            new OA\Property(property: 'errors', type: 'object', example: ['email' => ['The email has already been taken.']]),
        ])
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(RegisterData::fromArray($request->validated()), $request);

        return $this->success([
            'user' => new UserResource($result['user']),
            'organization_uuid' => $result['organization']->uuid,
            'token' => $result['token'],
        ], 'Registration successful.', status: 201);
    }

    #[OA\Post(
        path: '/auth/login',
        operationId: 'login',
        summary: 'Login user',
        description: 'Authenticates user with email and password. Returns user data and Bearer token.',
        tags: ['Authentication'],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
            new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
            new OA\Property(property: 'device_name', type: 'string', example: 'web', nullable: true),
        ])
    )]
    #[OA\Response(
        response: 200,
        description: 'Login successful',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'message', type: 'string', example: 'Login successful.'),
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                new OA\Property(property: 'token', type: 'string', example: '2|xyz789abc012...'),
            ], type: 'object'),
        ])
    )]
    #[OA\Response(
        response: 422,
        description: 'Invalid credentials',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'message', type: 'string', example: 'Validation failed.'),
            new OA\Property(property: 'errors', type: 'object', example: ['email' => ['The provided credentials are incorrect.']]),
        ])
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated(), $request);

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Login successful.');
    }

    #[OA\Post(
        path: '/auth/logout',
        operationId: 'logout',
        summary: 'Logout user',
        description: 'Revokes current Bearer token.',
        tags: ['Authentication'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Logout successful',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'message', type: 'string', example: 'Logout successful.'),
            new OA\Property(property: 'data', type: 'object', example: []),
        ])
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(message: 'Logout successful.');
    }

    #[OA\Post(
        path: '/auth/refresh',
        operationId: 'refreshToken',
        summary: 'Refresh API token',
        description: 'Revokes current token and issues a new one.',
        tags: ['Authentication'],
        security: [['sanctum' => []]]
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'device_name', type: 'string', example: 'web', nullable: true),
        ])
    )]
    #[OA\Response(
        response: 200,
        description: 'Token refreshed',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'message', type: 'string', example: 'Token refreshed successfully.'),
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'token', type: 'string', example: '3|newtoken...'),
            ], type: 'object'),
        ])
    )]
    public function refresh(Request $request): JsonResponse
    {
        $deviceName = $request->input('device_name');
        $token = $this->authService->refreshToken($request->user(), is_string($deviceName) && $deviceName !== '' ? $deviceName : 'web');

        return $this->success([
            'token' => $token,
        ], 'Token refreshed successfully.');
    }

    #[OA\Post(
        path: '/auth/forgot-password',
        operationId: 'forgotPassword',
        summary: 'Send password reset link',
        description: 'Sends a password reset email to the given address.',
        tags: ['Authentication'],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        ])
    )]
    #[OA\Response(
        response: 200,
        description: 'Reset link processed',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'message', type: 'string', example: 'Password reset link processed.'),
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'status', type: 'string', example: 'We have emailed your password reset link.'),
            ], type: 'object'),
        ])
    )]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->sendPasswordResetLink($request->validated('email'));

        return $this->success([
            'status' => $status,
        ], 'Password reset link processed.');
    }

    #[OA\Post(
        path: '/auth/reset-password',
        operationId: 'resetPassword',
        summary: 'Reset password',
        description: 'Resets the password using a token from the reset email.',
        tags: ['Authentication'],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'token', type: 'string', example: 'reset-token-from-email'),
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
            new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewP@ss123'),
            new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewP@ss123'),
        ])
    )]
    #[OA\Response(
        response: 200,
        description: 'Password reset successful',
    )]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->resetPassword($request->validated());

        return $this->success([
            'status' => $status,
        ], 'Password reset successful.');
    }

    #[OA\Get(
        path: '/auth/profile',
        operationId: 'getProfile',
        summary: 'Get user profile',
        description: 'Returns the authenticated user\'s profile.',
        tags: ['Authentication'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Profile retrieved',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'message', type: 'string', example: 'Profile retrieved successfully.'),
            new OA\Property(property: 'data', ref: '#/components/schemas/User'),
        ])
    )]
    public function profile(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()->load('currentOrganization')), 'Profile retrieved successfully.');
    }

    #[OA\Put(
        path: '/auth/profile',
        operationId: 'updateProfile',
        summary: 'Update user profile',
        description: 'Updates the authenticated user\'s profile information.',
        tags: ['Authentication'],
        security: [['sanctum' => []]]
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
            new OA\Property(property: 'phone', type: 'string', example: '+1234567890', nullable: true),
            new OA\Property(property: 'job_title', type: 'string', example: 'Field Officer', nullable: true),
            new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewP@ss123', nullable: true),
            new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewP@ss123', nullable: true),
        ])
    )]
    #[OA\Response(
        response: 200,
        description: 'Profile updated',
    )]
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated());

        return $this->success(new UserResource($user), 'Profile updated successfully.');
    }

    #[OA\Get(
        path: '/auth/email/verify/{id}/{hash}',
        operationId: 'verifyEmail',
        summary: 'Verify email address',
        description: 'Verifies the user\'s email address via signed URL.',
        tags: ['Authentication'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'hash', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Email verified')]
    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::query()->find($id);

        if (! $user) {
            return $this->error('User not found.', status: 404);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return $this->error('Invalid verification hash.', status: 400);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->success(message: 'Email already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->success(message: 'Email verified successfully.');
    }

    #[OA\Post(
        path: '/auth/email/verification-notification',
        operationId: 'resendVerification',
        summary: 'Resend verification email',
        description: 'Resends the email verification notification.',
        tags: ['Authentication'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(response: 200, description: 'Verification notification sent')]
    public function resendVerification(Request $request): JsonResponse
    {
        $request->user()->sendEmailVerificationNotification();

        return $this->success(message: 'Verification notification sent.');
    }
}
