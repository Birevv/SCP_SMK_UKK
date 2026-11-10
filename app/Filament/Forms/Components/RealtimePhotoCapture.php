<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Http\UploadedFile;

class RealtimePhotoCapture extends Field
{
    protected string $view = 'filament.forms.components.realtime-photo-capture';

    protected string $disk = 'public';

    protected string $directory = 'journal_photos';

    protected int $maxSize = 5120; // KB (5MB default for journals)

    protected bool $allowPdf = false;

    protected bool $enableWatermark = true;

    protected string $defaultFacingMode = 'environment';

    protected string $captureType = 'journal'; // 'journal' | 'leave_request'

    protected array $acceptedFileTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrateStateUsing(function ($state) {
            if ($state instanceof UploadedFile) {
                return $state->store($this->getDirectory(), $this->getDisk());
            }

            if (is_array($state)) {
                $first = reset($state);
                if ($first instanceof UploadedFile) {
                    return $first->store($this->getDirectory(), $this->getDisk());
                }

                return is_string($first) ? $first : null;
            }

            return $state;
        });
    }

    public function disk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function getDisk(): string
    {
        return $this->disk;
    }

    public function directory(string $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function maxSize(int $maxSize): static
    {
        $this->maxSize = $maxSize;

        return $this;
    }

    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    public function allowPdf(bool $allow = true): static
    {
        $this->allowPdf = $allow;
        if ($allow && ! in_array('application/pdf', $this->acceptedFileTypes, true)) {
            $this->acceptedFileTypes[] = 'application/pdf';
        }

        return $this;
    }

    public function getAllowPdf(): bool
    {
        return $this->allowPdf;
    }

    public function enableWatermark(bool $enable = true): static
    {
        $this->enableWatermark = $enable;

        return $this;
    }

    public function getEnableWatermark(): bool
    {
        return $this->enableWatermark;
    }

    public function defaultFacingMode(string $mode): static
    {
        $this->defaultFacingMode = $mode;

        return $this;
    }

    public function getDefaultFacingMode(): string
    {
        return $this->defaultFacingMode;
    }

    public function captureType(string $type): static
    {
        $this->captureType = $type;

        return $this;
    }

    public function getCaptureType(): string
    {
        return $this->captureType;
    }

    public function acceptedFileTypes(array $types): static
    {
        $this->acceptedFileTypes = $types;

        return $this;
    }

    public function getAcceptedFileTypes(): array
    {
        return $this->acceptedFileTypes;
    }

    public function image(): static
    {
        return $this;
    }

    public function visibility(string $visibility): static
    {
        return $this;
    }
}
