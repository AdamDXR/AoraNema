{{-- Deretan keterangan di bawah judul film: durasi, batas usia, dan format layar.
     Dipakai beranda dan halaman /film, kotak maupun baris, supaya isinya selalu sama.
     Sengaja satu baris: format digabung jadi satu kotak yang dipotong dengan elipsis
     kalau kartunya terlalu sempit, jadi deretannya tidak pernah turun ke baris kedua. --}}
<div class="mt-2 flex items-center gap-1 text-xs text-nema-muted">
    @if ($f['durasiTeks'])
        <span class="inline-flex h-7 shrink-0 items-center rounded-md bg-nema-surface-2 px-2">
            <span class="sr-only">Durasi </span>{{ $f['durasiTeks'] }}
        </span>
    @endif

    @if ($f['usia'])
        @include('partials.usia', ['usia' => $f['usia']])
    @endif

    @if ($f['format'])
        <span class="inline-flex h-7 min-w-0 items-center rounded-md bg-nema-surface-2 px-2">
            <span class="truncate"><span class="sr-only">Format </span>{{ implode(' · ', $f['format']) }}</span>
        </span>
    @endif
</div>

@if ($f['mulaiTeks'])
    <p class="mt-2 text-xs text-nema-accent">Tayang mulai {{ $f['mulaiTeks'] }}</p>
@elseif (! $f['tayang'])
    <p class="mt-2 text-xs text-nema-accent">Segera tayang</p>
@endif
