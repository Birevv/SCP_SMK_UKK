<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
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
class User extends Authenticatable implements FilamentUser, HasAvatar, HasName
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

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile_photo_url;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->profile_photo_url;
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo) {
            return null;
        }

        if (str_starts_with($this->profile_photo, 'http://') || str_starts_with($this->profile_photo, 'https://')) {
            return $this->profile_photo;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $url = $disk->url($this->profile_photo);

        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
            ? $url
            : url($url);
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

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }

    public function getAssignmentAttribute(): string
    {
        $roleName = $this->role?->name;

        // Siswa
        if ($this->hasRole('Siswa') || $roleName === 'Siswa') {
            $classroom = $this->classrooms->first();

            return $classroom?->name ?? 'Siswa';
        }

        // Guru BK
        if ($this->hasRole('Guru BK') || $roleName === 'Guru BK') {
            return 'Guru BK';
        }

        $assignments = [];

        // Check Wali Kelas
        $classroom = $this->classrooms->first();
        $isWali = $this->hasRole('Wali Kelas') || $roleName === 'Wali Kelas' || ($classroom && ($this->hasRole('Guru') || $roleName === 'Guru'));
        if ($isWali && $classroom) {
            $assignments[] = "Wali Kelas {$classroom->name}";
        } elseif ($this->hasRole('Wali Kelas') || $roleName === 'Wali Kelas') {
            $assignments[] = 'Wali Kelas';
        }

        // Check Guru Mata Pelajaran
        $subjects = Schedule::where('teacher_id', $this->id)
            ->with('subject')
            ->get()
            ->pluck('subject.name')
            ->filter()
            ->unique()
            ->values();

        if ($subjects->isNotEmpty()) {
            $assignments[] = 'Guru '.$subjects->join(', ');
        } elseif (($this->hasRole('Guru') || $roleName === 'Guru') && empty($assignments)) {
            $assignments[] = 'Guru Mata Pelajaran';
        }

        if (count($assignments) >= 2) {
            return "'{$assignments[0]}' dan '{$assignments[1]}'";
        }

        if (count($assignments) === 1) {
            return $assignments[0];
        }

        // Fallbacks
        if ($this->hasRole('Waka Kurikulum') || $roleName === 'Waka Kurikulum') {
            return 'Waka Kurikulum';
        }
        if ($this->hasRole('Super Admin') || $roleName === 'Super Admin') {
            return 'Super Admin';
        }

        return $roleName ?: 'Pengguna';
    }
}
