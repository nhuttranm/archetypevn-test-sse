<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'current_state',
        'next_state',
        'required_role',
        'condition_expression',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'condition_expression' => 'array',
        'is_active' => 'boolean',
    ];

    /* ── Scopes ────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForState($query, string $state)
    {
        return $query->where('current_state', $state);
    }
}
