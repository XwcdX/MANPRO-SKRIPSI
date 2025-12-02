<?php

namespace App\Services;

use App\Models\LecturerTopic;
use App\Models\Period;

class TopicService
{
    public function getTopicsForLecturer(string $lecturerId, ?string $search = null, ?string $periodId = null)
    {
        return LecturerTopic::with('period')
            ->where('lecturer_id', $lecturerId)
            ->when($search, function ($query) use ($search) {
                $query->where('topic', 'like', '%' . $search . '%');
            })
            // ->when($periodId, function ($query) use ($periodId) {
            //     $query->where('period_id', $periodId);
            // })
            ->latest();
    }

    public function createTopic(array $data): LecturerTopic
    {
        return LecturerTopic::create($data);
    }

    public function updateTopic(LecturerTopic $topic, array $data): LecturerTopic
    {
        $topic->update($data);
        return $topic;
    }

    public function deleteTopic(LecturerTopic $topic): bool
    {
        return $topic->delete();
    }

    public function toggleAvailability(LecturerTopic $topic): LecturerTopic
    {
        $topic->is_available = !$topic->is_available;
        $topic->save();
        return $topic;
    }

    public function getActivePeriods()
    {
        return Period::notArchived()->orderBy('start_date', 'desc')->get();
    }
}
