<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JournalPhotoUploadController extends Controller
{
    /**
     * Upload realtime photo taken from camera or file picker (base64 or multipart file).
     * Supports both journal photos (up to 5MB) and leave request attachments (up to 2MB).
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $folder = $request->input('folder', 'journal_photos');
            if (! in_array($folder, ['journal_photos', 'leave_attachments'], true)) {
                $folder = 'journal_photos';
            }

            $isLeaveAttachment = $folder === 'leave_attachments';
            $maxBytes = $isLeaveAttachment ? (2048 * 1024) : (5120 * 1024);
            $maxKb = $isLeaveAttachment ? 2048 : 5120;
            $prefix = $isLeaveAttachment ? 'leave_doc_' : 'realtime_';

            $filePayload = $request->file('photo')
                ?? $request->file('attachment_photo')
                ?? $request->file('file');

            if ($request->has('photo_base64') && ! empty($request->input('photo_base64'))) {
                $base64Data = (string) $request->input('photo_base64');
                $extension = 'jpg';

                // Extract mime and data
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                    $mimeExt = strtolower($type[1]);
                    if (in_array($mimeExt, ['png', 'webp', 'jpeg', 'jpg'], true)) {
                        $extension = $mimeExt === 'jpeg' ? 'jpg' : $mimeExt;
                    }
                    $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                }

                $decodedData = base64_decode($base64Data, true);
                if ($decodedData === false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Format gambar realtime tidak valid.',
                    ], 422);
                }

                $size = strlen($decodedData);
                if ($size > $maxBytes) {
                    return response()->json([
                        'success' => false,
                        'message' => "Ukuran berkas ({$this->formatBytes($size)}) melebihi batas maksimal {$maxKb} KB.",
                    ], 422);
                }

                $filename = $prefix.now()->format('Ymd_His').'_'.Str::random(10).'.'.$extension;
                $storagePath = $folder.'/'.$filename;

                Storage::disk('public')->put($storagePath, $decodedData);
                $mimeType = 'image/'.($extension === 'jpg' ? 'jpeg' : $extension);
            } elseif ($filePayload) {
                $allowedMimes = $isLeaveAttachment
                    ? ['jpg', 'jpeg', 'png', 'webp', 'pdf']
                    : ['jpg', 'jpeg', 'png', 'webp'];

                $request->validate([
                    'photo' => 'sometimes|file|mimes:'.implode(',', $allowedMimes).'|max:'.$maxKb,
                    'attachment_photo' => 'sometimes|file|mimes:'.implode(',', $allowedMimes).'|max:'.$maxKb,
                    'file' => 'sometimes|file|mimes:'.implode(',', $allowedMimes).'|max:'.$maxKb,
                ], [
                    'mimes' => 'Format berkas harus berupa '.strtoupper(implode(', ', $allowedMimes)).'.',
                    'max' => "Ukuran berkas maksimal {$maxKb} KB.",
                ]);

                $extension = strtolower($filePayload->getClientOriginalExtension() ?: 'jpg');
                $filename = $prefix.now()->format('Ymd_His').'_'.Str::random(10).'.'.$extension;
                $storagePath = $filePayload->storeAs($folder, $filename, 'public');
                $size = $filePayload->getSize();
                $mimeType = $filePayload->getMimeType() ?: 'image/jpeg';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data foto atau berkas yang dikirim.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'path' => $storagePath,
                'url' => Storage::disk('public')->url($storagePath),
                'filename' => $filename,
                'size' => $size,
                'size_formatted' => $this->formatBytes($size),
                'mime' => $mimeType,
                'captured_at' => now()->translatedFormat('d M Y, H:i:s'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->validator->errors()->first(),
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses berkas: '.$e->getMessage(),
            ], 500);
        }
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }
}
