<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    use HasFactory;

    // Action Constants
    public const ACTION_LOGIN = 'LOGIN';
    public const ACTION_LOGOUT = 'LOGOUT';
    public const ACTION_IMPORT = 'IMPORT';
    public const ACTION_DELETE = 'DELETE';
    public const ACTION_USER_CREATE = 'USER_CREATE';
    public const ACTION_USER_UPDATE = 'USER_UPDATE';
    public const ACTION_USER_DELETE = 'USER_DELETE';
    public const ACTION_DOWNLOAD = 'DOWNLOAD';
    public const ACTION_SYSTEM = 'SYSTEM';

    // Level Constants
    public const LEVEL_INFO = 'info';
    public const LEVEL_SUCCESS = 'success';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'level',
        'description',
        'ip_address',
        'user_agent',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    /**
     * Relasi ke model User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat aktivitas aplikasi secara praktis
     */
    public static function record(
        string $action,
        string $description,
        array $context = [],
        string $level = self::LEVEL_INFO,
        ?User $user = null
    ): self {
        $currentUser = $user ?? Auth::user();

        return static::create([
            'user_id' => $currentUser?->id,
            'user_name' => $currentUser?->name ?? 'System',
            'action' => strtoupper($action),
            'level' => strtolower($level),
            'description' => $description,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'System',
            'context' => !empty($context) ? $context : null,
        ]);
    }
}
