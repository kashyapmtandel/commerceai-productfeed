<?php

namespace App\Jobs;

use App\Models\Feed;
use App\Models\FeedRow;
use App\Services\FeedValidatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 2;

    public function __construct(public readonly Feed $feed) {}

    public function handle(FeedValidatorService $validator): void
    {
        $this->feed->update(['status' => 'processing']);

        try {
            $content = Storage::get($this->feed->storage_path);

            if ($content === null) {
                throw new \RuntimeException("Feed file not found at: {$this->feed->storage_path}");
            }

            $rows = $this->parseFile($content, $this->feed->mime_type);

            $rowCount = $errorCount = $warningCount = 0;

            $this->feed->rows()->delete();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 1;
                $result    = $validator->validate($row, $rowNumber);

                FeedRow::create([
                    'feed_id'    => $this->feed->id,
                    'row_number' => $rowNumber,
                    'data'       => $row,
                    'status'     => $result['status'],
                    'issues'     => $result['issues'],
                ]);

                $rowCount++;
                match ($result['status']) {
                    'error'   => $errorCount++,
                    'warning' => $warningCount++,
                    default   => null,
                };
            }

            $this->feed->update([
                'status'        => 'done',
                'row_count'     => $rowCount,
                'error_count'   => $errorCount,
                'warning_count' => $warningCount,
            ]);

        } catch (\Throwable $e) {
            $this->feed->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function parseFile(string $content, string $mimeType): array
    {
        return match (true) {
            str_contains($mimeType, 'xml')                                      => $this->parseXml($content),
            str_contains($mimeType, 'tab') || str_contains($mimeType, 'tsv')   => $this->parseCsv($content, "\t"),
            default                                                              => $this->parseCsv($content, ','),
        };
    }

    private function parseCsv(string $content, string $separator = ','): array
    {
        $lines = explode("\n", str_replace("\r\n", "\n", trim($content)));

        if (count($lines) < 2) {
            throw new \RuntimeException('Feed file has no data rows (only a header or is empty).');
        }

        $header = array_map('trim', str_getcsv(array_shift($lines), $separator));
        $rows   = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            $values = str_getcsv($line, $separator);
            while (count($values) < count($header)) $values[] = '';
            $values = array_slice($values, 0, count($header));
            $rows[] = array_combine($header, $values);
        }

        return $rows;
    }

    private function parseXml(string $content): array
    {
        $xml   = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
        $rows  = [];
        $items = $xml->channel->item ?? $xml->item ?? [];

        foreach ($items as $item) {
            $row = [];
            $gNs = $item->children('http://base.google.com/ns/1.0');
            foreach ($gNs as $key => $value) {
                $row[$key] = (string) $value;
            }
            foreach ($item as $key => $value) {
                if (! isset($row[$key])) $row[$key] = (string) $value;
            }
            $row['link']  = $row['link']  ?? $row['url']     ?? '';
            $row['id']    = $row['id']    ?? $row['item_id'] ?? $row['sku'] ?? '';
            $row['price'] = $row['price'] ?? $row['Price']   ?? '';
            $rows[] = $row;
        }

        return $rows;
    }
}
