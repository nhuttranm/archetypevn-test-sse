<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /* ── Relationships ────────────────────────── */

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'created_by');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PoStatusLog::class, 'acted_by');
    }

    /* ── Role Helpers ─────────────────────────── */

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isDirector(): bool
    {
        return $this->role === 'director';
    }

    public function isFinance(): bool
    {
        return $this->role === 'finance';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /* ── Scopes ────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}
