{{-- Dipakai di semua halaman admin. Tautan aktif ditandai garis aksen,
     bukan cuma warna teks, supaya perbedaannya tetap terbaca. --}}
<nav aria-label="Menu admin" class="border-b border-nema-line/40">
    <div class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 sm:px-6">
        @foreach (['film' => 'Film', 'studio' => 'Studio', 'jadwal' => 'Jadwal Tayang', 'pesanan' => 'Pesanan'] as $jalur => $label)
            @php $aktif = request()->is('admin/' . $jalur . '*'); @endphp

            <a href="{{ url('/admin/' . $jalur) }}"
               @if ($aktif) aria-current="page" @endif
               class="inline-flex min-h-11 shrink-0 items-center border-b-2 px-4 text-sm transition-colors {{ $aktif ? 'border-nema-accent' : 'border-transparent text-nema-muted hover:text-nema-text' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</nav>

