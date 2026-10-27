@extends('layouts.app')

@section('content')
<div id="crud" data-page="{{ $page }}" data-title="{{ $pageDef['label'] }}" data-can-create="{{ $canCreate ? 1 : 0 }}">
    <div class="empty">Memuat…</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages.js') }}?v={{ filemtime(public_path('js/pages.js')) }}"></script>
@endpush
