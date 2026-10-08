{{-- $name, $accept, $hint --}}
<label data-upload-box class="flex items-center gap-3 border-2 border-dashed border-line rounded-lg px-4 py-3 cursor-pointer hover:border-primary/50 transition">
    <svg class="w-5 h-5 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M12 12v9m0-9l-3 3m3-3l3 3"/></svg>
    <div class="min-w-0">
        <p class="text-sm font-semibold" data-upload-filename></p>
        <p class="text-xs text-muted" data-upload-hint>{{ $hint }}</p>
    </div>
    <input type="file" name="{{ $name }}" accept="{{ $accept }}" class="hidden" onchange="handleFilePreview(this)">
</label>
