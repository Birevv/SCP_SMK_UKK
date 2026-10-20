<?php

namespace App\Filament\Resources\Journals\Schemas;

use App\Models\Schedule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class JournalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('schedule_id')
                    ->relationship(
                        name: 'schedule',
                        titleAttribute: 'id',
                        modifyQueryUsing: function (Builder $query) {
                            $user = Auth::user();
                            if ($user && $user->role?->name === 'Guru') {
                                $query->where('teacher_id', $user->id);
                            }

                            return $query->forActiveCycle()->with(['subject', 'classroom', 'room']);
                        }
                    )
                    ->getOptionLabelFromRecordUsing(fn (Schedule $record) => "{$record->subject?->name} - {$record->classroom?->name} ({$record->day_of_week}, ".substr($record->start_time, 0, 5).' - '.substr($record->end_time, 0, 5).')')
                    ->required()
                    ->label('Jadwal Mengajar')
                    ->preload()
                    ->searchable(),

                Hidden::make('teacher_id')
                    ->default(Auth::id()),

                DatePicker::make('date')
                    ->default(now())
                    ->required()
                    ->label('Tanggal Jurnal'),

                Textarea::make('teaching_material')
                    ->required()
                    ->columnSpanFull()
                    ->label('Materi & Ringkasan Pembelajaran'),

                Textarea::make('notes')
                    ->columnSpanFull()
                    ->label('Catatan Tambahan / Kendala'),

                FileUpload::make('activity_photo')
                    ->image()
                    ->disk('public')
                    ->directory('journal_photos')
                    ->visibility('public')
                    ->maxSize(5120)
                    ->label('Bukti Foto Kegiatan (Realtime)'),
            ]);
    }
}
