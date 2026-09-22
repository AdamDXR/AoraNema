{{-- Tanda batas usia: kotak merah untuk 17+, segitiga kuning untuk 13+, bulat hijau untuk SU.
     Bentuknya dibedakan, bukan cuma warnanya, supaya tetap terbaca oleh yang buta warna. --}}
<span class="inline-flex h-8 items-center rounded-md bg-nema-surface-2 px-2.5">
    <span class="sr-only">Batas usia </span>

    @if ($usia === '13+')
        <span class="inline-flex h-6 w-9 items-end justify-center bg-usia-remaja pb-0.5 text-[11px] font-semibold leading-none text-nema-bg [clip-path:polygon(50%_0,100%_100%,0_100%)]">
            {{ $usia }}
        </span>
    @elseif ($usia === 'SU')
        <span class="inline-flex size-6 items-center justify-center rounded-full bg-usia-semua text-[11px] font-semibold leading-none text-white">
            {{ $usia }}
        </span>
    @else
        <span class="inline-flex h-6 min-w-8 items-center justify-center rounded-sm bg-usia-dewasa px-1 text-[11px] font-semibold leading-none text-white">
            {{ $usia }}
        </span>
    @endif
</span>
