<?php

namespace App\Services;

use App\Models\Period;
use App\Models\Student;
use App\Models\HistoryProposal;
use App\Models\Lecturer;
use Illuminate\Http\UploadedFile;
use App\Models\SupervisionApplication;

class SubmissionService
{
    protected FileService $fileService;
    protected CrudService $crud;

    public function __construct(FileService $fileService, CrudService $crud)
    {
        $this->fileService = $fileService;
        $this->crud = $crud;
    }

    /**
     * Submit proposal
     * 
     * @param string $studentId Student's Id or User Id
     * @param UploadedFile $file
     */
    public function submitProposal(Student $student, UploadedFile $file, string $description)
    {
        $path = $this->fileService->upload($file, 'proposal', $student, "public");
        $this->crud->setModel(new HistoryProposal())->create([
            'student_id' => $student->id,
            'description' => $description,
            'file_path' => $path
        ]);
        // tambahan logika lain harusnya seperti next step sama notify dosen
        return true;
    }

    /**
     * Submit proposal
     * 
     * @param string $studentNrp Student's Id or User Id
     * @param string $title Thesis title
     */
    public function submitTitle(string $studentId, string $title, string $description): bool
    {
        try {
            // Pastikan data tidak kosong
            if (trim($title) === '' || trim($description) === '') {
                \Log::warning("Submit title gagal: judul atau deskripsi kosong", [
                    'student_id' => $studentId,
                ]);
                return false;
            }

            // Validasi khusus judul (misalnya sudah dipakai atau tidak)
            if (! $this->isTitleValid($title)) {
                \Log::info("Judul tidak valid atau sudah digunakan", [
                    'student_id' => $studentId,
                    'title' => $title,
                ]);
                return false;
            }

            // Update data mahasiswa
            $this->crud
                ->setModel(new Student())
                ->update($studentId, [
                    'status' => 1,
                    'thesis_title' => $title,
                    'thesis_description' => $description,
                ]);

            \Log::info("✅ Judul berhasil disubmit", [
                'student_id' => $studentId,
                'title' => $title,
            ]);

            return true;

        } catch (\Throwable $e) {
            \Log::error("Error saat submit title: {$e->getMessage()}", [
                'student_id' => $studentId,
                'title' => $title,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Logic for checking title
     * 
     * @param string $title Title to be checked
     */
    private function isTitleValid(string $title)
    {
        return True;
        //Logika check disini, returnya boolean
    }

    /**
     * Logic for assign supervisor
     * 
     * @param string $studentId
     * @param string $supervisorId
     * @param int $role
     * @param string $note
     * @param string|null $divisionId
     */
    public function assignSupervisor(string $studentId, string $supervisorId, int $role, string $note, ?string $divisionId = null): bool
    {
        try {
            $student = $this->crud->setModel(new Student())->find($studentId);
            $lecturer = $this->crud->setModel(new Lecturer())->find($supervisorId);

            if (!$student || !$lecturer) {
                throw new \Exception('Data mahasiswa atau dosen tidak ditemukan.');
            }

            $period = $student->periods()
                ->wherePivot('is_active', true)
                ->first();

            if (!$period) {
                throw new \Exception('Tidak ada periode aktif saat ini.');
            }

            $this->crud->setModel(new SupervisionApplication())->create([
                'period_id' => $period->id,
                'lecturer_id' => $lecturer->id,
                'student_id' => $student->id,
                'division_id' => $divisionId,
                'proposed_role' => $role,
                'student_notes' => $note,
            ]);

            return true;

        } catch (\Throwable $e) {
            \Log::error('Gagal assign supervisor: ' . $e->getMessage(), [
                'student_id' => $studentId,
                'supervisor_id' => $supervisorId,
            ]);
            return false;
        }
    }
}