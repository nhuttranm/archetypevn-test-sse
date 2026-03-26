<?php

namespace App\Models;

use App\Scopes\DepartmentScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number',
        'parent_po_id',
        'department_id',
        'vendor_id',
        'created_by',
        'total_amount',
        'status',
        'revision_number',
        'is_latest',
        'notes',
        'rejection_reason',
        'submitted_at',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'is_latest' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /* ── Status Constants ─────────────────────── */

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_MANAGER = 'pending_manager';
    const STATUS_PENDING_DIRECTOR = 'pending_director';
    const STATUS_PENDING_FINANCE = 'pending_finance';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_MANAGER,
        self::STATUS_PENDING_DIRECTOR,
        self::STATUS_PENDING_FINANCE,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    /* ── Boot ──────────────────────────────────── */

    protected static function booted(): void
    {
        // Row-level security: Auto-scope POs by user department
        // Only apply when there's an authenticated user who is not finance/director
        if (auth()->check()) {
            $user = auth()->user();
            if (!in_array($user->role, ['director', 'finance'])) {
                static::addGlobalScope(new DepartmentScope());
            }
        }
    }

    /* ── Relationships ────────────────────────── */

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentPo(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'parent_po_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'parent_po_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PoItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PoStatusLog::class)->orderBy('created_at', 'desc');
    }

    /* ── Status Helpers ───────────────────────── */

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_MANAGER,
            self::STATUS_PENDING_DIRECTOR,
            self::STATUS_PENDING_FINANCE,
        ]);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /* ── PO Number Generator ──────────────────── */

    public static function generatePoNumber(): string
    {
        $prefix = 'PO-' . date('Ym');
        $lastPo = static::withoutGlobalScopes()
            ->where('po_number', 'like', $prefix . '%')
            ->orderBy('po_number', 'desc')
            ->first();

        if ($lastPo) {
            $lastNumber = (int) substr($lastPo->po_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /* ── Scopes ────────────────────────────────── */

    public function scopeLatest($query)
    {
        return $query->where('is_latest', true);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
}
