<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ADMIN_PORTAL_ROLES = ['admin', 'finance', 'staff'];

    public const ADMIN_PORTAL_ROLE_SLUGS = ['super_admin', 'admin', 'finance', 'staff', 'teacher'];

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
        'last_active_at',
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
            'last_active_at' => 'datetime',
            'last_admin_login_at' => 'datetime',
            'password' => 'hashed',
            'access_permissions' => 'array',
        ];
    }

    public function enrollmentApplicant(): HasOne
    {
        return $this->hasOne(EnrollmentApplicant::class);
    }

    public function linkedIdentities(): HasMany
    {
        return $this->hasMany(LinkedIdentity::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EbookAccessLog::class, 'user_id');
    }

    public function ebooks(): HasMany
    {
        return $this->hasMany(Ebook::class, 'created_by');
    }

    public function enrollmentApplicants(): HasMany
    {
        return $this->hasMany(EnrollmentApplicant::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function roles(): BelongsToMany
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
            return $this->roles->pluck('slug')->intersect(self::ADMIN_PORTAL_ROLE_SLUGS)->isNotEmpty();
        }

        return in_array($this->role, self::ADMIN_PORTAL_ROLE_SLUGS, true);
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
        if ($this->hasRole(['super_admin', 'admin', 'finance'])) {
            return false;
        }

        if ($this->relationLoaded('roles') || $this->roles()->exists()) {
            $rolePermissions = $this->roles->flatMap(function ($role) {
                return $role->permissions->pluck('slug');
            });

            if ($rolePermissions->contains('view_only')) {
                return true;
            }
        }

        return (bool) ($this->access_permissions['view_only'] ?? ($this->role === 'staff'));
    }

    public function isTeacherAdminViewer(): bool
    {
        return $this->hasRole('teacher') && ! $this->hasRole(['super_admin', 'admin', 'finance']);
    }

    public function adminHomeRouteName(): string
    {
        return $this->isTeacherAdminViewer()
            ? 'admin.applications.enrollment'
            : 'admin.dashboard';
    }

    public function adminVisibleGradeLevels(): array
    {
        if (! $this->isTeacherAdminViewer()) {
            return [];
        }

        $email = strtolower((string) $this->email);
        $username = strtolower((string) $this->username);
        $grades = collect();

        if (Schema::hasTable('class_advisory_assignments') && Schema::hasTable('sections')) {
            $grades = $grades->merge(
                ClassAdvisoryAssignment::query()
                    ->with('section:id,grade_level')
                    ->where('status', 'active')
                    ->where(function ($query) use ($email, $username) {
                        $email !== ''
                            ? $query->whereRaw('LOWER(teacher_email) = ?', [$email])
                            : $query->whereRaw('1 = 0');

                        if ($username !== '') {
                            $query->orWhereRaw('LOWER(teacher_key) = ?', [$username]);
                        }
                    })
                    ->get()
                    ->pluck('section.grade_level')
            );
        }

        if (Schema::hasTable('teacher_subject_assignments') && Schema::hasTable('subjects')) {
            $grades = $grades->merge(
                TeacherSubjectAssignment::query()
                    ->with('subject:id,grade_level')
                    ->where('status', 'active')
                    ->where(function ($query) use ($email, $username) {
                        $email !== ''
                            ? $query->whereRaw('LOWER(teacher_email) = ?', [$email])
                            : $query->whereRaw('1 = 0');

                        if ($username !== '') {
                            $query->orWhereRaw('LOWER(teacher_key) = ?', [$username]);
                        }
                    })
                    ->get()
                    ->pluck('subject.grade_level')
            );
        }

        $gradeOrder = [
            'Kinder 1',
            'Kinder 2',
            'Grade 1',
            'Grade 2',
            'Grade 3',
            'Grade 4',
            'Grade 5',
            'Grade 6',
            'Grade 7',
            'Grade 8',
            'Grade 9',
            'Grade 10',
            'Grade 11',
            'Grade 12',
        ];

        return $grades
            ->filter()
            ->map(fn ($grade) => trim((string) $grade))
            ->unique()
            ->sortBy(fn ($grade) => array_search($grade, $gradeOrder, true) === false ? 999 : array_search($grade, $gradeOrder, true))
            ->values()
            ->all();
    }

    public function canViewAdminGrade(?string $gradeLevel): bool
    {
        if (! $this->isTeacherAdminViewer()) {
            return true;
        }

        return in_array((string) $gradeLevel, $this->adminVisibleGradeLevels(), true);
    }

    public function canAccessAdminRoute(?string $routeName): bool
    {
        if (! $this->isTeacherAdminViewer()) {
            return true;
        }

        if (! $routeName) {
            return false;
        }

        return $routeName === 'admin.applications.index'
            || $routeName === 'admin.applications.enrollment'
            || $routeName === 'admin.applications.print-no-payment'
            || $routeName === 'admin.applicants.index'
            || $routeName === 'admin.applicants.show'
            || $routeName === 'admin.students.index'
            || $routeName === 'admin.students.show'
            || $routeName === 'admin.students.reports'
            || str_starts_with($routeName, 'admin.support.');
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
        $this->attributes['name'] = $value !== null ? mb_strtoupper($value, 'UTF-8') : null;
    }

    public function isActive(): bool
    {
        return $this->last_active_at && $this->last_active_at->gt(now()->subMinutes(5));
    }
}
