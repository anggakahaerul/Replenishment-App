<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'branch_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_OPERATOR = 'operator';
    public const ROLE_VIEWER = 'viewer';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'branch_id' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    /**
     * Determine if the user has global access across all branches.
     * Any user (Admin, Operator, Viewer) with branch_id = null can access all branches.
     */
    public function hasGlobalBranchAccess(): bool
    {
        return $this->branch_id === null;
    }

    /**
     * Determine if the user can access a specific branch ID.
     */
    public function canAccessBranch(?int $branchId): bool
    {
        if ($this->hasGlobalBranchAccess()) {
            return true;
        }

        return (int) $this->branch_id === (int) $branchId;
    }

    public function canUpload(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_OPERATOR], true);
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }
}
