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

            <div class="flex items-center gap-1 sm:gap-3">

                <a href="{{ url('/') }}" @if (request()->is('/')) aria-current="page" @endif
                    class="inline-flex min-h-11 items-center px-2 text-sm transition-colors sm:px-3 {{ request()->is('/') ? 'text-nema-text' : 'text-nema-muted hover:text-nema-text' }}">
                    Beranda
                </a>

                <a href="{{ url('/film') }}" @if (request()->is('film')) aria-current="page" @endif
                    class="inline-flex min-h-11 items-center px-2 text-sm transition-colors sm:px-3 {{ request()->is('film') ? 'text-nema-text' : 'text-nema-muted hover:text-nema-text' }}">
                    Film
                </a>

                @auth
                    {{-- Menu akun memakai <details> supaya terbuka dengan ketukan di ponsel dan Enter
                         di keyboard. Menu yang hanya terbuka saat kursor lewat tidak bisa dipakai di HP. --}}
                    <details data-menu-akun class="relative">
                        <summary
                            class="inline-flex min-h-11 cursor-pointer list-none items-center gap-1.5 px-2 text-sm text-nema-text transition-colors hover:text-white sm:px-3 [&::-webkit-details-marker]:hidden">
                            <span class="max-w-24 truncate sm:max-w-40">{{ Auth::user()->name }}</span>
                            <svg class="size-4 shrink-0 text-nema-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </summary>

                        <div class="absolute right-0 top-full mt-1 flex w-52 flex-col rounded-lg border border-nema-line bg-nema-surface p-1 shadow-xl shadow-black/40">

                            @if (Auth::user()->isUser())
                                <a href="{{ url('/tiket-saya') }}"
                                   class="flex min-h-11 items-center rounded-md px-3 text-sm text-nema-muted transition-colors hover:bg-nema-surface-2 hover:text-nema-text">
                                    Tiket Saya
                                </a>
                            @endif

                            @if (Auth::user()->isAdmin())
                                <a href="{{ url('/admin') }}"
                                   class="flex min-h-11 items-center rounded-md px-3 text-sm text-nema-muted transition-colors hover:bg-nema-surface-2 hover:text-nema-text">
                                    Panel Admin
                                </a>
                            @endif

                            <div class="my-1 h-px bg-nema-line/40"></div>

                            <form method="POST" action="{{ url('/keluar') }}">
                                @csrf
                                <button type="submit"
                                        class="flex min-h-11 w-full items-center rounded-md px-3 text-left text-sm text-nema-muted transition-colors hover:bg-nema-surface-2 hover:text-nema-text">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </details>

                    <script>
                        // Menu akun ditutup lagi kalau pengguna mengetuk di luar menu atau menekan Esc.
                        (function () {
                            const menu = document.querySelector('[data-menu-akun]');

                            document.addEventListener('click', function (e) {
                                if (menu.open && ! menu.contains(e.target)) menu.open = false;
                            });

                            document.addEventListener('keydown', function (e) {
                                if (e.key === 'Escape' && menu.open) {
                                    menu.open = false;
                                    menu.querySelector('summary').focus();
                                }
                            });
                        })();
                    </script>
                @else
                    <a href="{{ url('/masuk') }}"
                        class="inline-flex min-h-11 items-center rounded-md bg-nema-maroon px-4 font-medium text-white sm:px-5 transition-colors hover:bg-nema-maroon-hover">
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
            AoraNema
        </p>
    </footer>

</body>

</html>