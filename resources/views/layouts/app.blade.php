<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'AoraNema')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-nema-bg text-nema-text antialiased">

    {{-- Header dibuat tembus pandang karena slider poster lewat di bawahnya saat digulir --}}
    <header class="sticky top-0 z-50 border-b border-nema-line/40 bg-nema-bg/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">

            <a href="{{ url('/') }}" class="font-display text-2xl font-semibold tracking-tight">
                Aora<span class="text-nema-accent">Nema</span>
            </a>

            <div class="flex items-center gap-4">
                @auth
                    {{-- Jika user sudah login, tampilkan nama dan tombol keluar --}}
                    <span class="text-sm text-nema-muted hidden sm:inline">
                        Halo, <strong class="text-nema-text">{{ Auth::user()->name }}</strong>
                    </span>

                    <form action="{{ url('/keluar') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="inline-flex min-h-11 items-center rounded-md border border-nema-line px-4 text-sm font-medium transition-colors hover:bg-nema-surface">
                            Keluar
                        </button>
                    </form>
                @else
                    {{-- Jika user belum login, tampilkan tombol Masuk --}}
                    <a href="{{ url('/masuk') }}"
                       class="inline-flex min-h-11 items-center rounded-md bg-nema-maroon px-5 font-medium text-white transition-colors hover:bg-nema-maroon-hover">
                        Masuk
                    </a>
                @endauth
            </div>

        </div>
    </header>

    <main>
        @yield('konten')
    </main>

    <footer class="mt-20 border-t border-nema-line/40 px-4 py-10 sm:px-6">
        <p class="mx-auto max-w-7xl text-sm text-nema-muted">
            AoraNema. Project kuliah, bukan layanan komersial.
        </p>
    </footer>

</body>
</html>
