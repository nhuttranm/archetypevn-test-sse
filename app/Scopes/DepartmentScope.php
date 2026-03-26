<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Row-Level Security Scope
 * 
 * Automatically filters purchase orders by the authenticated user's department.
 * Director and Finance roles are exempt (they see all departments).
 */
class DepartmentScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Director and Finance can see all departments
            if (in_array($user->role, ['director', 'finance'])) {
                return;
            }

            $builder->where($model->getTable() . '.department_id', $user->department_id);
        }
    }
}
