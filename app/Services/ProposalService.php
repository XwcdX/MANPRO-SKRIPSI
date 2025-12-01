<?php

namespace App\Services;

use App\Models\HistoryProposal;
use App\Models\HistoryThesis;
use Illuminate\Support\Facades\DB;

class ProposalService
{
    public function acceptProposal(string $proposalId, string $divisionId, ?string $comment = null): array
    {
        return DB::transaction(function () use ($proposalId, $divisionId, $comment) {
            $proposal = HistoryProposal::with('student')->findOrFail($proposalId);
            
            $newStatus = $proposal->status == 0 ? 2 : 3;
            
            $proposal->update([
                'status' => $newStatus,
                'division_id' => $divisionId,
                'comment' => $comment,
            ]);

            if (!$proposal->student->division_id) {
                $proposal->student->update(['division_id' => $divisionId]);
            }

            return $proposal->toArray();
        });
    }

    public function declineProposal(string $proposalId, string $comment): array
    {
        return DB::transaction(function () use ($proposalId, $comment) {
            $proposal = HistoryProposal::findOrFail($proposalId);
            
            $proposal->update([
                'status' => 1,
                'comment' => $comment,
            ]);

            return $proposal->toArray();
        });
    }

    public function acceptThesis(string $thesisId, ?string $comment = null): array
    {
        return DB::transaction(function () use ($thesisId, $comment) {
            $thesis = HistoryThesis::with('student')->findOrFail($thesisId);
            
            $newStatus = $thesis->status == 0 ? 2 : 3;
            
            $thesis->update([
                'status' => $newStatus,
                'division_id' => $thesis->student->division_id,
                'comment' => $comment,
            ]);

            return $thesis->toArray();
        });
    }

    public function declineThesis(string $thesisId, string $comment): array
    {
        return DB::transaction(function () use ($thesisId, $comment) {
            $thesis = HistoryThesis::findOrFail($thesisId);
            
            $thesis->update([
                'status' => 1,
                'comment' => $comment,
            ]);

            return $thesis->toArray();
        });
    }
}
