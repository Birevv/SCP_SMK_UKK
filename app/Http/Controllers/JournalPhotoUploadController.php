<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JournalPhotoUploadController extends Controller
{
    /**
     * Upload realtime photo taken from camera (base64 or multipart file).
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $filename = 'realtime_'.now()->format('Ymd_His').'_'.Str::random(10).'.jpg';
            $relativeFolder = 'journal_photos';
            $storagePath = $relativeFolder.'/'.$filename;

            if ($request->has('photo_base64') && ! empty($request->input('photo_base64'))) {
                $base64Data = (string) $request->input('photo_base64');

                // Extract mime and data
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                    $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                }

                $decodedData = base64_decode($base64Data, true);
                if ($decodedData === false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Format gambar realtime tidak valid.',
                    ], 422);
                }

                // Max 5MB
                if (strlen($decodedData) > 5 * 1024 * 1024) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ukuran foto melebihi batas maksimal 5MB.',
                    ], 422);
                }

                Storage::disk('public')->put($storagePath, $decodedData);
            } elseif ($request->hasFile('photo')) {
                $request->validate([
                    'photo' => 'required|image|max:5120',
                ]);

                $file = $request->file('photo');
                $storagePath = $file->storeAs($relativeFolder, $filename, 'public');
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data foto yang dikirim.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'path' => $storagePath,
                'url' => Storage::disk('public')->url($storagePath),
                'filename' => $filename,
                'captured_at' => now()->translatedFormat('d M Y, H:i:s'),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses foto: '.$e->getMessage(),
            ], 500);
        }
    }
}
