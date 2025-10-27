<?php

namespace App\Services;

use App\Models\Period;
use App\Models\Student;
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
    public function submitProposal(string $studentId, UploadedFile $file)
    {
        $path = $this->fileService->upload($file, 'proposal', $studentId, "local");
        // tambahan logika lain harusnya seperti next step sama notify dosen
        return $path;
    }

    /**
     * Submit proposal
     * 
     * @param string $studentNrp Student's Id or User Id
     * @param string $title Thesis title
     */
    public function submitTitle(string $studentId, string $title, string $description)
    {
        if($this->isTitleValid($title)){
            $this->crud->setModel(new Student())->update($studentId, [
                'status' => 1,
                'thesis_title' => $title,
                'thesis_description' => $description,
            ]);
            return true;
        }

        return false;
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
     * @param string $supervisor1Id
     * @param string $supervisor2Id
     * @param string $notes
     */
    public function assignSupervisor(string $studentId, string $supervisor1Id, string $supervisor2Id, string $notes)
    {
        $student = $this->crud->setModel(new Student())->find($studentId);
        $supervisor1 = null;
        $supervisor2 = null;

        if($supervisor1Id){
            $supervisor1 = $this->crud->setModel(new Lecturer())->find($supervisor1Id);
        }
        if($supervisor2Id){
            $supervisor2 = $this->crud->setModel(new Lecturer())->find($supervisor2Id);
        }

        $period = $this->crud->setModel(new Period())->getModel()->where('is_active', true);

        if($supervisor1){
            $this->crud->setModel(new SupervisionApplication())->create([
                'period_id' => $period->id,
                'lecturer_id' => $lecturerId,
                'proposed_role' => 0,
                'student_notes' => $notes,
            ]);
        }
    }
}