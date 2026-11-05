<?php

namespace App\Filament\Resources\Journals\Tables;

use App\Models\Classroom;
use App\Models\Journal;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JournalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Tanggal'),

                TextColumn::make('teacher.full_name')
                    ->searchable()
                    ->sortable()
                    ->label('Guru'),

                TextColumn::make('schedule.classroom.name')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable()
                    ->label('Kelas'),

                TextColumn::make('schedule.classroom.major.code')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable()
                    ->label('Jurusan'),

                TextColumn::make('schedule.subject.name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('teaching_material')
                    ->label('Materi Pembelajaran')
                    ->limit(40)
                    ->tooltip(fn (Journal $record): ?string => $record->teaching_material)
                    ->searchable()
                    ->toggleable(),

                ImageColumn::make('activity_photo')
                    ->disk('public')
                    ->label('Bukti Foto'),

                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat Pada'),

            ])
            ->filters([
                Filter::make('date')
                    ->label('Filter Tanggal')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Tanggal Spesifik'),
                        DatePicker::make('from_date')
                            ->label('Dari Tanggal'),
                        DatePicker::make('until_date')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['date'] ?? null),
                                fn (Builder $q) => $q->whereDate('date', $data['date']),
                            )
                            ->when(
                                filled($data['from_date'] ?? null),
                                fn (Builder $q) => $q->whereDate('date', '>=', $data['from_date']),
                            )
                            ->when(
                                filled($data['until_date'] ?? null),
                                fn (Builder $q) => $q->whereDate('date', '<=', $data['until_date']),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['date'] ?? null)) {
                            $indicators['date'] = 'Tanggal: '.Carbon::parse($data['date'])->translatedFormat('d M Y');
                        }

                        if (filled($data['from_date'] ?? null)) {
                            $indicators['from_date'] = 'Dari: '.Carbon::parse($data['from_date'])->translatedFormat('d M Y');
                        }

                        if (filled($data['until_date'] ?? null)) {
                            $indicators['until_date'] = 'Sampai: '.Carbon::parse($data['until_date'])->translatedFormat('d M Y');
                        }

                        return $indicators;
                    }),

                SelectFilter::make('classroom_id')
                    ->label('Kelas')
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => Classroom::orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('schedule', fn (Builder $q) => $q->where('classroom_id', $data['value']))
                        : $query
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
