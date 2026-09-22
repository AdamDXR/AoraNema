{{-- Deretan keterangan di bawah judul film: durasi, batas usia, dan format layar.
     Dipakai tampilan kotak maupun baris supaya isinya selalu sama. --}}
<div class="mt-2 flex flex-wrap gap-1.5 text-sm text-nema-muted">
    <span class="inline-flex h-8 items-center rounded-md bg-nema-surface-2 px-2.5">
        <span class="sr-only">Durasi </span>{{ $f['durasiTeks'] }}
    </span>

    @include('partials.usia', ['usia' => $f['usia']])

    @foreach ($f['format'] as $format)
        <span class="inline-flex h-8 items-center rounded-md bg-nema-surface-2 px-2.5">{{ $format }}</span>
    @endforeach
</div>

@if ($f['mulaiTeks'])
    <p class="mt-2 text-xs text-nema-accent">Tayang mulai {{ $f['mulaiTeks'] }}</p>
@endif
