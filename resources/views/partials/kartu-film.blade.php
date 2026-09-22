{{-- Kartu film bentuk kotak: poster, judul di bawahnya, lalu keterangan.
     Dipakai bagian Semua Film di beranda dan tampilan kotak di /film. --}}
<a href="{{ url('/film/' . $f['slug']) }}" class="group block">
    <div class="overflow-hidden rounded-lg bg-nema-surface-2">
        @if ($f['poster'])
            <img src="{{ $f['poster'] }}" alt="" loading="lazy"
                 class="aspect-2/3 w-full object-cover transition-transform duration-300 group-hover:scale-105">
        @else
            <div class="flex aspect-2/3 items-end p-3">
                <span class="text-xs text-nema-muted">Poster belum tersedia</span>
            </div>
        @endif
    </div>

    <h3 class="mt-3 line-clamp-2 text-base leading-snug transition-colors group-hover:text-nema-accent">
        {{ $f['judul'] }}
    </h3>

    @include('partials.keterangan-film', ['f' => $f])
</a>
