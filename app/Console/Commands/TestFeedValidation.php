<?php

namespace App\Console\Commands;

use App\Jobs\ProcessFeedJob;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestFeedValidation extends Command
{
    protected $signature   = 'feed:test-validate {--user=1}';
    protected $description = 'Run the feed validator synchronously against the sample CSV';

    public function handle(): int
    {
        // Find or create a test user
        $user = User::first();

        if (! $user) {
            $this->error('No users found. Please log in via the web UI first, then re-run this command.');
            return 1;
        }

        $this->info("Running as user: {$user->name}");

        // Copy sample CSV into the feeds storage
        $samplePath = base_path('storage/app/sample-feed.csv');
        if (! file_exists($samplePath)) {
            $this->error('Sample CSV not found at: ' . $samplePath);
            return 1;
        }

        $storagePath = "feeds/{$user->id}/sample-feed-test.csv";
        Storage::put($storagePath, file_get_contents($samplePath));

        // Create feed record
        $feed = Feed::create([
            'user_id'           => $user->id,
            'name'              => 'Sample Test Feed',
            'original_filename' => 'sample-feed.csv',
            'storage_path'      => $storagePath,
            'mime_type'         => 'text/csv',
            'status'            => 'pending',
        ]);

        $this->info("Feed #{$feed->id} created. Running validator synchronously...");

        // Run synchronously (not via queue) for testing
        dispatch_sync(new ProcessFeedJob($feed));

        $feed->refresh();

        $this->newLine();
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("  ✅  Validation Complete!");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Rows',    $feed->row_count],
                ['Errors',        $feed->error_count],
                ['Warnings',      $feed->warning_count],
                ['Health Score',  $feed->health_score . '%'],
            ]
        );

        $this->newLine();
        $this->line("Row-by-row breakdown:");

        foreach ($feed->rows()->orderBy('row_number')->get() as $row) {
            $icon   = match($row->status) { 'error' => '❌', 'warning' => '⚠️ ', default => '✅' };
            $title  = substr($row->field('title') ?: "Row {$row->row_number}", 0, 45);
            $issues = count($row->issues ?? []);
            $this->line("  {$icon}  Row {$row->row_number}: {$title}" . ($issues ? "  [{$issues} issues]" : "  [clean]"));

            if ($issues > 0) {
                foreach ($row->issues as $issue) {
                    $type = strtoupper($issue['type']);
                    $this->line("        {$type}: [{$issue['field']}] {$issue['message']}");
                }
            }
        }

        $this->newLine();
        $this->info("View it in the browser: http://laravelfeed.test/feeds/{$feed->id}");

        return 0;
    }
}
