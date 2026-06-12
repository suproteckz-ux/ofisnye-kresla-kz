@extends('layouts.app')

@section('title', 'Контакты магазина офисных кресел в Алматы | ' . config('app.name'))
@section('description', 'Контакты магазина офисных кресел в Алматы: адрес, телефон, WhatsApp и режим работы. Работаем с организациями и плательщиками НДС.')

@section('canonical')
<link rel="canonical" href="{{ route('contacts') }}">
@endsection

@section('breadcrumbs')
<x-ui.breadcrumbs :items="[['name' => 'Контакты']]"/>
@endsection

@section('content')
<section class="contacts-page">
    <div class="container">
        <header class="contacts-intro">
            <h1>Контакты</h1>
            <p>Свяжитесь с нами для консультации, уточнения наличия и оформления заказа.</p>
        </header>

        <div class="contacts-grid">
            <article class="contacts-card">
                <span>Адрес</span>
                <h2>{{ $address }}</h2>
            </article>

            <article class="contacts-card">
                <span>Телефон</span>
                <h2><a href="tel:{{ preg_replace('/\D/', '', $phone) }}">{{ $phone }}</a></h2>
            </article>

            @if($workingHours)
            <article class="contacts-card">
                <span>Режим работы</span>
                <h2>{!! nl2br(e($workingHours)) !!}</h2>
            </article>
            @endif

            <article class="contacts-card contacts-card-action">
                <span>Быстрая консультация</span>
                <h2>Напишите нам в WhatsApp</h2>
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}"
                   target="_blank" rel="noopener" class="contacts-wa">WhatsApp</a>
            </article>
        </div>

        <div class="legal-banner">
            <div>
                <h2>Работаем с юридическими лицами, плательщики НДС</h2>
                <p>Заключаем договоры, выставляем счета и предоставляем закрывающие документы.</p>
            </div>
            <a href="{{ route('delivery-payment') }}#legal">Условия для организаций →</a>
        </div>
    </div>
</section>

<style>
.contacts-page{padding:28px 0 56px}.contacts-intro{max-width:720px;margin-bottom:26px}
.contacts-intro h1{font-size:clamp(1.75rem,4vw,2.3rem);font-weight:800;margin-bottom:8px}.contacts-intro p{color:#666;line-height:1.65}
.contacts-grid{display:grid;grid-template-columns:1fr;gap:14px}.contacts-card{padding:22px;border:1px solid #e7e5e4;border-radius:16px;background:#fff}
.contacts-card>span{display:block;font-size:12px;color:#a8a29e;margin-bottom:8px}.contacts-card h2{font-size:17px;line-height:1.45}
.contacts-card h2 a:hover{color:#d97706}.contacts-card-action{background:#fafaf9}.contacts-wa{display:inline-flex;margin-top:16px;padding:10px 18px;border-radius:9px;background:#22c55e;color:#fff;font-size:13px;font-weight:700}
.contacts-wa:hover{background:#16a34a}.legal-banner{display:flex;flex-direction:column;gap:16px;margin-top:20px;padding:22px;border:1px solid #fed7aa;border-radius:16px;background:#fffaf5}
.legal-banner h2{font-size:18px;margin-bottom:5px}.legal-banner p{font-size:14px;color:#666}.legal-banner>a{align-self:flex-start;padding:9px 14px;border:1px solid #e7e5e4;border-radius:9px;background:#fff;font-size:13px;font-weight:600}
@media(min-width:700px){.contacts-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.legal-banner{flex-direction:row;align-items:center;justify-content:space-between}.legal-banner>a{align-self:center;white-space:nowrap}}
</style>
@endsection

@section('schema')
<x-schema.breadcrumbs :items="[['name' => 'Контакты', 'url' => route('contacts')]]"/>
@endsection
