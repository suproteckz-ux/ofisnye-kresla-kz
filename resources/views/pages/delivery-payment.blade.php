@extends('layouts.app')

@section('title', 'Доставка и оплата офисных кресел | ' . config('app.name'))
@section('description', 'Доставка офисных кресел по Алматы за 1–2 дня и по Казахстану. Оплата наличными, Kaspi, банковской картой и безналичным расчётом.')

@section('canonical')
<link rel="canonical" href="{{ route('delivery-payment') }}">
@endsection

@section('breadcrumbs')
<x-ui.breadcrumbs :items="[['name' => 'Доставка и оплата']]"/>
@endsection

@section('content')
<section class="service-page">
    <div class="container">
        <header class="service-intro">
            <h1>Доставка и оплата</h1>
            <p>Удобные способы получения и оплаты заказа для частных покупателей и организаций.</p>
        </header>

        <div class="service-grid">
            <article class="service-card" id="delivery">
                <div class="service-icon">01</div>
                <h2>Доставка</h2>
                <ul>
                    <li><strong>По Алматы:</strong> 1–2 дня.</li>
                    <li><strong>По Казахстану:</strong> транспортными компаниями.</li>
                </ul>
            </article>

            <article class="service-card" id="payment">
                <div class="service-icon">02</div>
                <h2>Оплата</h2>
                <ul>
                    <li>Наличные.</li>
                    <li>Kaspi.</li>
                    <li>Банковская карта.</li>
                    <li>Безналичный расчёт.</li>
                </ul>
            </article>

            <article class="service-card" id="warranty">
                <div class="service-icon">03</div>
                <h2>Гарантия</h2>
                <p>На офисные кресла предоставляется гарантия. Срок и условия зависят от выбранной модели и производителя.</p>
            </article>

            <article class="service-card" id="legal">
                <div class="service-icon">04</div>
                <h2>Для юридических лиц</h2>
                <p>Договор, счёт на оплату, закрывающие документы и НДС.</p>
            </article>
        </div>
    </div>
</section>

<style>
.service-page{padding:28px 0 56px}.service-intro{max-width:760px;margin-bottom:26px}
.service-intro h1{font-size:clamp(1.75rem,4vw,2.3rem);font-weight:800;margin-bottom:8px}.service-intro p{color:#666;line-height:1.65}
.service-grid{display:grid;grid-template-columns:1fr;gap:14px}.service-card{padding:22px;background:#fff;border:1px solid #e7e5e4;border-radius:16px}
.service-icon{display:flex;align-items:center;justify-content:center;width:38px;height:38px;margin-bottom:16px;border-radius:10px;background:#fff3e0;color:#e67a00;font-weight:800;font-size:13px}
.service-card h2{font-size:19px;margin-bottom:10px}.service-card p,.service-card li{font-size:14px;color:#57534e;line-height:1.7}
.service-card ul{padding-left:19px}.service-card li+li{margin-top:5px}
@media(min-width:768px){.service-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.service-card{padding:26px}}
</style>
@endsection

@section('schema')
<x-schema.breadcrumbs :items="[['name' => 'Доставка и оплата', 'url' => route('delivery-payment')]]"/>
@endsection
