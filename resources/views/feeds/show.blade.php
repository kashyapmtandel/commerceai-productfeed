@extends('layouts.app')
@section('title', $feed->name . ' — Commerce AI')
@section('heading', $feed->name)

@section('header-actions')
@if($feed->status === 'done')
<a href="{{ route('feeds.export', $feed) }}"
   class="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-medium rounded-lg transition-all">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
    </svg>
    Export Clean CSV
</a>
@endif
<a href="{{ route('dashboard') }}" class="text-xs text-gray-600 hover:text-gray-300 transition-colors">← Back</a>
@endsection

@section('content')

<div x-data="{
    rowCount: @js($feed->row_count),
    errorCount: @js($feed->error_count),
    warningCount: @js($feed->warning_count),
    healthScore() {
        if (this.rowCount === 0) return 0;
        const bad = this.errorCount + (this.warningCount * 0.5);
        return Math.max(0, Math.round(100 - ((bad / this.rowCount) * 100)));
    },
    healthScoreColor() {
        if (this.healthScore() >= 80) return 'text-emerald-400';
        if (this.healthScore() >= 50) return 'text-yellow-400';
        return 'text-red-400';
    },
    updateFeedStatusCounts(fromStatus, toStatus) {
        if (fromStatus === toStatus) return;
        if (fromStatus === 'error') this.errorCount = Math.max(0, this.errorCount - 1);
        if (fromStatus === 'warning') this.warningCount = Math.max(0, this.warningCount - 1);
        if (toStatus === 'error') this.errorCount++;
        if (toStatus === 'warning') this.warningCount++;
    }
}">

{{-- Summary cards --}}
@if($feed->status === 'done')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="glass rounded-xl p-4 text-center">
        <p class="text-xl font-bold text-white">{{ number_format($feed->row_count) }}</p>
        <p class="text-[11px] text-gray-600 mt-0.5">Total Products</p>
    </div>
    <div class="glass rounded-xl p-4 text-center">
        <p class="text-xl font-bold text-red-400" x-text="errorCount.toLocaleString()">{{ number_format($feed->error_count) }}</p>
        <p class="text-[11px] text-gray-600 mt-0.5">Errors</p>
    </div>
    <div class="glass rounded-xl p-4 text-center">
        <p class="text-xl font-bold text-yellow-400" x-text="warningCount.toLocaleString()">{{ number_format($feed->warning_count) }}</p>
        <p class="text-[11px] text-gray-600 mt-0.5">Warnings</p>
    </div>
    <div class="glass rounded-xl p-4 text-center">
        <p class="text-xl font-bold" :class="healthScoreColor()" x-text="healthScore() + '%'">{{ $feed->health_score }}%</p>
        <p class="text-[11px] text-gray-600 mt-0.5">Health Score</p>
    </div>
</div>

@elseif($feed->is_processing)
<div class="glass rounded-xl p-5 mb-6 flex items-center gap-3"
     x-data x-init="setInterval(async () => {
         const r = await fetch('{{ route('feeds.status', $feed) }}');
         const d = await r.json();
         if (['done','failed'].includes(d.status)) location.reload();
     }, 3000)">
    <svg class="w-5 h-5 text-blue-400 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
    </svg>
    <div>
        <p class="text-sm font-medium text-white">Processing your feed…</p>
        <p class="text-xs text-gray-600">Page refreshes automatically when done.</p>
    </div>
</div>

@elseif($feed->status === 'failed')
<div class="glass rounded-xl p-5 mb-6 border border-red-500/20 bg-red-500/5">
    <p class="text-sm font-medium text-red-400 mb-1">Processing failed</p>
    <p class="text-xs text-gray-500">{{ $feed->error_message }}</p>
</div>
@endif

<div>
    {{-- Filter tabs --}}
    @if($rows->isNotEmpty())
    <div class="flex items-center gap-1.5 mb-4 text-xs">
        <span class="text-gray-600">Showing</span>
        <span class="px-2 py-1 rounded-md bg-white/5 text-gray-300 font-medium">
            {{ $rows->total() }} rows
        </span>
        <span x-show="errorCount > 0" x-cloak class="px-2 py-1 rounded-md bg-red-500/10 text-red-400 font-medium"
              x-text="errorCount + (errorCount === 1 ? ' error' : ' errors')"></span>
        <span x-show="warningCount > 0" x-cloak class="px-2 py-1 rounded-md bg-yellow-500/10 text-yellow-400 font-medium"
              x-text="warningCount + (warningCount === 1 ? ' warning' : ' warnings')"></span>
        <span x-show="errorCount === 0 && warningCount === 0" x-cloak class="px-2 py-1 rounded-md bg-emerald-500/10 text-emerald-400 font-medium">
            All fixed
        </span>
    </div>
    @endif

    {{-- Rows --}}
    <div class="space-y-2">
    @forelse($rows as $row)
    <div class="glass rounded-xl overflow-hidden transition-colors"
         :class="{
             'border-emerald-500/30 bg-emerald-500/[0.06]': status === 'valid',
             'border-yellow-500/20': status === 'warning',
             'border-red-500/20': status === 'error'
         }"
         x-data="{
             open: {{ $row->hasErrors() ? 'true' : 'false' }},
             status: @js($row->status),
             issues: @js($row->issues ?? []),
             aiLoading: false,
             aiSuccess: false,
             aiResult: @js($row->ai_fixed_data ? ['fixed_data' => $row->ai_fixed_data, 'suggestion' => $row->ai_suggestion] : null),
             applying: false,
             rateLimitCountdown: 0,
             autoRetryCount: 0,
             maxAutoRetries: 2,
             countdownTimer: null,
             startCountdown(seconds = 62, errorMessage = null) {
                 if (this.autoRetryCount >= this.maxAutoRetries) {
                     this.rateLimitCountdown = 0;
                     this.aiResult = { error: (errorMessage ? errorMessage + ' ' : '') + 'Automatic retries are exhausted. Click Try Again when ready.' };
                     return;
                 }
                 this.autoRetryCount++;
                 this.rateLimitCountdown = seconds;
                 this.aiResult = errorMessage ? { error: errorMessage } : null;
                 if (this.countdownTimer) clearInterval(this.countdownTimer);
                 this.countdownTimer = setInterval(() => {
                     this.rateLimitCountdown--;
                     if (this.rateLimitCountdown <= 0) {
                         clearInterval(this.countdownTimer);
                         this.rateLimitCountdown = 0;
                         this.getAi();
                     }
                 }, 1000);
             },
             async getAi() {
                 if (this.aiLoading || this.rateLimitCountdown > 0) return;
                 this.aiLoading = true;
                 this.aiSuccess = false;
                 this.aiResult = null;
                 try {
                     const r = await fetch('{{ route('feeds.rows.ai-suggest', [$feed, $row]) }}', {
                         method: 'POST',
                         headers: {
                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                             'Accept': 'application/json'
                         }
                     });
                     const data = await r.json();
                     if (r.status === 429 || data.retryable) {
                         this.startCountdown(data.retry_after || (r.status === 429 ? 62 : 8), data.error);
                         return;
                     }
                     if (! r.ok) {
                         this.aiResult = { error: data.error || data.message || 'Unable to generate an AI fix.' };
                         return;
                     }
                     if (data.fixed_data) {
                         this.aiSuccess = true;
                         this.autoRetryCount = 0;
                         this.aiResult = data;
                         return;
                     }
                     this.aiResult = { error: data.error || 'Gemini did not return a usable fix. Try again.' };
                 } catch(e) {
                     this.aiResult = { error: e.message };
                 } finally {
                     this.aiLoading = false;
                 }
             },
             resetAndRetry() {
                 this.autoRetryCount = 0;
                 this.aiResult = null;
                 this.aiSuccess = false;
                 this.rateLimitCountdown = 0;
                 if (this.countdownTimer) clearInterval(this.countdownTimer);
                 this.getAi();
             },
             async applyFix() {
                 this.applying = true;
                 try {
                     const r = await fetch('{{ route('feeds.rows.ai-apply', [$feed, $row]) }}', {
                         method: 'POST',
                         headers: {
                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                             'Accept': 'application/json'
                         }
                     });
                     const data = await r.json();
                     if (! r.ok) {
                         this.aiResult = { error: data.message || 'Unable to apply AI fix.' };
                         return;
                     }
                     const previousStatus = this.status;
                     this.status = data.new_status;
                     this.issues = data.issues || [];
                     updateFeedStatusCounts(previousStatus, this.status);
                     this.aiResult = null;
                     this.aiSuccess = false;
                     if (this.issues.length === 0) {
                         this.open = false;
                     }
                 } catch(e) {
                     this.aiResult = { error: e.message };
                 } finally {
                     this.applying = false;
                 }
             }
         }">

        {{-- Row header --}}
        <button class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-white/[0.03] transition-all"
                @click="open = !open">

            {{-- Status dot --}}
            <span class="w-2 h-2 rounded-full shrink-0"
                  :class="status === 'error'
                      ? 'bg-red-500 shadow-sm shadow-red-500/50'
                      : (status === 'warning'
                          ? 'bg-yellow-500 shadow-sm shadow-yellow-500/50'
                          : 'bg-emerald-500 shadow-sm shadow-emerald-500/50')">
            </span>

            <span class="text-[11px] text-gray-600 w-14 shrink-0 tabular-nums">#{{ $row->row_number }}</span>

            <span class="flex-1 text-sm text-white truncate">
                {{ Str::limit($row->field('title') ?: ($row->field('id') ?: 'Row ' . $row->row_number), 80) }}
            </span>

            <template x-if="issues.length > 0">
                <span class="shrink-0 text-[11px] px-2 py-0.5 rounded-full font-medium"
                      :class="status === 'error' ? 'bg-red-500/15 text-red-400' : 'bg-yellow-500/15 text-yellow-400'"
                      x-text="issues.length + (issues.length === 1 ? ' issue' : ' issues')"></span>
            </template>

            <template x-if="status === 'valid'">
                <span class="shrink-0 text-[11px] px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400">All fixed</span>
            </template>

            <svg class="w-4 h-4 text-gray-700 shrink-0 transition-transform duration-200" :class="open && 'rotate-180'"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        {{-- Expanded content --}}
        <div x-show="open" x-collapse>
            <div class="border-t border-white/5 px-4 py-4 space-y-4">

                {{-- Issues --}}
                @if($row->issues && count($row->issues) > 0)
                <div x-show="issues.length > 0">
                    <p class="text-[10px] font-semibold text-gray-600 uppercase tracking-widest mb-2">Validation Issues</p>
                    <div class="space-y-1.5">
                        @foreach($row->issues as $issue)
                        <div class="flex items-start gap-2.5 text-xs">
                            <span class="mt-0.5 shrink-0 inline-flex items-center justify-center w-4 h-4 rounded-full text-[10px] font-bold
                                @if($issue['type']==='error') bg-red-500/20 text-red-400 @else bg-yellow-500/20 text-yellow-400 @endif">
                                {{ $issue['type']==='error' ? '✕' : '!' }}
                            </span>
                            <div class="leading-relaxed">
                                <code class="text-indigo-400 text-[11px]">{{ $issue['field'] }}</code>
                                <span class="text-gray-400 ml-1.5">{{ $issue['message'] }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- AI Fix --}}
                @if($row->hasErrors() || $row->hasWarnings())
                <div class="border-t border-white/5 pt-4" x-show="issues.length > 0">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[10px] font-semibold text-gray-600 uppercase tracking-widest">✦ Gemini AI Fix</p>
                        <button @click="getAi()" :disabled="aiLoading || rateLimitCountdown > 0"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all
                                       bg-indigo-600/20 hover:bg-indigo-600/40 border border-indigo-500/25 text-indigo-300
                                       disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="aiLoading" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <span x-show="rateLimitCountdown > 0" x-text="'Auto-retry ' + autoRetryCount + '/' + maxAutoRetries + ' in ' + rateLimitCountdown + 's'"></span>
                            <span x-show="rateLimitCountdown <= 0" x-text="aiLoading ? 'Contacting Gemini…' : (aiResult && aiResult.fixed_data ? 'Regenerate' : 'Suggest Fix')"></span>
                        </button>
                    </div>

                    {{-- ✅ SUCCESS --}}
                    <template x-if="aiResult && aiResult.fixed_data">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                                <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <p class="text-xs text-emerald-300 font-medium">Gemini AI fix ready — review and apply below</p>
                            </div>
                            <pre class="text-[11px] bg-black/40 rounded-lg p-3 text-emerald-300 overflow-x-auto leading-relaxed"
                                 x-text="JSON.stringify(aiResult.fixed_data, null, 2)"></pre>
                            <div class="flex items-center gap-3">
                                <button @click="applyFix()" :disabled="applying"
                                        class="flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-lg transition-all shadow-sm shadow-emerald-500/20 disabled:opacity-50">
                                    <svg x-show="applying" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    <span x-text="applying ? 'Applying fix…' : '✓ Apply AI Fix'"></span>
                                </button>
                                <p class="text-xs text-gray-600">Replaces this row's data with Gemini's corrected values</p>
                            </div>
                        </div>
                    </template>

                    {{-- ⏳ RATE LIMIT COUNTDOWN --}}
                    <template x-if="rateLimitCountdown > 0">
                        <div class="flex items-center gap-3 text-xs bg-yellow-500/10 border border-yellow-500/20 rounded-lg px-3 py-2.5">
                            <svg class="w-4 h-4 text-yellow-400 shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div class="flex-1">
                                <p class="text-yellow-300 font-medium">Waiting to retry</p>
                                <p class="text-yellow-700 mt-0.5">Auto-retry <span class="text-yellow-500" x-text="autoRetryCount"></span>/<span x-text="maxAutoRetries"></span> fires in <span class="font-bold text-yellow-400" x-text="rateLimitCountdown"></span>s</p>
                            </div>
                        </div>
                    </template>

                    {{-- ❌ ERROR / RETRIES EXHAUSTED --}}
                    <template x-if="aiResult && aiResult.error && rateLimitCountdown <= 0 && !aiSuccess">
                        <div class="space-y-2">
                            <div class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg p-3" x-text="aiResult.error"></div>
                            <button @click="resetAndRetry()"
                                    class="text-xs px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white border border-white/10 transition-all">
                                ↻ Try Again Manually
                            </button>
                        </div>
                    </template>

                    @if($row->ai_applied)
                    <div class="flex items-center gap-2 text-xs text-emerald-400 bg-emerald-500/10 border border-emerald-500/15 rounded-lg px-3 py-2">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        AI fix already applied to this row
                    </div>
                    @endif
                </div>
                @endif

            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-16 text-gray-600 text-sm">No rows found.</div>
    @endforelse
    </div>
</div>

@if($rows->hasPages())
<div class="mt-5">{{ $rows->links() }}</div>
@endif

</div>

@endsection
