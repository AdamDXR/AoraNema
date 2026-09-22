{{-- Navigasi halaman untuk tabel admin. Menggantikan tampilan bawaan Laravel yang berbahasa
     Inggris dan tombolnya lebih pendek dari 44 piksel. Dipakai lewat ->links('partials.halaman'). --}}
@if ($paginator->hasPages())
    <nav aria-label="Halaman" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-nema-muted">
            Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}
        </p>

        <div class="flex gap-2">
            @if ($paginator->onFirstPage())
                <span class="inline-flex min-h-11 items-center rounded-md border border-nema-line/40 px-4 text-sm text-nema-muted/50">
                    &larr;&nbsp; Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="inline-flex min-h-11 items-center rounded-md border border-nema-line px-4 text-sm transition-colors hover:bg-nema-surface">
                    &larr;&nbsp; Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="inline-flex min-h-11 items-center rounded-md border border-nema-line px-4 text-sm transition-colors hover:bg-nema-surface">
                    Berikutnya&nbsp; &rarr;
                </a>
            @else
                <span class="inline-flex min-h-11 items-center rounded-md border border-nema-line/40 px-4 text-sm text-nema-muted/50">
                    Berikutnya&nbsp; &rarr;
                </span>
            @endif
        </div>
    </nav>
@endif
