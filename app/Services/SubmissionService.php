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
    public function submitTitle(string $studentId, string $title, string $description): array
    {
        try {
            // Pastikan data tidak kosong
            if (trim($title) === '') {
                \Log::warning("Submit title gagal: judul kosong", [
                    'student_id' => $studentId,
                ]);
                throw new \Exception('Judul tidak boleh kosong!');
            }

            // Validasi khusus judul (misalnya sudah dipakai atau tidak)
            if (! $this->isTitleValid($title)) {
                \Log::info("Judul tidak valid atau sudah digunakan", [
                    'student_id' => $studentId,
                    'title' => $title,
                ]);
                throw new \Exception('Judul memiliki kemiripan diatas 70% dengan judul terdahulu!');
            }

            $student = $this->crud
                ->setModel(new Student())
                ->find($studentId);

            // Update data mahasiswa
            $this->crud
                ->setModel(new Student())
                ->update($studentId, [
                    'status' => $student->status == 0 ? 1 : $student->status,
                    'thesis_title' => $title,
                    'thesis_description' => $description,
                ]);

            \Log::info("✅ Judul berhasil disubmit", [
                'student_id' => $studentId,
                'title' => $title,
            ]);

            return [
                'success' => true,
                'message' => 'Judul berhasil disubmit.'
            ];

        } catch (\Throwable $e) {
            \Log::error("Error saat submit title: {$e->getMessage()}", [
                'student_id' => $studentId,
                'title' => $title,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
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
    public function assignSupervisor(string $studentId, string $supervisorId, int $role, string $note = null): array
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

            $exists = SupervisionApplication::where('student_id', $student->id)
                ->whereNot('lecturer_id', $lecturer->id)
                ->where('proposed_role', $role)
                ->where('period_id', $period->id)
                ->where('status', 'accepted')
                ->first();

            if($exists){
                $exists->status = 'changed';
                $exists->save();
            }

            $exists = SupervisionApplication::where('student_id', $student->id)
                ->where('lecturer_id', $lecturer->id)
                ->where('proposed_role', $role)
                ->where('period_id', $period->id)
                ->first();

            if ($exists) {
                if($exists->status == "accepted" or $exists->status == "pending"){
                    throw new \Exception('Pengajuan sudah pernah dibuat sebelumnya');
                }

                $exists->status = "pending";
                $exists->save();
            }
            else{
                $this->crud->setModel(new SupervisionApplication())->create([
                    'period_id' => $period->id,
                    'lecturer_id' => $lecturer->id,
                    'student_id' => $student->id,
                    'proposed_role' => $role,
                    'student_notes' => $note,
                ]);
            }

            return [
                'success' => true,
                'message' => 'Pengajuan berhasil dikirim'
            ];

        } catch (\Throwable $e) {
            \Log::error('Gagal assign supervisor: ' . $e->getMessage(), [
                'student_id' => $studentId,
                'supervisor_id' => $supervisorId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function cancelSupervisor(string $studentId, string $supervisorId, int $role): array
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

            $exists = SupervisionApplication::where('student_id', $student->id)
                ->where('lecturer_id', $lecturer->id)
                ->where('proposed_role', $role)
                ->where('period_id', $period->id)
                ->where('status', 'pending')
                ->first();

            if (!$exists) {
                throw new \Exception('Pengajuan tidak ditemukan');
            }

            $exists->status='canceled';
            $exists->save();


            return [
                'success' => true,
                'message' => 'Pembatalan berhasil'
            ];

        } catch (\Throwable $e) {
            \Log::error('Gagal melakukan pembatalan supervisor: ' . $e->getMessage(), [
                'student_id' => $studentId,
                'supervisor_id' => $supervisorId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}