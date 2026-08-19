<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileSecurityValidator
{
    private const ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'pdf', 'doc', 'docx', 'txt', 'csv', 'zip'];
    private const DANGEROUS_EXTENSIONS = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'phar', 'exe', 'bat', 'cmd', 'sh', 'js', 'html', 'htm', 'svg', 'cgi', 'pl', 'py', 'htaccess', 'asp', 'aspx', 'jsp'];

    /**
     * Validate an UploadedFile instance (multipart/form-data).
     */
    public static function validateUploadedFile(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (empty($extension) || in_array($extension, self::DANGEROUS_EXTENSIONS, true)) {
            return false;
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return false;
        }

        // Check file content magic bytes / inspection
        $realPath = $file->getRealPath();
        if (!$realPath || !file_exists($realPath)) {
            return false;
        }

        $content = file_get_contents($realPath, false, null, 0, 512);
        return self::validateFileContent($content, $extension);
    }

    /**
     * Validate raw content decoded from Base64 or binary.
     */
    public static function validateRawFileContent(string $content, string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (empty($extension) || in_array($extension, self::DANGEROUS_EXTENSIONS, true)) {
            return false;
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return false;
        }

        return self::validateFileContent($content, $extension);
    }

    private static function validateFileContent(string $content, string $extension): bool
    {
        // Reject PHP scripts or script tags embedded in files
        if (preg_match('/<\?php/i', $content) || preg_match('/<script/i', $content)) {
            return false;
        }

        // Magic byte checks
        return match ($extension) {
            'png' => str_starts_with($content, "\x89PNG\r\n\x1a\n"),
            'jpg', 'jpeg' => str_starts_with($content, "\xFF\xD8\xFF"),
            'gif' => str_starts_with($content, "GIF87a") || str_starts_with($content, "GIF89a"),
            'pdf' => str_starts_with($content, "%PDF-"),
            'zip', 'docx' => str_starts_with($content, "PK\x03\x04") || str_starts_with($content, "PK\x05\x06"),
            'txt', 'csv', 'doc' => true,
            default => false,
        };
    }
}
