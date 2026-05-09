<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Form;
use App\Models\User;
use App\Repositories\Contracts\FormRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FormRepository implements FormRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Form::query()
            ->with(['organization', 'project', 'sections.fields'])
            ->when(! $user->isSuperAdmin(), fn ($builder) => $builder->where('organization_id', $user->current_organization_id))
            ->when($filters['search'] ?? null, function ($builder, string $search): void {
                $builder->where(fn ($nested) => $nested
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%"));
            })
            ->when($filters['status'] ?? null, fn ($builder, string $status) => $builder->where('status', $status))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc');

        return $query->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }

    public function findScoped(User $user, string $uuid): Form
    {
        return Form::query()
            ->with(['organization', 'project', 'sections.fields', 'versions'])
            ->when(! $user->isSuperAdmin(), fn ($builder) => $builder->where('organization_id', $user->current_organization_id))
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function create(array $attributes): Form
    {
        return Form::query()->create($attributes);
    }

    public function update(Form $form, array $attributes): Form
    {
        $form->fill($attributes)->save();

        return $form->refresh();
    }
}
