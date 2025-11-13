<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
    public function upload(UploadedFile $file, string $path, Student $student, string $disk = 'public'): string
    {
        // Ambil ekstensi file
        $extension = $file->getClientOriginalExtension();

        // Buat nama file custom: studentID_timestamp.ext
        $filename = $student->name . '_proposal_' . time() . '.' . $extension;

        // Simpan file
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