<?php
/**
 * SIGAP-POLRI: Penyimpanan File Unggahan Privat (Bukti & Lampiran Klarifikasi)
 */

namespace App\Core;

use App\Config\Config;

class Upload
{
    public const MAX_BYTES = 20 * 1024 * 1024; // 20 MB per file

    // Tipe file yang diizinkan (dideteksi dari isi file, bukan ekstensi) => ekstensi simpan
    public const MIME_EXTENSIONS = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'application/pdf' => 'pdf',
        'video/mp4'       => 'mp4',
    ];

    /**
     * Simpan satu file unggahan ke storage privat dengan nama acak.
     * Mengembalikan ['file_path', 'mime', 'size'] atau null bila ditolak.
     *
     * @param string[] $allowedMimes subset dari MIME_EXTENSIONS
     */
    public static function store(string $tmpPath, int $error, int $size, array $allowedMimes): ?array
    {
        if ($error !== UPLOAD_ERR_OK || $size <= 0 || $size > self::MAX_BYTES || !is_uploaded_file($tmpPath)) {
            return null;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath);
        if (!in_array($mime, $allowedMimes, true) || !isset(self::MIME_EXTENSIONS[$mime])) {
            return null;
        }

        $uploadDir = Config::getUploadDir();
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0750, true);
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . self::MIME_EXTENSIONS[$mime];
        if (!move_uploaded_file($tmpPath, $uploadDir . DIRECTORY_SEPARATOR . $fileName)) {
            return null;
        }

        return [
            'file_path' => 'uploads/' . $fileName,
            'mime'      => $mime,
            'size'      => $size,
        ];
    }

    /**
     * Kirim file tersimpan ke browser (inline untuk tipe yang dikenal) lalu hentikan request.
     * Kembali tanpa mengirim apa pun bila file tidak ada (pemanggil merespons 404).
     */
    public static function stream(string $storedPath, string $downloadName): void
    {
        $file = self::absolutePath($storedPath);
        if (!is_file($file)) {
            return;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
        $inline = isset(self::MIME_EXTENSIONS[$mime]);
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName) ?: 'lampiran';

        header('Content-Type: ' . ($inline ? $mime : 'application/octet-stream'));
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $safeName . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($file);
        exit;
    }

    /**
     * Path absolut file tersimpan dari nilai file_path/attachment_path di database
     */
    public static function absolutePath(string $storedPath): string
    {
        return Config::getUploadDir() . DIRECTORY_SEPARATOR . basename($storedPath);
    }
}
