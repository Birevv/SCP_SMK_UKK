<?php

namespace App\Filament\Resources\Journals\Schemas;

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
                    ->relationship('schedule', 'subject_name', function (Builder $query) {
                        /** @var \Illuminate\Database\Eloquent\Builder|\App\Models\Schedule $query */
                        $user = Auth::user();
                        if ($user && $user->role?->name === 'Guru') {
                            $query->where('teacher_id', $user->id);
                        }
                        return $query->forActiveCycle();
                    })
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
                    ->directory('journal-photos')
                    ->label('Bukti Foto Kegiatan (Realtime)'),
            ]);
    }
}

