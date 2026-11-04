<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class RealtimePhotoCapture extends Field
{
    protected string $view = 'filament.forms.components.realtime-photo-capture';

    protected string $disk = 'public';

    protected string $directory = 'journal_photos';

    protected int $maxSize = 5120; // 5MB

    protected ?string $imagePreviewHeight = '300px';

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

    public function image(): static
    {
        return $this;
    }

    public function visibility(string $visibility): static
    {
        return $this;
    }

    public function acceptedFileTypes(array $types): static
    {
        return $this;
    }

    public function imagePreviewHeight(?string $height): static
    {
        $this->imagePreviewHeight = $height;

        return $this;
    }

    public function getImagePreviewHeight(): ?string
    {
        return $this->imagePreviewHeight;
    }
}

