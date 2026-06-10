@extends('layouts.app')

@php $appName = config('app.name'); @endphp
@section('title', '404 — Страница не найдена | ' . $appName)
@section('noindex', true)
@section('canonical')
@endsection

@section('content')
<div class="container mx-auto px-4 py-20 text-center max-w-2xl">

    <div class="text-[120px] font-black leading-none text-gray-100 select-none mb-2">
        404
    </div>

    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3">
        Страница не найдена
    </h1>
    <p class="text-gray-500 mb-8 leading-relaxed max-w-md mx-auto">
        Возможно, страница была удалена или вы перешли по устаревшей ссылке.
    </p>

    <form action="{{ route('search') }}" method="GET"
          class="flex gap-2 max-w-md mx-auto mb-8">
        <input type="search" name="q"
               placeholder="Найти товар..."
               autofocus
               class="flex-1 px-4 py-3 border border-gray-200 rounded-xl text-sm
                      focus:outline-none focus:ring-2 focus:ring-amber-400">
        <button type="submit"
                class="px-5 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </button>
    </form>

    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
        <a href="{{ route('home') }}"
           class="inline-flex items-center justify-center px-6 py-3 bg-amber-500
                  hover:bg-amber-600 text-white font-semibold rounded-xl transition-colors">
            На главную
        </a>
        <a href="{{ route('catalog') }}"
           class="inline-flex items-center justify-center px-6 py-3
                  border border-gray-200 rounded-xl text-gray-700 font-semibold
                  hover:border-amber-400 hover:text-amber-600 transition-colors">
            Каталог товаров
        </a>
    </div>

</div>
@endsection
