<?php

namespace App\Http\Requests\Api;

use App\Models\Schedule;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreJournalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin') {
            return true;
        }

        // Guru BK murni (tanpa peran Guru/Wali Kelas) tidak diizinkan membuat jurnal mengajar
        $isPureBk = ($user->hasRole('Guru BK') || $user->role?->name === 'Guru BK')
            && ! $user->hasAnyRole(['Guru', 'Wali Kelas'])
            && ! in_array($user->role?->name, ['Guru', 'Wali Kelas'], true);

        if ($isPureBk) {
            return false;
        }

        return $user->can('Create:Journal')
            || $user->hasAnyRole(['Guru', 'Wali Kelas'])
            || in_array($user->role?->name, ['Guru', 'Wali Kelas'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $isSuperAdmin = $user && ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin');

        return [
            'schedule_id' => [
                'required',
                'integer',
                'exists:schedules,id',
                Rule::unique('journals', 'schedule_id')->where(function ($query) {
                    return $query->whereDate('date', $this->input('date'));
                }),
                function (string $attribute, mixed $value, \Closure $fail) use ($isSuperAdmin): void {
                    if (! $value) {
                        return;
                    }

                    $schedule = Schedule::find($value);
                    if (! $schedule) {
                        return;
                    }

                    $dateInput = $this->input('date');
                    try {
                        $targetDate = filled($dateInput)
                            ? Carbon::parse($dateInput, 'Asia/Jakarta')->startOfDay()
                            : Carbon::today('Asia/Jakarta');
                    } catch (\Throwable) {
                        return;
                    }

                    $expectedDay = Schedule::indonesianDayName($targetDate);
                    if ($expectedDay && $schedule->day_of_week !== $expectedDay) {
                        $fail("Jadwal yang dipilih ({$schedule->day_of_week}) tidak sesuai dengan hari pembelajaran ({$expectedDay}). Jurnal untuk jadwal mendatang atau hari lain tidak dapat dikirim.");

                        return;
                    }

                    if (! $isSuperAdmin) {
                        $activeCycle = SystemConfiguration::current()->active_cycle;
                        if ($activeCycle && $schedule->cycle_type && $schedule->cycle_type !== $activeCycle) {
                            $fail("Jadwal yang dipilih berada pada {$schedule->cycle_type}, sedangkan siklus aktif saat ini adalah {$activeCycle}.");
                        }
                    }
                },
            ],
            'date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($isSuperAdmin): void {
                    if ($isSuperAdmin || ! filled($value)) {
                        return;
                    }

                    try {
                        $journalDate = Carbon::parse($value, 'Asia/Jakarta')->startOfDay();
                    } catch (\Throwable) {
                        return;
                    }

                    $today = Carbon::today('Asia/Jakarta');

                    if ($journalDate->isAfter($today)) {
                        $fail('Jurnal mengajar hanya dapat diisi pada hari pembelajaran (hari ini). Pengisian untuk tanggal mendatang / besok tidak diperbolehkan.');

                        return;
                    }

                    if ($journalDate->isBefore($today)) {
                        $fail('Pengisian jurnal masa lalu tidak diperbolehkan. Jurnal hanya dapat diisi pada hari jadwal mengajar (hari ini).');
                    }
                },
            ],
            'teaching_material' => ['required', 'string', 'max:1000'],
            'notes' => ['required', 'string', 'max:500'],
            'activity_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:3072'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'integer', 'exists:users,id'],
            'attendances.*.status' => ['required', 'in:Hadir,Izin,Sakit,Alpa'],
            'attendances.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'schedule_id.unique' => 'Jurnal untuk jadwal mengajar ini pada tanggal yang dipilih sudah pernah diisi. Setiap jadwal hanya dapat diisi 1 kali per tanggal pembelajaran.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $firstError = $validator->errors()->first();

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $firstError ?: 'Validasi Input Jurnal Dan Presensi Gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
