<div class="kop">
    @isset($kop_title)<h2>{{ $kop_title }}</h2>@endisset
    @isset($kop_title)<h3>Rukun Tetangga {{ config('rt.rt') }} / Rukun Warga {{ config('rt.rw') }}</h3>
    @else<h2>Rukun Tetangga {{ config('rt.rt') }} / Rukun Warga {{ config('rt.rw') }}</h2>@endisset
    <h3>Kelurahan {{ config('rt.kelurahan') }}, Kecamatan {{ config('rt.kecamatan') }}, {{ config('rt.kota') }}</h3>
    <p>Sekretariat: {{ config('rt.secretariat.address') }}<br>Telp/WhatsApp: {{ config('rt.secretariat.phone') }} · Email: {{ config('rt.secretariat.email') }}</p>
</div>
