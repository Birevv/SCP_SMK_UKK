<?php

namespace App\Filament\Resources\Schedules;

use App\Filament\Resources\Schedules\Pages\CreateSchedule;
use App\Filament\Resources\Schedules\Pages\EditSchedule;
use App\Filament\Resources\Schedules\Pages\ListSchedules;
use App\Filament\Resources\Schedules\Schemas\ScheduleForm;
use App\Filament\Resources\Schedules\Tables\SchedulesTable;
use App\Models\Schedule;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static UnitEnum|string|null $navigationGroup = 'Manajemen Akademik';

    protected static ?string $modelLabel = 'Jadwal Mengajar';

    protected static ?string $pluralModelLabel = 'Jadwal Mengajar';

    protected static ?string $navigationLabel = 'Jadwal Mengajar';

    protected static ?string $pluralLabel = 'Jadwal Mengajar';

    public static function form(Schema $schema): Schema
    {
        return ScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchedulesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['teacher', 'classroom.major', 'room', 'subject', 'academicYear']);

        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return $query;
        }

        $isSchoolAdmin = $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';

        if ($isSchoolAdmin) {
            return $query;
        }

        // Siswa hanya melihat jadwal untuk kelasnya sendiri
        if ($user->hasRole('Siswa') || $user->role?->name === 'Siswa') {
            $classIds = $user->classrooms()->pluck('classrooms.id')->toArray();
            if (empty($classIds) && $user->classroom_id) {
                $classIds = [$user->classroom_id];
            }
            if (! empty($classIds)) {
                $query->whereIn('classroom_id', $classIds);
            }

            return $query;
        }

        // Guru / Wali Kelas: hanya melihat jadwal mengajarnya sendiri
        if ($user->hasAnyRole(['Guru', 'Wali Kelas']) || in_array($user->role?->name, ['Guru', 'Wali Kelas'], true)) {
            $query->where('teacher_id', $user->id);

            return $query;
        }

        // Guru BK: jika mengajar mapel tampilkan jadwalnya, jika tidak tampilkan jadwal kelas binaan
        if ($user->hasRole('Guru BK') || $user->role?->name === 'Guru BK') {
            if (Schedule::where('teacher_id', $user->id)->exists()) {
                $query->where('teacher_id', $user->id);
            } else {
                $classIds = $user->classrooms()->pluck('classrooms.id')->toArray();
                if (! empty($classIds)) {
                    $query->whereIn('classroom_id', $classIds);
                }
            }

            return $query;
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchedules::route('/'),
            'create' => CreateSchedule::route('/create'),
            'edit' => EditSchedule::route('/{record}/edit'),
        ];
    }
}
