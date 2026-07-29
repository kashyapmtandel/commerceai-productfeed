<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    protected $fillable = [
        'user_id', 'name', 'original_filename', 'storage_path',
        'mime_type', 'status', 'row_count', 'error_count',
        'warning_count', 'error_message',
    ];

    protected $casts = [
        'row_count'     => 'integer',
        'error_count'   => 'integer',
        'warning_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(FeedRow::class);
    }

    public function errorRows(): HasMany
    {
        return $this->hasMany(FeedRow::class)->where('status', 'error');
    }

    public function warningRows(): HasMany
    {
        return $this->hasMany(FeedRow::class)->where('status', 'warning');
    }

    public function validRows(): HasMany
    {
        return $this->hasMany(FeedRow::class)->where('status', 'valid');
    }

    public function getIsProcessingAttribute(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function getHealthScoreAttribute(): int
    {
        if ($this->row_count === 0) return 0;
        $bad = $this->error_count + ($this->warning_count * 0.5);
        return (int) max(0, round(100 - ($bad / $this->row_count * 100)));
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'done'       => $this->error_count > 0 ? 'yellow' : 'green',
            'processing' => 'blue',
            'failed'     => 'red',
            default      => 'gray',
        };
    }
}
