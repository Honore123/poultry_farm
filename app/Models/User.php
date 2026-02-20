<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Mail\TwoFactorCodeMail;
use App\Notifications\ResetPasswordNotification;
use App\Tenancy\TenantContext;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Multitenancy\CurrentTenantContainer;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, LogsActivity, HasRoles {
        assignRole as protected spatieAssignRole;
        syncRoles as protected spatieSyncRoles;
        hasRole as protected spatieHasRole;
        hasAnyRole as protected spatieHasAnyRole;
        hasAllRoles as protected spatieHasAllRoles;
        hasPermissionTo as protected spatieHasPermissionTo;
        checkPermissionTo as protected spatieCheckPermissionTo;
        hasAnyPermission as protected spatieHasAnyPermission;
        hasAllPermissions as protected spatieHasAllPermissions;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "User {$eventName}");
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Allow all users to access the admin panel
    }

    protected function ensurePermissionsTeamId(): void
    {
        if (!class_exists(PermissionRegistrar::class)) {
            return;
        }

        if ($this->is_super_admin) {
            return;
        }

        $tenantId = $this->tenant_id ?: null;

        $context = app(TenantContext::class);
        $tenant = $tenantId ? ($this->relationLoaded('tenant') ? $this->tenant : $this->tenant()->getResults()) : null;
        $context->setTenant($tenant);
        $context->applyPermissionContext($tenant);

        if (class_exists(CurrentTenantContainer::class)) {
            CurrentTenantContainer::set($tenant);
        }
    }

    public function assignRole(...$roles)
    {
        $this->ensurePermissionsTeamId();

        return $this->spatieAssignRole(...$roles);
    }

    public function syncRoles(...$roles)
    {
        $this->ensurePermissionsTeamId();

        return $this->spatieSyncRoles(...$roles);
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        $this->ensurePermissionsTeamId();

        return $this->spatieHasRole($roles, $guard);
    }

    public function hasAnyRole(...$roles): bool
    {
        $this->ensurePermissionsTeamId();

        return $this->spatieHasAnyRole(...$roles);
    }

    public function hasAllRoles($roles, ?string $guard = null): bool
    {
        $this->ensurePermissionsTeamId();

        return $this->spatieHasAllRoles($roles, $guard);
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $this->ensurePermissionsTeamId();

        return $this->spatieHasPermissionTo($permission, $guardName);
    }

    public function checkPermissionTo($permission, $guardName = null): bool
    {
        $this->ensurePermissionsTeamId();

        return $this->spatieCheckPermissionTo($permission, $guardName);
    }

    public function hasAnyPermission(...$permissions): bool
    {
        $this->ensurePermissionsTeamId();

        return $this->spatieHasAnyPermission(...$permissions);
    }

    public function hasAllPermissions(...$permissions): bool
    {
        $this->ensurePermissionsTeamId();

        return $this->spatieHasAllPermissions(...$permissions);
    }

    /**
     * Get the employee salary record for this user
     */
    public function employeeSalary(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeeSalary::class);
    }

    /**
     * Get all salary payments for this user through their employee salary
     */
    public function salaryPayments(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(SalaryPayment::class, EmployeeSalary::class);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Generate and send a two-factor authentication code.
     */
    public function generateTwoFactorCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'two_factor_code' => $code,
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($this->email)->send(new TwoFactorCodeMail($this, $code));

        return $code;
    }

    /**
     * Verify the two-factor authentication code.
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        if ($this->two_factor_code !== $code) {
            return false;
        }

        if ($this->two_factor_expires_at && $this->two_factor_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Clear the two-factor authentication code.
     */
    public function clearTwoFactorCode(): void
    {
        $this->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ]);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'is_super_admin',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
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
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'two_factor_expires_at' => 'datetime',
        ];
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
