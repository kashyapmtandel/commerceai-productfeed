<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('services.gemini.key');
        $this->model   = config('services.gemini.model', 'gemini-2.0-flash');
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    }

    public function suggestFix(array $rowData, array $issues): array
    {
        $issueText = collect($issues)
            ->map(fn($i) => "- [{$i['type']}] {$i['field']}: {$i['message']}")
            ->join("\n");

        $rowJson = json_encode($rowData, JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
You are an expert in Google Merchant Center product feed optimization.

I have a product row from a product feed that has the following validation issues:

{$issueText}

Here is the current product data (JSON):
```json
{$rowJson}
```

Please:
1. Fix all the validation issues listed above.
2. Return ONLY a valid JSON object with the corrected field values.
3. Do not include any explanation or markdown — just the raw JSON object.
4. Only include fields that need to be fixed or are already present in the original data.
5. For the `price` field, ensure format is "X.XX USD" (or appropriate currency).
6. For `availability`, use one of: in stock, out of stock, preorder, backorder.
7. For URLs, ensure they start with https://.

Return ONLY the corrected JSON object with no extra text.
PROMPT;

        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents'          => [['parts' => [['text' => $prompt]]]],
                    'generationConfig'  => [
                        'temperature'     => 0.2,
                        'maxOutputTokens' => 1024,
                    ],
                ]);

            if (! $response->successful()) {
                $status  = $response->status();
                $message = match($status) {
                    429     => 'Gemini rate limit reached. You are on the free tier (15 req/min). Please wait 60 seconds and try again.',
                    401, 403 => 'Invalid Gemini API key. Please check GEMINI_API_KEY in your .env file.',
                    default  => "Gemini API error (HTTP {$status}). Check your API key and quota.",
                };
                Log::error('Gemini API error', ['status' => $status, 'body' => $response->body()]);
                return $this->errorResult($message);
            }

            $text = $response->json('candidates.0.content.parts.0.text', '');
            $text = preg_replace('/^```(?:json)?\n?/m', '', $text);
            $text = preg_replace('/```$/m', '', $text);
            $text = trim($text);

            $fixedData = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Gemini returned non-JSON', ['text' => $text]);
                return ['suggestion' => $text, 'fixed_data' => null];
            }

            return [
                'suggestion' => $text,
                'fixed_data' => array_merge($rowData, $fixedData),
            ];

        } catch (\Throwable $e) {
            Log::error('Gemini request failed', ['error' => $e->getMessage()]);
            return $this->errorResult($e->getMessage());
        }
    }

    private function errorResult(string $message): array
    {
        return ['suggestion' => null, 'fixed_data' => null, 'error' => $message];
    }
}
