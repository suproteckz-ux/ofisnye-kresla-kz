<x-filament-panels::page>

    {{-- Настройки импорта --}}
    <x-filament::section heading="Настройки импорта XML фида">
        <div style="display:flex;flex-direction:column;gap:16px">

            <div>
                <label style="display:block;font-size:14px;font-weight:500;color:#374151;margin-bottom:6px">
                    URL XML фида
                </label>
                <input type="url"
                       wire:model.defer="xmlUrl"
                       placeholder="https://net-bazar.kz/google_merchant_center.xml?..."
                       style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;outline:none">
                <p style="font-size:12px;color:#9ca3af;margin-top:4px">
                    По умолчанию берётся из IMPORT_XML_URL в .env
                </p>
            </div>

            <div style="display:flex;gap:24px;flex-wrap:wrap">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" wire:model.defer="pricesOnly"
                           style="width:16px;height:16px;accent-color:#d97706">
                    <span style="font-size:14px;color:#374151">Только цены и наличие</span>
                    <span style="font-size:12px;color:#9ca3af">(без создания новых товаров)</span>
                </label>

                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" wire:model.defer="noImages"
                           style="width:16px;height:16px;accent-color:#d97706">
                    <span style="font-size:14px;color:#374151">Не загружать фото</span>
                    <span style="font-size:12px;color:#9ca3af">(быстрее, только данные)</span>
                </label>
            </div>

            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <x-filament::button wire:click="preview" color="gray" icon="heroicon-o-eye">
                    Предварительный просмотр
                </x-filament::button>

                <x-filament::button wire:click="runImport" color="warning" icon="heroicon-o-arrow-path"
                    wire:confirm="Запустить импорт товаров из XML фида? Это может занять несколько минут.">
                    🚀 Импортировать товары
                </x-filament::button>
            </div>

            <div wire:loading style="display:flex;align-items:center;gap:8px;color:#d97706;font-size:14px">
                <svg style="width:16px;height:16px;animation:spin 1s linear infinite" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Выполняется импорт, пожалуйста подождите...
            </div>
        </div>
    </x-filament::section>

    {{-- Результат --}}
    @if($report)

    @if($report['mode'] === 'import')
    <x-filament::section heading="📊 Отчёт об импорте">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:24px">
            @foreach([
                ['Найдено в фиде',   $report['total'],    '#6366f1'],
                ['✅ Создано новых',  $report['created'],  '#22c55e'],
                ['🔄 Обновлено',      $report['updated'],  '#f59e0b'],
                ['⏭️ Пропущено',      $report['skipped'],  '#94a3b8'],
                ['❌ Ошибок',         $report['errors'],   '#ef4444'],
                ['🖼️ Загружено фото', $report['images'],   '#8b5cf6'],
            ] as [$label, $value, $color])
            <div style="background:#f9fafb;border-radius:12px;padding:16px;text-align:center;border:1px solid #e5e7eb">
                <div style="font-size:1.75rem;font-weight:800;color:{{ $color }}">{{ $value }}</div>
                <div style="font-size:12px;color:#6b7280;margin-top:4px">{{ $label }}</div>
            </div>
            @endforeach
        </div>

        <div style="font-size:13px;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:12px;display:flex;gap:24px;flex-wrap:wrap">
            <span>⏱️ Время: <strong>{{ $report['elapsed'] }} сек</strong></span>
            <span>🗃️ Batch ID: <strong>#{{ $report['batch_id'] }}</strong></span>
        </div>

        @if($report['errors'] > 0)
        <div style="margin-top:12px;padding:12px;background:#fef2f2;border-radius:8px;border:1px solid #fee2e2">
            <p style="font-size:13px;color:#dc2626">
                ⚠️ Возникло {{ $report['errors'] }} ошибок. Подробности смотрите в логах.
            </p>
        </div>
        @endif
    </x-filament::section>
    @endif

    @if($report['mode'] === 'preview')
    <x-filament::section heading="👁️ Предварительный просмотр фида (первые 20 товаров)">
        <p style="font-size:14px;color:#6b7280;margin-bottom:16px">
            В фиде найдено: <strong>{{ $report['total'] }}</strong> товаров. Данные не сохранялись.
        </p>
        @if(!empty($report['sample']))
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <thead>
                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <th style="padding:8px 12px;text-align:left;font-weight:600;color:#374151">SKU</th>
                        <th style="padding:8px 12px;text-align:left;font-weight:600;color:#374151">Название</th>
                        <th style="padding:8px 12px;text-align:right;font-weight:600;color:#374151">Цена</th>
                        <th style="padding:8px 12px;text-align:left;font-weight:600;color:#374151">Категория</th>
                        <th style="padding:8px 12px;text-align:left;font-weight:600;color:#374151">Материал</th>
                        <th style="padding:8px 12px;text-align:center;font-weight:600;color:#374151">Фото</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['sample'] as $i => $row)
                    <tr style="{{ $i % 2 === 0 ? 'background:#fff' : 'background:#f9fafb' }};border-bottom:1px solid #e5e7eb">
                        <td style="padding:8px 12px;color:#6b7280;font-family:monospace">{{ $row['sku'] }}</td>
                        <td style="padding:8px 12px;color:#111827">{{ $row['name'] }}</td>
                        <td style="padding:8px 12px;text-align:right;font-weight:600;color:#111827">{{ $row['price'] }}</td>
                        <td style="padding:8px 12px;color:#6b7280">{{ $row['category'] }}</td>
                        <td style="padding:8px 12px;color:#6b7280">{{ $row['material'] }}</td>
                        <td style="padding:8px 12px;text-align:center">{{ $row['has_photo'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </x-filament::section>
    @endif

    @endif

    {{-- Инструкция --}}
    <x-filament::section heading="ℹ️ Как использовать">
        <div style="font-size:14px;color:#6b7280;line-height:1.8">
            <ol style="padding-left:20px;display:flex;flex-direction:column;gap:8px">
                <li>Нажмите <strong>«Предварительный просмотр»</strong> — фид загрузится без сохранения, вы увидите список товаров.</li>
                <li>Если всё верно — нажмите <strong>«Импортировать товары»</strong>.</li>
                <li>Дождитесь отчёта: создано / обновлено / ошибок / время.</li>
                <li>Для обновления только цен — включите «Только цены и наличие».</li>
                <li>Для быстрого импорта без фото — включите «Не загружать фото».</li>
            </ol>
            <p style="margin-top:12px;padding:12px;background:#fef3c7;border-radius:8px;color:#92400e">
                ⚡ <strong>Важно:</strong> Импорт — только ручной. Автоматического расписания нет.
                Запускайте вручную при необходимости обновить ассортимент или цены.
            </p>
        </div>
    </x-filament::section>

    <style>
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>

</x-filament-panels::page>
