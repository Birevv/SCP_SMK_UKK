<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['role_id', 'full_name', 'username', 'email', 'password', 'identity_number', 'phone', 'profile_photo', 'status'])]
#[Hidden(['password', 'remember_token'])]
/**
 * @mixin HasRoles
 */
class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->role_id && $user->role) {
                if (! $user->hasRole($user->role->name)) {
                    $user->syncRoles([$user->role->name]);
                }
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['Super Admin', 'Waka Kurikulum', 'Guru', 'Wali Kelas', 'Guru BK', 'Siswa'])
            || in_array($this->role?->name, ['Super Admin', 'Waka Kurikulum', 'Guru', 'Wali Kelas', 'Guru BK', 'Siswa']);
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }

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
        ];
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo
            ? Storage::url($this->profile_photo)
            : null;
    }

    public function getClassroomAttribute()
    {
        return $this->classrooms->first();
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'user_class')
            ->withPivot(['academic_year_id', 'status'])
            ->withTimestamps();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
