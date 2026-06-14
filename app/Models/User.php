<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ADMIN_PORTAL_ROLES = ['admin', 'finance', 'staff'];

    public const PAYMENT_REVIEW_ROLES = ['admin', 'finance'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'access_permissions',
        'active_admin_session_id',
        'last_admin_login_at',
        'account_status',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_admin_login_at' => 'datetime',
            'password' => 'hashed',
            'access_permissions' => 'array',
        ];
    }

    public function enrollmentApplicant(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EnrollmentApplicant::class);
    }

    public function linkedIdentities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LinkedIdentity::class);
    }

    public function enrollmentApplicants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EnrollmentApplicant::class);
    }

    public function students(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(string|array $role): bool
    {
        $roles = is_array($role) ? $role : [$role];

        if ($this->relationLoaded('roles') || $this->roles()->exists()) {
            return $this->roles->pluck('slug')->intersect($roles)->isNotEmpty();
        }

        return in_array($this->role, $roles, true);
    }

    public function hasPermission(string|array $permission): bool
    {
        $permissions = is_array($permission) ? $permission : [$permission];

        if ($this->relationLoaded('roles') || $this->roles()->exists()) {
            $userPermissions = $this->roles->flatMap(function ($role) {
                return $role->permissions->pluck('slug');
            })->unique();

            if ($userPermissions->intersect($permissions)->isNotEmpty()) {
                return true;
            }
        }

        foreach ($permissions as $perm) {
            if ($this->allowsAccess($perm)) {
                return true;
            }
        }

        return false;
    }

    public function hasAdminPortalAccess(): bool
    {
        if ($this->relationLoaded('roles') || $this->roles()->exists()) {
            return $this->roles->pluck('slug')->intersect(['super_admin', 'admin', 'finance', 'staff'])->isNotEmpty();
        }
        return in_array($this->role, self::ADMIN_PORTAL_ROLES, true);
    }

    public function canReviewEnrollmentPayments(): bool
    {
        return $this->hasPermission('payment_review');
    }

    public function canReviewEnrollmentApplications(): bool
    {
        return $this->hasPermission('document_review');
    }

    public function isViewOnlyAccess(): bool
    {
        return (bool) ($this->access_permissions['view_only'] ?? ($this->role === 'staff'));
    }

    public function defaultAccessPermissions(): array
    {
        return [
            'payment_review' => in_array($this->role, self::PAYMENT_REVIEW_ROLES, true),
            'document_review' => $this->role === 'admin',
            'view_only' => $this->role === 'staff',
        ];
    }

    private function allowsAccess(string $key, bool $default = false): bool
    {
        $permissions = $this->access_permissions;

        if (! is_array($permissions)) {
            $permissions = $this->defaultAccessPermissions();
        }

        if ((bool) ($permissions['view_only'] ?? false)) {
            return false;
        }

        return (bool) ($permissions[$key] ?? $default);
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = mb_strtoupper($value, 'UTF-8');
    }
}
