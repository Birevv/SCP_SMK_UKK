@props([
    'active' => null,
    'code' => null,
    'count' => null,
])

@php
    $codes = ['H', 'S', 'I', 'A'];

    $resolveActiveClasses = static function (string $letter): string {
        return match ($letter) {
            'H' => 'bg-[#1D4ED8] text-white border-blue-700 font-bold shadow-xs',
            'S', 'I' => 'bg-amber-100 text-amber-900 border-amber-300 font-bold dark:bg-amber-950 dark:text-amber-200 dark:border-amber-700',
            'A' => 'bg-red-100 text-red-900 border-red-300 font-bold dark:bg-red-950 dark:text-red-200 dark:border-red-700',
            default => 'bg-slate-100 text-slate-800 border-slate-300 font-medium dark:bg-slate-800 dark:text-slate-200 dark:border-slate-600',
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
                    : 'bg-white text-slate-400 border-slate-300 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-600 font-medium';
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

