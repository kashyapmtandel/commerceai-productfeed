<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedRow extends Model
{
    protected $fillable = [
        'feed_id', 'row_number', 'data', 'fixed_data', 'status',
        'issues', 'ai_suggestion', 'ai_fixed_data', 'ai_applied',
    ];

    protected $casts = [
        'data'          => 'array',
        'fixed_data'    => 'array',
        'issues'        => 'array',
        'ai_fixed_data' => 'array',
        'ai_applied'    => 'boolean',
        'row_number'    => 'integer',
    ];

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    public function getEffectiveData(): array
    {
        return $this->fixed_data ?? $this->data ?? [];
    }

    public function getIssuesByType(string $type): array
    {
        return collect($this->issues ?? [])
            ->filter(fn($i) => $i['type'] === $type)
            ->values()
            ->toArray();
    }

    public function hasErrors(): bool
    {
        return $this->status === 'error';
    }

    public function hasWarnings(): bool
    {
        return in_array($this->status, ['warning', 'error']);
    }

    public function field(string $key): mixed
    {
        return $this->getEffectiveData()[$key] ?? null;
    }

    public function scopeWithIssues($query)
    {
        return $query->whereIn('status', ['error', 'warning']);
    }

    public function scopeErrors($query)
    {
        return $query->where('status', 'error');
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'valid');
    }
}
