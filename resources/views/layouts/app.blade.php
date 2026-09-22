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

                <a href="{{ url('/film') }}" @if (request()->is('film')) aria-current="page" @endif
                    class="inline-flex min-h-11 items-center px-3 text-sm transition-colors {{ request()->is('film') ? 'text-nema-text' : 'text-nema-muted hover:text-nema-text' }}">
                    Film
                </a>

                @auth
                    <div class="relative group">
                        <button
                            class="inline-flex min-h-11 items-center gap-2 px-3 text-sm transition-colors text-nema-text hover:text-white">
                            <span>{{ Auth::user()->name }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="opacity-50">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>
                        <div
                            class="absolute right-0 top-full hidden w-48 flex-col rounded-md border border-nema-line bg-nema-bg p-1 shadow-lg group-hover:flex">
                            
                            @if(Auth::user()->isUser())
                                <a href="{{ url('/tiket-saya') }}"
                                    class="flex w-full min-h-10 items-center rounded-sm px-3 text-sm text-left text-nema-muted hover:bg-nema-line/30 hover:text-nema-text transition-colors">
                                    Tiket Saya
                                </a>
                                <div class="my-1 h-px w-full bg-nema-line/40"></div>
                            @endif

                            @if(Auth::user()->isAdmin())
                                <a href="{{ url('/admin') }}"
                                    class="flex w-full min-h-10 items-center rounded-sm px-3 text-sm text-left text-nema-muted hover:bg-nema-line/30 hover:text-nema-text transition-colors">
                                    Admin Panel
                                </a>
                                <div class="my-1 h-px w-full bg-nema-line/40"></div>
                            @endif
                            
                            <form method="POST" action="{{ url('/keluar') }}">
                                @csrf
                                <button type="submit"
                                    class="flex w-full min-h-10 items-center rounded-sm px-3 text-sm text-left text-nema-muted hover:bg-nema-line/30 hover:text-nema-text transition-colors">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                @else
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
            AoraNema
        </p>
    </footer>

</body>

</html>