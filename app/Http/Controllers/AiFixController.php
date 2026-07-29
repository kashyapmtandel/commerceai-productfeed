<?php

namespace App\Http\Controllers;

use App\Models\Feed;
use App\Models\FeedRow;
use App\Services\FeedValidatorService;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class AiFixController extends Controller
{
    public function __construct(
        private readonly GeminiService $gemini,
        private readonly FeedValidatorService $validator,
    ) {}

    public function suggest(Feed $feed, FeedRow $row)
    {
        $this->authorize('view', $feed);

        if ($row->feed_id !== $feed->id) abort(404);

        if (empty($row->issues)) {
            return response()->json(['message' => 'This row has no issues to fix.'], 422);
        }

        $result = $this->gemini->suggestFix($row->data, $row->issues);

        if (isset($result['error'])) {
            $isRateLimit = str_contains($result['error'], 'rate limit');
            return response()->json(
                ['error' => $result['error'], 'rate_limited' => $isRateLimit],
                $isRateLimit ? 429 : 500
            );
        }

        $row->update([
            'ai_suggestion' => $result['suggestion'],
            'ai_fixed_data' => $result['fixed_data'],
        ]);

        return response()->json([
            'suggestion' => $result['suggestion'],
            'fixed_data' => $result['fixed_data'],
        ]);
    }

    public function applyAiFix(Feed $feed, FeedRow $row)
    {
        $this->authorize('view', $feed);

        if ($row->feed_id !== $feed->id) abort(404);

        if (! $row->ai_fixed_data) {
            return response()->json(['message' => 'No AI suggestion available. Request one first.'], 422);
        }

        // Save the fixed data
        $fixedData = $row->ai_fixed_data;
        $row->update(['fixed_data' => $fixedData, 'ai_applied' => true]);

        // Re-validate the fixed data so status + issues reflect the actual state
        $validation = $this->validator->validate($fixedData, $row->row_number);
        $row->update([
            'status' => $validation['status'],
            'issues' => $validation['issues'],
        ]);

        // Recalculate and update the parent feed's counts
        $this->recalculateFeedCounts($feed);

        return response()->json([
            'message'      => 'AI fix applied and row re-validated.',
            'new_status'   => $validation['status'],
            'issue_count'  => count($validation['issues']),
        ]);
    }

    public function manualFix(Request $request, Feed $feed, FeedRow $row)
    {
        $this->authorize('view', $feed);

        if ($row->feed_id !== $feed->id) abort(404);

        $request->validate(['fixed_data' => 'required|array']);

        $merged = array_merge($row->getEffectiveData(), $request->input('fixed_data'));

        // Re-validate merged data
        $validation = $this->validator->validate($merged, $row->row_number);

        $row->update([
            'fixed_data' => $merged,
            'ai_applied' => false,
            'status'     => $validation['status'],
            'issues'     => $validation['issues'],
        ]);

        $this->recalculateFeedCounts($feed);

        return response()->json([
            'message'    => 'Manual fix saved and re-validated.',
            'fixed_data' => $merged,
            'new_status' => $validation['status'],
        ]);
    }

    private function recalculateFeedCounts(Feed $feed): void
    {
        $feed->update([
            'error_count'   => $feed->rows()->where('status', 'error')->count(),
            'warning_count' => $feed->rows()->where('status', 'warning')->count(),
        ]);
    }
}

