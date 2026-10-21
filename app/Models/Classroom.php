<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'class_level_id',
        'major_id',
        'rombel_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (Classroom $classroom) {
            // Auto parse name if relationships are empty but name is provided
            if (empty($classroom->class_level_id) || empty($classroom->major_id) || empty($classroom->rombel_id)) {
                $classroom->syncRelationsFromName();
            }

            // Auto compose name if relationships are present but name is empty
            if (empty($classroom->name) && $classroom->class_level_id && $classroom->major_id && $classroom->rombel_id) {
                $level = ClassLevel::find($classroom->class_level_id)?->name;
                $major = Major::find($classroom->major_id)?->code;
                $rombel = Rombel::find($classroom->rombel_id)?->name;

                if ($level && $major && $rombel) {
                    $classroom->name = "{$level} {$major} {$rombel}";
                }
            }
        });
    }

    public function syncRelationsFromName(): void
    {
        if (empty($this->name)) {
            return;
        }

        // Example match: "XII RPL 2" or "X AKL 1" or "10 RPL 1"
        if (preg_match('/^(XII|XI|X|12|11|10|Lulus)\s+([A-Za-z]+)\s*(\d+)?$/i', trim($this->name), $matches)) {
            $rawLevel = strtoupper($matches[1]);
            $rawMajor = strtoupper($matches[2]);
            $rawRombel = $matches[3] ?? '1';

            // Normalize level (e.g. 10 -> X, 11 -> XI, 12 -> XII)
            $normalizedLevel = match ($rawLevel) {
                '10' => 'X',
                '11' => 'XI',
                '12' => 'XII',
                default => $rawLevel,
            };

            if (empty($this->class_level_id)) {
                $classLevel = ClassLevel::firstOrCreate(
                    ['name' => $normalizedLevel],
                    [
                        'level' => match ($normalizedLevel) {
                            'X' => 10,
                            'XI' => 11,
                            'XII' => 12,
                            default => 0,
                        },
                        'description' => "Kelas {$normalizedLevel}",
                    ]
                );
                $this->class_level_id = $classLevel->id;
            }

            if (empty($this->major_id)) {
                $major = Major::firstOrCreate(
                    ['code' => $rawMajor],
                    ['name' => $rawMajor]
                );
                $this->major_id = $major->id;
            }

            if (empty($this->rombel_id)) {
                $rombel = Rombel::firstOrCreate(
                    ['name' => (string) $rawRombel],
                    ['number' => (int) $rawRombel, 'description' => "Rombel {$rawRombel}"]
                );
                $this->rombel_id = $rombel->id;
            }
        }
    }

    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_class');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function scopeForGrade(Builder $query, string|int $grade): Builder
    {
        $grade = strtoupper(trim((string) $grade));

        // First check relationship if class_level_id is populated
        $levelNames = match ($grade) {
            '10', 'X' => ['X', '10'],
            '11', 'XI' => ['XI', '11'],
            '12', 'XII' => ['XII', '12'],
            'LULUS' => ['Lulus'],
            default => [$grade],
        };

        return $query->where(function (Builder $q) use ($levelNames, $grade) {
            $q->whereHas('classLevel', fn ($sub) => $sub->whereIn('name', $levelNames))
                ->orWhere(function ($sub) use ($grade) {
                    if (in_array($grade, ['10', 'X'], true)) {
                        $sub->where(function ($s) {
                            $s->where('name', 'like', 'X %')
                                ->orWhere('name', 'like', '10 %')
                                ->orWhere('name', 'like', 'X-%')
                                ->orWhere('name', 'like', '10-%')
                                ->orWhere('name', '=', 'X')
                                ->orWhere('name', '=', '10');
                        })->where('name', 'not like', 'XI%');
                    } elseif (in_array($grade, ['11', 'XI'], true)) {
                        $sub->where(function ($s) {
                            $s->where('name', 'like', 'XI %')
                                ->orWhere('name', 'like', '11 %')
                                ->orWhere('name', 'like', 'XI-%')
                                ->orWhere('name', 'like', '11-%')
                                ->orWhere('name', '=', 'XI')
                                ->orWhere('name', '=', '11');
                        })->where('name', 'not like', 'XII%');
                    } elseif (in_array($grade, ['12', 'XII'], true)) {
                        $sub->where(function ($s) {
                            $s->where('name', 'like', 'XII %')
                                ->orWhere('name', 'like', '12 %')
                                ->orWhere('name', 'like', 'XII-%')
                                ->orWhere('name', 'like', '12-%')
                                ->orWhere('name', '=', 'XII')
                                ->orWhere('name', '=', '12');
                        });
                    } elseif ($grade === 'LULUS') {
                        $sub->where('name', 'like', '%Lulus%');
                    }
                });
        });
    }

    public function scopeForMajor(Builder $query, string|int $major): Builder
    {
        if (is_numeric($major)) {
            return $query->where('major_id', $major);
        }

        $code = strtoupper(trim((string) $major));

        return $query->where(function (Builder $q) use ($code) {
            $q->whereHas('major', fn ($m) => $m->where('code', $code))
                ->orWhere('name', 'like', "% {$code} %")
                ->orWhere('name', 'like', "% {$code}");
        });
    }

    public function scopeForRombel(Builder $query, string|int $rombel): Builder
    {
        if (is_numeric($rombel) && $this->where('rombel_id', $rombel)->exists()) {
            return $query->where('rombel_id', $rombel);
        }

        $r = (string) $rombel;

        return $query->where(function (Builder $q) use ($r) {
            $q->whereHas('rombel', fn ($sub) => $sub->where('name', $r))
                ->orWhere('name', 'like', "% {$r}");
        });
    }
}
