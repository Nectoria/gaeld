<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body
    class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
    <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
        @if (Route::has('login'))
            <nav class="flex items-center justify-end gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                        {{ __('Dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal">
                        {{ __('Log in') }}
                    </a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                            {{ __('Register') }}
                        </a>
                    @endif
                @endauth
            </nav>
        @endif
    </header>
    <div class="bg-slate-900/90 dark:bg-slate-800/90 rounded-3xl w-full lg:max-w-4xl max-w-md shadow-lg shadow-slate-900/10 dark:shadow-slate-800/10">
        <div class="px-6 py-24 sm:py-32 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <div class="mx-auto mb-6 h-12 w-12 sm:h-16 sm:w-16">
                    <x-app-logo-icon class="h-full w-full text-white" />
                </div>
                <h2 class="text-4xl font-semibold tracking-tight text-balance text-white sm:text-5xl">{{ __('Welcome') }}</h2>
                <p class="mx-auto mt-6 max-w-xl text-lg/8 text-pretty text-indigo-200">{{ __('Gäld is an open-source invoicing and billing application designed to simplify your financial management tasks.') }}</p>
            </div>
        </div>
    </div>

    @if (Route::has('login'))
        <div class="h-14.5 hidden lg:block"></div>
    @endif
</body>

</html>
