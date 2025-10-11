<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class FileService
{
    /**
     * Upload file ke storage.
     *
     * @param UploadedFile $file
     * @param string $path
     * @param string $filename
     * @param string $disk public or local
     * @return string $filePath
     */
    public function upload(UploadedFile $file, string $path, string $filename, string $disk = 'public'): string
    {
        $filePath = $file->storeAs($path, $filename, $disk);

        return $filePath;
    }

    /**
     * Hapus file dari storage.
     *
     * @param string $path
     * @param string $disk public or local
     * @return bool
     */
    public function delete(string $path, string $disk = 'public'): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }
}