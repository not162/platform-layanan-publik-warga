@props([
    'variant' => 'horizontal', // 'horizontal', 'symbol', 'monochrome', 'favicon'
    'class' => 'h-10',
    'fetchpriority' => 'auto',
])

@php
$assetMap = [
    'horizontal' => ['src' => 'images/logo-horizontal.svg', 'width' => 180, 'height' => 40],
    'symbol' => ['src' => 'images/logo-symbol.svg', 'width' => 40, 'height' => 40],
    'monochrome' => ['src' => 'images/logo-monochrome.svg', 'width' => 180, 'height' => 40],
    'favicon' => ['src' => 'images/favicon.svg', 'width' => 32, 'height' => 32],
];
$config = $assetMap[$variant] ?? $assetMap['horizontal'];
$src = asset($config['src']);
$alt = 'Layanan Publik Warga' . ($variant !== 'horizontal' ? ' (' . ucfirst($variant) . ')' : '');
@endphp

<img src="{{ $src }}" alt="{{ $alt }}" width="{{ $config['width'] }}" height="{{ $config['height'] }}" fetchpriority="{{ $fetchpriority }}" decoding="async" {{ $attributes->merge(['class' => $class]) }}>
