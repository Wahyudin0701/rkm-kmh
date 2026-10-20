<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageCompressionService
{
    /**
     * Kompresi dan simpan foto ke disk publik dengan optimasi resolusi & ukuran file.
     *
     * @param UploadedFile $file File yang diunggah dari form request
     * @param string $directory Direktori penyimpanan (misal: 'vehicles', 'employees')
     * @param int $maxWidth Lebar maksimal gambar dalam pixel (default: 800)
     * @param int $maxHeight Tinggi maksimal gambar dalam pixel (default: 800)
     * @param int $quality Kualitas kompresi JPEG 1-100 (default: 75)
     * @return string Path relatif file yang tersimpan di disk public
     */
    public static function uploadAndCompress(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 800,
        int $maxHeight = 800,
        int $quality = 75
    ): string {
        $cleanDir = trim($directory, '/');

        // Jika ekstensi GD tidak aktif, fallback simpan langsung
        if (!extension_loaded('gd')) {
            $fallbackName = Str::random(40) . '.' . ($file->guessExtension() ?: 'jpg');
            return $file->storeAs($cleanDir, $fallbackName, 'public');
        }

        try {
            $realPath = $file->getRealPath();
            $imageContent = file_get_contents($realPath);
            if ($imageContent === false) {
                $fallbackName = Str::random(40) . '.' . ($file->guessExtension() ?: 'jpg');
                return $file->storeAs($cleanDir, $fallbackName, 'public');
            }

            // Buat resource gambar dari data string
            $srcImage = @imagecreatefromstring($imageContent);
            if (!$srcImage) {
                $fallbackName = Str::random(40) . '.' . ($file->guessExtension() ?: 'jpg');
                return $file->storeAs($cleanDir, $fallbackName, 'public');
            }

            // 1. Tangani Auto-Orientation berdasarkan EXIF (kamera smartphone)
            if (function_exists('exif_read_data')) {
                $exif = @exif_read_data($realPath);
                if (!empty($exif['Orientation'])) {
                    $srcImage = match ($exif['Orientation']) {
                        3 => imagerotate($srcImage, 180, 0),
                        6 => imagerotate($srcImage, -90, 0),
                        8 => imagerotate($srcImage, 90, 0),
                        default => $srcImage,
                    };
                }
            }

            // 2. Hitung Dimensi Proporsional
            $origWidth = imagesx($srcImage);
            $origHeight = imagesy($srcImage);

            if ($origWidth > $maxWidth || $origHeight > $maxHeight) {
                $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
                $targetWidth = max(1, (int) round($origWidth * $ratio));
                $targetHeight = max(1, (int) round($origHeight * $ratio));
            } else {
                $targetWidth = $origWidth;
                $targetHeight = $origHeight;
            }

            // 3. Buat Canvas Truecolor Baru
            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

            // Beri latar belakang putih solid jika ada area transparan (PNG/WebP)
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $white);

            // 4. Salin & Resample Gambar dengan Kualitas Tinggi
            imagecopyresampled(
                $canvas,
                $srcImage,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $origWidth,
                $origHeight
            );

            // 5. Simpan Hasil Kompresi Gambar ke Buffer Memori sesuai format yang didukung
            $compressedStream = null;
            $extension = 'jpg';

            if (function_exists('imagejpeg')) {
                $extension = 'jpg';
                ob_start();
                imagejpeg($canvas, null, $quality);
                $compressedStream = ob_get_clean();
            } elseif (function_exists('imagewebp')) {
                $extension = 'webp';
                ob_start();
                imagewebp($canvas, null, $quality);
                $compressedStream = ob_get_clean();
            } elseif (function_exists('imagepng')) {
                $extension = 'png';
                $pngLevel = max(0, min(9, (int) round((100 - $quality) / 10)));
                ob_start();
                imagepng($canvas, null, $pngLevel);
                $compressedStream = ob_get_clean();
            }

            // Bersihkan resource GD dari memori
            imagedestroy($canvas);
            imagedestroy($srcImage);

            if (!empty($compressedStream)) {
                $fileName = Str::random(40) . '.' . $extension;
                $targetPath = $cleanDir . '/' . $fileName;
                Storage::disk('public')->put($targetPath, $compressedStream);
                return $targetPath;
            }
        } catch (\Throwable $e) {
            // Jika terjadi kesalahan proses GD, fallback aman simpan file asli
        }

        $fallbackName = Str::random(40) . '.' . ($file->guessExtension() ?: 'jpg');
        return $file->storeAs($cleanDir, $fallbackName, 'public');
    }

    /**
     * Hapus file foto lama dari disk publik jika ada.
     *
     * @param string|null $path
     * @return void
     */
    public static function deleteIfExists(?string $path): void
    {
        if (!empty($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
