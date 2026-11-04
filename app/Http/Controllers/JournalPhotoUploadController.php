<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JournalPhotoUploadController extends Controller
{
    /**
     * Upload photo realtime dari webcam / kamera / file picker.
     */
    public function upload(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Support multipart file upload (dari input file / form-data blob)
        if ($request->hasFile('photo')) {
            $request->validate([
                'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            ]);

            $file = $request->file('photo');
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = 'realtime_'.now()->format('Ymd_His').'_'.Str::random(8).'.'.$extension;
            $path = $file->storeAs('journal_photos', $filename, 'public');

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'filename' => $filename,
                'captured_at' => now()->format('d/m/Y H:i:s'),
                'message' => 'Foto bukti kegiatan berhasil diunggah.',
            ]);
        }

        // Support base64 image (dari canvas snapshot webcam realtime)
        if ($request->filled('photo_base64')) {
            $base64 = (string) $request->input('photo_base64');

            if (! preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format gambar base64 tidak valid.',
                ], 422);
            }

            $extension = strtolower($matches[1]);
            if (! in_array($extension, ['jpeg', 'jpg', 'png', 'webp'], true)) {
                $extension = 'jpg';
            }

            $imageData = substr($base64, strpos($base64, ',') + 1);
            $imageData = base64_decode($imageData);

            if ($imageData === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membaca data gambar base64.',
                ], 422);
            }

            // Validasi ukuran maks 5MB
            if (strlen($imageData) > 5 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ukuran gambar melebihi batas maksimal 5MB.',
                ], 422);
            }

            $filename = 'realtime_'.now()->format('Ymd_His').'_'.Str::random(8).'.'.$extension;
            $path = 'journal_photos/'.$filename;

            Storage::disk('public')->put($path, $imageData);

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'filename' => $filename,
                'captured_at' => now()->format('d/m/Y H:i:s'),
                'message' => 'Foto realtime berhasil diambil dan disimpan.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tidak ada file atau gambar yang dikirim.',
        ], 422);
    }
}
