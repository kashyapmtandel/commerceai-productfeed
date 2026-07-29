<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessFeedJob;
use App\Models\Feed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeedController extends Controller
{
    public function index()
    {
        $feeds = Auth::user()->feeds()->latest()->paginate(15);
        return view('dashboard', compact('feeds'));
    }

    public function show(Feed $feed)
    {
        $this->authorize('view', $feed);

        $rows = $feed->rows()
            ->orderByRaw("CASE status WHEN 'error' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->orderBy('row_number')
            ->paginate(50);

        return view('feeds.show', compact('feed', 'rows'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimes:csv,txt,xml,tsv'],
            'name' => 'nullable|string|max:255',
        ]);

        $file     = $request->file('file');
        $mimeType = $file->getMimeType() ?? 'text/csv';
        $path     = $file->store('feeds/' . Auth::id(), 'local');

        $feed = Feed::create([
            'user_id'           => Auth::id(),
            'name'              => $request->input('name') ?: $file->getClientOriginalName(),
            'original_filename' => $file->getClientOriginalName(),
            'storage_path'      => $path,
            'mime_type'         => $mimeType,
            'status'            => 'pending',
        ]);

        ProcessFeedJob::dispatch($feed);

        return redirect()->route('feeds.show', $feed)
            ->with('success', 'Feed uploaded! Processing has started in the background.');
    }

    public function status(Feed $feed)
    {
        $this->authorize('view', $feed);

        return response()->json([
            'status'        => $feed->status,
            'row_count'     => $feed->row_count,
            'error_count'   => $feed->error_count,
            'warning_count' => $feed->warning_count,
            'health_score'  => $feed->health_score,
        ]);
    }

    public function export(Feed $feed): StreamedResponse
    {
        $this->authorize('view', $feed);

        $filename = 'cleaned-' . str_replace(' ', '-', $feed->name) . '.csv';

        return response()->streamDownload(function () use ($feed) {
            $handle = fopen('php://output', 'w');
            $first  = true;

            $feed->rows()->orderBy('row_number')->chunk(200, function ($rows) use ($handle, &$first) {
                foreach ($rows as $row) {
                    $data = $row->getEffectiveData();
                    if ($first) {
                        fputcsv($handle, array_keys($data));
                        $first = false;
                    }
                    fputcsv($handle, array_values($data));
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function destroy(Feed $feed)
    {
        $this->authorize('delete', $feed);
        Storage::disk('local')->delete($feed->storage_path);
        $feed->delete();

        return redirect()->route('dashboard')->with('success', 'Feed deleted.');
    }
}
