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
    public const ROLE_SUPERVISOR = 'supervisor';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_VIEWER = 'viewer';

    /** @var array<string, array<string, bool>>|null */
    protected ?array $resolvedPermissions = null;

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

    public function roleRecord(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'slug');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    public function isSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER || $this->isSupervisor();
    }

    /**
     * Check whether the user has a given permission on a menu.
     */
    public function hasPermission(string $menu, string $action = 'view'): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $perms = $this->getResolvedPermissions();
        return (bool) ($perms[$menu][$action] ?? false);
    }

    /**
     * Resolve and cache the user's permission matrix from the roles table.
     *
     * @return array<string, array<string, bool>>
     */
    public function getResolvedPermissions(): array
    {
        if ($this->resolvedPermissions !== null) {
            return $this->resolvedPermissions;
        }

        $lookupSlug = $this->role === self::ROLE_VIEWER ? self::ROLE_SUPERVISOR : $this->role;
        $roleModel = Role::where('slug', $lookupSlug)->first();

        if ($roleModel && is_array($roleModel->permissions)) {
            return $this->resolvedPermissions = $roleModel->permissions;
        }

        $defaults = collect(Role::defaultDefinitions())->keyBy('slug');
        return $this->resolvedPermissions = $defaults->get($lookupSlug)['permissions'] ?? Role::buildMatrix(false);
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN      => 'ADMIN',
            self::ROLE_OPERATOR   => 'OPERATOR',
            self::ROLE_SUPERVISOR => 'SUPERVISOR',
            self::ROLE_MANAGER    => 'MANAGER',
            default               => strtoupper((string) $this->role),
        };
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
        return $this->hasPermission('matching', 'create');
    }

    public function canManageUsers(): bool
    {
        return $this->hasPermission('users', 'view');
    }
}
