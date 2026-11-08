@props([
    'active' => null,
    'code' => null,
    'count' => null,
])

@php
    $codes = ['H', 'S', 'I', 'A'];

    $resolveActiveClasses = static function (string $letter): string {
        return match ($letter) {
            'H' => 'bg-accent text-on-accent border-accent font-medium',
            'S', 'I' => 'bg-note-tint text-note border-note/30 font-medium',
            'A' => 'bg-danger/10 text-danger border-danger/30 font-medium',
            default => 'bg-raised text-ink border-hairline-strong font-medium',
        };
    };
@endphp

@if ($active !== null)
    <div {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-xs tabular-nums select-none']) }} role="group" aria-label="Status presensi {{ $active }}">
        @foreach ($codes as $letter)
            @php
                $isCurrent = strtoupper((string) $active) === $letter;
                $boxClasses = $isCurrent
                    ? $resolveActiveClasses($letter)
                    : 'bg-transparent text-ink-muted border-hairline';
            @endphp
            <span
                class="inline-flex items-center justify-center w-6 h-6 rounded border leading-none {{ $boxClasses }}"
                @if ($isCurrent) aria-current="true" @endif
            >{{ $letter }}</span>
        @endforeach
    </div>
@elseif ($code !== null)
    @php
        $upperCode = strtoupper((string) $code);
        $singleClasses = $resolveActiveClasses($upperCode);
    @endphp
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-xs tabular-nums']) }}>
        <span class="inline-flex items-center justify-center w-5 h-5 rounded border leading-none {{ $singleClasses }}">{{ $upperCode }}</span>
        @if ($count !== null)
            <span class="text-ink-secondary">{{ $count }}</span>
        @endif
    </span>
@endif

