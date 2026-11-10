@if (session('status'))
    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
        {{ session('status') }}
    </div>
@endif

@if (session('warning'))
    <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">
        {{ session('warning') }}
    </div>
@endif
