<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\Models\Schedule;
use App\Rules\NoScheduleConflict;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('teacher_id')
                    ->relationship(
                        name: 'teacher',
                        titleAttribute: 'full_name',
                        modifyQueryUsing: fn ($query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Guru'))
                    )
                    ->default(fn () => Auth::user()?->role?->name === 'Guru' ? Auth::id() : null)
                    ->disabled(fn () => Auth::user()?->role?->name === 'Guru')
                    ->dehydrated()
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Guru Pengajar'),

                Select::make('classroom_id')
                    ->relationship('classroom', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Kelas'),

                Select::make('room_id')
                    ->relationship('room', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Ruangan'),

                Select::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->relationship('academicYear', 'name')
                    ->required(),

                Select::make('subject_id')
                    ->label('Mata Pelajaran')
                    ->relationship('subject', 'name')
                    ->required()
                    ->rules([
                        fn (Get $get, ?Schedule $record): NoScheduleConflict => new NoScheduleConflict(
                            dayOfWeek: (string) $get('day_of_week'),
                            startTime: (string) $get('start_time'),
                            endTime: (string) $get('end_time'),
                            cycleType: (string) $get ('cycle_type'),
                            classroomId: $get('classroom_id') ? (int) $get('classroom_id') : null,
                            roomId: $get('room_id') ? (int) $get('room_id') : null,
                            teacherId: $get('teacher_id') ? (int) $get('teacher_id') : null,
                            ignoreId: $record?->id
                        ),
                    ]),
                Select::make('day_of_week')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                    ])
                    ->required()
                    ->label('Hari'),
                
                TimePicker::make('start_time')
                    ->required()
                    ->seconds(false)
                    ->label('Jam Mulai'),

                TimePicker::make('end_time')
                    ->after('start_time')
                    ->required()
                    ->seconds(false)
                    ->label('Jam Selesai'),
                
                Select::make('cycle_type')
                    ->options([
                        'Putaran 1' => 'Putaran 1',
                        'Putaran 2' => 'Putaran 2',
                    ])
                    ->default('Putaran 1')
                    ->required()
                    ->label('Putaran Siklus'), 
            ]);
    }
}
