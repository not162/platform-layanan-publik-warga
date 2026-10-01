@props([
    'variant' => 'horizontal', // 'horizontal', 'symbol', 'monochrome', 'favicon'
    'class' => 'h-10',
])

@php
$assetMap = [
    'horizontal' => 'images/logo-horizontal.svg',
    'symbol' => 'images/logo-symbol.svg',
    'monochrome' => 'images/logo-monochrome.svg',
    'favicon' => 'images/favicon.svg',
];
$src = asset($assetMap[$variant] ?? $assetMap['horizontal']);
$alt = 'Layanan Publik Warga' . ($variant !== 'horizontal' ? ' (' . ucfirst($variant) . ')' : '');
@endphp

<img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => $class]) }}>
