@extends('layouts.app')
@section('title', 'Dashboard — Commerce AI')
@section('heading', 'My Feeds')

@section('header-actions')
<button @click="uploadOpen = true"
        class="flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium rounded-lg transition-all shadow-sm shadow-indigo-500/20">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Upload Feed
</button>
@endsection

@section('content')
<div x-data="{
    uploadOpen: false,
    dragOver: false,
    file: null,
}">

{{-- Upload Modal --}}
<template x-teleport="body">
<div x-show="uploadOpen" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
     @keydown.escape.window="uploadOpen = false"
     @click.self="uploadOpen = false">

    <div class="w-full max-w-md glass-dark rounded-2xl p-6 shadow-2xl shadow-black/60">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-base font-semibold text-white">Upload Product Feed</h2>
                <p class="text-xs text-gray-500 mt-0.5">CSV, TSV or Google Merchant XML</p>
            </div>
            <button @click="uploadOpen = false" class="text-gray-600 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form action="{{ route('feeds.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Feed Name <span class="text-gray-600">(optional)</span></label>
                <input name="name" type="text" placeholder="e.g. Summer 2026 Catalog"
                       class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-xl text-white text-sm placeholder-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500/50 transition-all">
            </div>

            <div class="rounded-xl border-2 border-dashed p-6 text-center cursor-pointer transition-all"
                 :class="dragOver ? 'border-indigo-500 bg-indigo-500/10' : 'border-gray-800 hover:border-gray-600 hover:bg-white/[0.02]'"
                 @dragover.prevent="dragOver = true"
                 @dragleave.prevent="dragOver = false"
                 @drop.prevent="dragOver = false; file = $event.dataTransfer.files[0]; $refs.fi.files = $event.dataTransfer.files"
                 @click="$refs.fi.click()">

                <input type="file" name="file" accept=".csv,.tsv,.txt,.xml" x-ref="fi"
                       class="hidden" @change="file = $event.target.files[0]">

                <template x-if="!file">
                    <div class="space-y-1.5">
                        <svg class="w-8 h-8 mx-auto text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-sm text-gray-500">Drop file here or <span class="text-indigo-400 hover:text-indigo-300">browse</span></p>
                        <p class="text-xs text-gray-700">Up to 50 MB</p>
                    </div>
                </template>

                <template x-if="file">
                    <div class="space-y-1">
                        <svg class="w-7 h-7 mx-auto text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-emerald-300 font-medium" x-text="file.name"></p>
                        <p class="text-xs text-gray-600" x-text="(file.size/1024/1024).toFixed(2) + ' MB'"></p>
                    </div>
                </template>
            </div>

            @error('file')<p class="text-red-400 text-xs">{{ $message }}</p>@enderror

            <button type="submit" :disabled="!file"
                    class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition-all">
                Validate Feed →
            </button>
        </form>
    </div>
</div>
</template>

{{-- Feeds list --}}
@if($feeds->isEmpty())
<div class="flex flex-col items-center justify-center py-32 text-center">
    <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 flex items-center justify-center mb-4">
        <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
    </div>
    <h3 class="text-base font-semibold text-white mb-1">No feeds yet</h3>
    <p class="text-gray-600 text-sm mb-5">Upload your first product feed CSV to begin validation</p>
    <button @click="uploadOpen = true"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-all">
        Upload Feed
    </button>
</div>

@else
<div class="space-y-2.5">
    @foreach($feeds as $feed)
    <div class="glass rounded-xl group">
        <div class="flex items-center gap-4 p-4">

            {{-- Status indicator --}}
            <div class="shrink-0 w-1.5 h-10 rounded-full
                @if($feed->status === 'done' && $feed->error_count === 0) bg-emerald-500
                @elseif($feed->status === 'done') bg-yellow-500
                @elseif($feed->status === 'processing' || $feed->status === 'pending') bg-blue-500
                @elseif($feed->status === 'failed') bg-red-500
                @else bg-gray-600 @endif">
            </div>

            {{-- Name + meta --}}
            <div class="flex-1 min-w-0">
                <a href="{{ route('feeds.show', $feed) }}"
                   class="text-sm font-medium text-white hover:text-indigo-300 transition-colors truncate block">
                    {{ $feed->name }}
                </a>
                <p class="text-xs text-gray-600 mt-0.5 truncate">
                    {{ $feed->original_filename }} · {{ $feed->created_at->diffForHumans() }}
                </p>
            </div>

            {{-- Stats --}}
            @if($feed->status === 'done')
            <div class="hidden sm:flex items-center gap-5 text-center">
                <div><p class="text-sm font-semibold text-white">{{ number_format($feed->row_count) }}</p><p class="text-[10px] text-gray-600 mt-0.5">Products</p></div>
                <div><p class="text-sm font-semibold text-red-400">{{ number_format($feed->error_count) }}</p><p class="text-[10px] text-gray-600 mt-0.5">Errors</p></div>
                <div><p class="text-sm font-semibold text-yellow-400">{{ number_format($feed->warning_count) }}</p><p class="text-[10px] text-gray-600 mt-0.5">Warnings</p></div>
                <div>
                    <p class="text-sm font-semibold
                        @if($feed->health_score>=80) text-emerald-400 @elseif($feed->health_score>=50) text-yellow-400 @else text-red-400 @endif">
                        {{ $feed->health_score }}%
                    </p>
                    <p class="text-[10px] text-gray-600 mt-0.5">Health</p>
                </div>
            </div>
            @elseif($feed->is_processing)
            <span class="text-xs text-blue-400 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Processing…
            </span>
            @elseif($feed->status === 'failed')
            <span class="text-xs text-red-400">Failed</span>
            @endif

            {{-- Actions --}}
            <div class="flex items-center gap-1.5">
                <a href="{{ route('feeds.show', $feed) }}"
                   class="p-1.5 text-gray-600 hover:text-white transition-colors rounded-lg hover:bg-white/5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-4">{{ $feeds->links() }}</div>
@endif

</div>
@endsection
