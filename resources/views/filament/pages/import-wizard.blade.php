{{--
    import-wizard.blade.php
    
    ИСПРАВЛЕНИЯ v3 (причина сломанного UI на скриншоте):
    
    1. УБРАНЫ все inline <svg> теги — они рендерились без ограничений
       размера, потому что Filament не применяет к ним свои стили.
       Именно они были "огромными SVG на весь экран".
    
    2. Dropzone с кастомным Alpine.js и кастомными SVG → заменён на
       простой нативный <input type="file"> без декораций.
    
    3. Используются ТОЛЬКО стандартные Filament компоненты:
       - x-filament-panels::page
       - x-filament::section
       - x-filament::button
       Никакого кастомного HTML, который ломает Filament rendering.
    
    4. Tailwind-классы используются только в простом контексте
       (text-, bg-, border-, grid-, flex-) — без SVG-зависимых классов.
--}}
<x-filament-panels::page>

{{-- ════════════════════════════════════════════════════
     СТЕППЕР (только шаги 1-4)
════════════════════════════════════════════════════ --}}
@if ($step <= 4)
    <div class="mb-6">
        <nav aria-label="Прогресс импорта">
            <ol class="flex items-center gap-0">
                @foreach ([1 => 'Файл', 2 => 'Просмотр', 3 => 'Режим', 4 => 'Маппинг'] as $n => $label)
                    <li class="{{ $n < 4 ? 'flex-1' : '' }} flex items-center">
                        <div class="flex flex-col items-center">
                            <span
                                class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold
                                    {{ $step > $n ? 'bg-primary-500 text-white' : ($step === $n ? 'bg-primary-600 text-white ring-4 ring-primary-100' : 'bg-gray-100 text-gray-400') }}"
                            >
                                @if ($step > $n) ✓ @else {{ $n }} @endif
                            </span>
                            <span class="mt-1 text-xs font-medium {{ $step >= $n ? 'text-primary-700' : 'text-gray-400' }}">
                                {{ $label }}
                            </span>
                        </div>
                        @if ($n < 4)
                            <div class="mb-4 flex-1 mx-2 h-0.5 rounded {{ $step > $n ? 'bg-primary-400' : 'bg-gray-200' }}"></div>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>
@endif

{{-- ════════════════════════════════════════════════════
     ШАГ 1: Загрузка файла
════════════════════════════════════════════════════ --}}
@if ($step === 1)
    <x-filament::section>
        <x-slot name="heading">Шаг 1 — Загрузка файла</x-slot>
        <x-slot name="description">
            Поддерживаются форматы: XLS, XLSX, CSV. Максимальный размер файла: 50 MB.
        </x-slot>

        <div class="space-y-5">

            {{-- Поле выбора файла --}}
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">
                    Файл импорта <span class="text-red-500">*</span>
                </label>
                <input
                    type="file"
                    wire:model="uploadedFile"
                    accept=".xls,.xlsx,.csv"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm
                           text-gray-700 shadow-sm transition
                           file:mr-4 file:cursor-pointer file:rounded-lg file:border-0
                           file:bg-primary-50 file:px-4 file:py-2 file:text-sm
                           file:font-semibold file:text-primary-700
                           hover:border-gray-400 hover:file:bg-primary-100
                           focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                />
                <p class="mt-1 text-xs text-gray-400">XLS, XLSX или CSV — до 50 MB</p>

                @error('uploadedFile')
                    <p class="mt-1.5 text-sm text-danger-600">{{ $message }}</p>
                @enderror

                {{-- Индикатор загрузки Livewire --}}
                <div wire:loading wire:target="uploadedFile" class="mt-2 text-sm text-gray-500">
                    Загрузка файла...
                </div>
            </div>

            {{-- Подсказка по формату 1С --}}
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                <p class="mb-2 text-sm font-semibold text-blue-800">Ожидаемый формат файла 1С:</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-blue-700">
                        <thead>
                            <tr class="border-b border-blue-200">
                                <th class="py-1 pr-4 text-left">Ед. изм.</th>
                                <th class="py-1 pr-4 text-left">Номенклатура</th>
                                <th class="py-1 pr-4 text-left">Номенклатура.Код</th>
                                <th class="py-1 pr-4 text-left">Остаток на складе</th>
                                <th class="py-1 text-left">Розничная цена</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="text-blue-600">
                                <td class="py-1 pr-4">шт</td>
                                <td class="py-1 pr-4">Офисное кресло Руководитель 120</td>
                                <td class="py-1 pr-4 font-mono">РТ-00001272</td>
                                <td class="py-1 pr-4">17</td>
                                <td class="py-1">7 300</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-blue-500">
                    Первая строка файла должна содержать заголовки. Порядок колонок не важен.
                </p>
            </div>

            {{-- Кнопка --}}
            <div class="flex justify-end">
                <x-filament::button
                    wire:click="uploadFile"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-60 cursor-not-allowed"
                    size="lg"
                >
                    <span wire:loading.remove wire:target="uploadFile">Загрузить и продолжить →</span>
                    <span wire:loading wire:target="uploadFile">Обработка...</span>
                </x-filament::button>
            </div>

        </div>
    </x-filament::section>
@endif

{{-- ════════════════════════════════════════════════════
     ШАГ 2: Предпросмотр
════════════════════════════════════════════════════ --}}
@if ($step === 2)
    <x-filament::section>
        <x-slot name="heading">Шаг 2 — Предпросмотр файла</x-slot>
        <x-slot name="description">
            Файл: <strong>{{ $fileName }}</strong>
            &nbsp;|&nbsp; Строк: <strong>{{ number_format($totalRows) }}</strong>
            &nbsp;|&nbsp; Колонок: <strong>{{ count($fileColumns) }}</strong>
        </x-slot>

        <div class="space-y-4">

            {{-- Обнаруженные колонки --}}
            <div>
                <p class="mb-2 text-sm font-medium text-gray-700">Обнаруженные колонки:</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($fileColumns as $col)
                        <span class="rounded-full border border-gray-200 bg-gray-100 px-3 py-1 font-mono text-xs text-gray-600">
                            {{ $col }}
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- Таблица --}}
            @if (! empty($previewRows))
                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-100 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="w-8 px-3 py-2.5 text-left text-gray-400">#</th>
                                @foreach ($fileColumns as $col)
                                    <th class="whitespace-nowrap px-3 py-2.5 text-left font-semibold text-gray-700">
                                        {{ $col }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 bg-white">
                            @foreach ($previewRows as $i => $row)
                                <tr class="{{ $i % 2 ? 'bg-gray-50/40' : '' }}">
                                    <td class="px-3 py-2 font-mono text-gray-300">{{ $i + 1 }}</td>
                                    @foreach ($fileColumns as $col)
                                        <td class="max-w-[180px] truncate whitespace-nowrap px-3 py-2 text-gray-700"
                                            title="{{ $row[$col] ?? '' }}">
                                            {{ $row[$col] ?? '—' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($totalRows > 20)
                    <p class="text-right text-xs text-gray-400">Показано 20 из {{ number_format($totalRows) }} строк</p>
                @endif
            @endif

            <div class="flex justify-between">
                <x-filament::button color="gray" wire:click="backToStep1">← Назад</x-filament::button>
                <x-filament::button wire:click="confirmPreview" size="lg">Данные верны →</x-filament::button>
            </div>

        </div>
    </x-filament::section>
@endif

{{-- ════════════════════════════════════════════════════
     ШАГ 3: Режим импорта
════════════════════════════════════════════════════ --}}
@if ($step === 3)
    <x-filament::section>
        <x-slot name="heading">Шаг 3 — Режим импорта</x-slot>
        <x-slot name="description">Выберите, как обрабатывать данные из файла.</x-slot>

        <div class="space-y-3">

            {{-- Режим: prices_only --}}
            <label class="block cursor-pointer" wire:click="$set('importMode', 'prices_only')">
                <div class="rounded-xl border-2 p-4 transition-colors
                            {{ $importMode === 'prices_only'
                                ? 'border-primary-500 bg-primary-50'
                                : 'border-gray-200 bg-white hover:border-gray-300' }}">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2
                                    {{ $importMode === 'prices_only'
                                        ? 'border-primary-500 bg-primary-500'
                                        : 'border-gray-300' }}">
                            @if ($importMode === 'prices_only')
                                <div class="h-2 w-2 rounded-full bg-white"></div>
                            @endif
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">
                                Обновление из 1С
                                <span class="ml-2 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                    Рекомендуется для ежедневного обновления
                                </span>
                            </p>
                            <p class="mt-1 text-sm text-gray-600">
                                Обновляет <strong>цену, остаток и наличие</strong> по SKU.
                                Названия, описания, SEO и изображения — не трогает.
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1.5 text-xs">
                                @foreach (['✓ price', '✓ quantity', '✓ in_stock'] as $f)
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 font-medium text-green-700">{{ $f }}</span>
                                @endforeach
                                @foreach (['✗ name', '✗ SEO', '✗ images'] as $f)
                                    <span class="rounded-full bg-red-50 px-2 py-0.5 text-red-500">{{ $f }}</span>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-amber-600">
                                Если SKU не найден — строка записывается в import_errors.
                            </p>
                        </div>
                    </div>
                </div>
            </label>

            {{-- Режим: full --}}
            <label class="block cursor-pointer" wire:click="$set('importMode', 'full')">
                <div class="rounded-xl border-2 p-4 transition-colors
                            {{ $importMode === 'full'
                                ? 'border-primary-500 bg-primary-50'
                                : 'border-gray-200 bg-white hover:border-gray-300' }}">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2
                                    {{ $importMode === 'full'
                                        ? 'border-primary-500 bg-primary-500'
                                        : 'border-gray-300' }}">
                            @if ($importMode === 'full')
                                <div class="h-2 w-2 rounded-full bg-white"></div>
                            @endif
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">Полный импорт</p>
                            <p class="mt-1 text-sm text-gray-600">
                                Создаёт новые товары и обновляет все поля существующих.
                                Используется для первоначального наполнения каталога.
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1.5 text-xs">
                                @foreach (['✓ Создание', '✓ Обновление всех полей', '✓ Категории', '✓ Бренды', '✓ Изображения по URL'] as $f)
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 font-medium text-green-700">{{ $f }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </label>

        </div>

        <div class="mt-5 flex justify-between">
            <x-filament::button color="gray" wire:click="backToStep2">← Назад</x-filament::button>
            <x-filament::button wire:click="selectMode" size="lg">Настроить колонки →</x-filament::button>
        </div>

    </x-filament::section>
@endif

{{-- ════════════════════════════════════════════════════
     ШАГ 4: Маппинг колонок
════════════════════════════════════════════════════ --}}
@if ($step === 4)
    <x-filament::section>
        <x-slot name="heading">Шаг 4 — Сопоставление колонок</x-slot>
        <x-slot name="description">
            Режим: <strong>{{ $importMode === 'prices_only' ? 'Обновление из 1С' : 'Полный импорт' }}</strong>.
            Поля со <span class="text-red-500">*</span> обязательны.
        </x-slot>

        <div class="space-y-5">

            {{-- Шаблоны --}}
            @php $templates = \App\Models\ImportColumnTemplate::where('type', $importMode)->get(); @endphp
            @if ($templates->count())
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Сохранённые шаблоны</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($templates as $tpl)
                            <button
                                wire:click="loadTemplate({{ $tpl->id }})"
                                class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors
                                       {{ $templateId === $tpl->id
                                           ? 'border-primary-500 bg-white text-primary-700 shadow-sm'
                                           : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300' }}"
                            >
                                {{ $tpl->name }}
                                @if ($tpl->is_default) <span class="text-green-600 text-xs">★</span> @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Таблица маппинга --}}
            <div class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200">
                <div class="grid grid-cols-12 gap-4 bg-gray-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <div class="col-span-4">Поле системы</div>
                    <div class="col-span-6">Колонка в файле</div>
                    <div class="col-span-2 text-right">Статус</div>
                </div>
                @foreach ($this->getSystemFields() as $field => $variants)
                    @php $mapped = ! empty($columnMap[$field]); $req = $this->isRequired($field); @endphp
                    <div class="grid grid-cols-12 items-center gap-4 bg-white px-4 py-3 hover:bg-gray-50/60">
                        <div class="col-span-4">
                            <span class="text-sm font-medium text-gray-800">
                                {{ $this->getMappingLabel($field) }}
                            </span>
                        </div>
                        <div class="col-span-6">
                            <select
                                wire:model.live="columnMap.{{ $field }}"
                                class="w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition
                                       focus:outline-none focus:ring-2 focus:ring-primary-400
                                       {{ $mapped ? 'border-primary-300 bg-primary-50/40' : ($req ? 'border-red-300 bg-red-50/30' : 'border-gray-200') }}"
                            >
                                <option value="">— не использовать —</option>
                                @foreach ($fileColumns as $col)
                                    <option value="{{ $col }}" {{ ($columnMap[$field] ?? '') === $col ? 'selected' : '' }}>
                                        {{ $col }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2 text-right">
                            @if ($mapped)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">OK</span>
                            @elseif ($req)
                                <span class="text-xs font-medium text-red-500">обяз.</span>
                            @else
                                <span class="text-xs text-gray-300">—</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Сохранить шаблон --}}
            <div>
                <button
                    wire:click="toggleSaveForm"
                    class="text-sm text-gray-500 underline-offset-2 hover:text-primary-600 hover:underline"
                >
                    {{ $showSaveForm ? 'Скрыть' : '+ Сохранить маппинг как шаблон' }}
                </button>

                @if ($showSaveForm)
                    <div class="mt-3 flex gap-2">
                        <input
                            type="text"
                            wire:model="templateName"
                            wire:keydown.enter="saveTemplate"
                            placeholder="Название шаблона"
                            class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm
                                   focus:border-primary-400 focus:outline-none focus:ring-1 focus:ring-primary-400"
                        />
                        <x-filament::button wire:click="saveTemplate" size="sm">Сохранить</x-filament::button>
                    </div>
                    @error('templateName')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    @if ($templateId)
                        <button wire:click="setDefaultTemplate" class="mt-1.5 text-xs text-gray-400 hover:text-gray-600">
                            Сделать шаблоном по умолчанию
                        </button>
                    @endif
                @endif
            </div>

            {{-- Кнопки --}}
            <div class="flex justify-between border-t border-gray-100 pt-4">
                <x-filament::button color="gray" wire:click="backToStep2">← Назад</x-filament::button>
                <x-filament::button
                    wire:click="startImport"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-60 cursor-not-allowed"
                    size="lg"
                    :color="$importMode === 'prices_only' ? 'success' : 'primary'"
                >
                    <span wire:loading.remove wire:target="startImport">
                        Запустить импорт — {{ number_format($totalRows) }} строк
                    </span>
                    <span wire:loading wire:target="startImport">Запуск...</span>
                </x-filament::button>
            </div>

        </div>
    </x-filament::section>
@endif

{{-- ════════════════════════════════════════════════════
     ШАГ 5: Прогресс и результат
════════════════════════════════════════════════════ --}}
@if ($step === 5)
    <div
        x-data="{ polling: true }"
        x-init="
            const iv = setInterval(() => {
                $wire.pollProgress();
                const s = $wire.progress.status;
                if (s === 'done' || s === 'failed') {
                    clearInterval(iv);
                    polling = false;
                }
            }, 3000);
        "
    >

        {{-- ЗАВЕРШЁН --}}
        @if (($progress['status'] ?? '') === 'done')
            <x-filament::section>
                <x-slot name="heading">Импорт завершён</x-slot>

                @php $batch = \App\Models\ImportBatch::find($batchId); @endphp
                @if ($batch)
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        @foreach ([
                            ['Всего строк',      $batch->total_rows,      'gray'],
                            ['Создано',          $batch->created_count,   'green'],
                            ['Обновлено',        $batch->updated_count,   'blue'],
                            ['Пропущено',        $batch->skipped_count,   'yellow'],
                            ['Не найдено (SKU)', $batch->not_found_count, 'orange'],
                            ['Ошибок',           $batch->error_count,     'red'],
                        ] as [$lbl, $val, $clr])
                            <div class="rounded-xl border p-4 text-center
                                        {{ $clr === 'gray'   ? 'border-gray-200 bg-gray-50 text-gray-700' : '' }}
                                        {{ $clr === 'green'  ? 'border-green-200 bg-green-50 text-green-700' : '' }}
                                        {{ $clr === 'blue'   ? 'border-blue-200 bg-blue-50 text-blue-700' : '' }}
                                        {{ $clr === 'yellow' ? 'border-yellow-200 bg-yellow-50 text-yellow-700' : '' }}
                                        {{ $clr === 'orange' ? 'border-orange-200 bg-orange-50 text-orange-700' : '' }}
                                        {{ $clr === 'red'    ? 'border-red-200 bg-red-50 text-red-700' : '' }}">
                                <p class="text-2xl font-black">{{ number_format($val ?? 0) }}</p>
                                <p class="mt-1 text-xs font-medium opacity-80">{{ $lbl }}</p>
                            </div>
                        @endforeach
                    </div>

                    {{-- Изменения цен --}}
                    @if (!empty($batch->price_changes) && count($batch->price_changes) > 0)
                        <div class="mt-5">
                            <p class="mb-2 text-sm font-semibold text-gray-800">
                                Изменения цен ({{ count($batch->price_changes) }})
                            </p>
                            <div class="max-h-60 overflow-y-auto overflow-x-auto rounded-xl border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-100 text-xs">
                                    <thead class="sticky top-0 bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-gray-500">SKU</th>
                                            <th class="px-3 py-2 text-left text-gray-500">Товар</th>
                                            <th class="px-3 py-2 text-right text-gray-500">Было</th>
                                            <th class="px-3 py-2 text-right text-gray-500">Стало</th>
                                            <th class="px-3 py-2 text-right text-gray-500">Δ</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 bg-white">
                                        @foreach (array_slice($batch->price_changes, 0, 100) as $ch)
                                            <tr>
                                                <td class="px-3 py-2 font-mono text-gray-400">{{ $ch['sku'] }}</td>
                                                <td class="max-w-[180px] truncate px-3 py-2 text-gray-700">{{ $ch['name'] }}</td>
                                                <td class="px-3 py-2 text-right text-gray-500">{{ number_format($ch['old'], 0, '.', ' ') }} ₸</td>
                                                <td class="px-3 py-2 text-right font-semibold text-gray-900">{{ number_format($ch['new'], 0, '.', ' ') }} ₸</td>
                                                <td class="px-3 py-2 text-right font-medium {{ ($ch['diff'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                                                    {{ ($ch['diff'] ?? 0) > 0 ? '+' : '' }}{{ number_format($ch['diff'] ?? 0, 0, '.', ' ') }} ₸
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if (($batch->error_count ?? 0) + ($batch->not_found_count ?? 0) > 0)
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                            {{ ($batch->error_count ?? 0) + ($batch->not_found_count ?? 0) }} записей с ошибками.
                            Детали — в журнале ошибок импорта.
                        </div>
                    @endif
                @endif

                <div class="mt-5">
                    <x-filament::button wire:click="resetWizard">Новый импорт</x-filament::button>
                </div>
            </x-filament::section>

        {{-- ОШИБКА --}}
        @elseif (($progress['status'] ?? '') === 'failed')
            <x-filament::section>
                <x-slot name="heading">Импорт завершился с ошибкой</x-slot>
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 font-mono text-sm text-red-700">
                    {{ $progress['error'] ?? 'Неизвестная ошибка. Смотрите storage/logs/laravel-*.log' }}
                </div>
                <div class="mt-4">
                    <x-filament::button wire:click="resetWizard" color="gray">Попробовать снова</x-filament::button>
                </div>
            </x-filament::section>

        {{-- В ПРОЦЕССЕ --}}
        @else
            <x-filament::section>
                <x-slot name="heading">Импорт выполняется...</x-slot>
                <x-slot name="description">{{ $fileName }} — {{ number_format($totalRows) }} строк</x-slot>

                <div class="space-y-4">
                    <div>
                        <div class="mb-1.5 flex justify-between text-sm text-gray-600">
                            <span>Прогресс</span>
                            <span class="font-bold text-primary-700">{{ $progress['percent'] ?? 0 }}%</span>
                        </div>
                        <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200">
                            <div
                                class="h-3 rounded-full bg-primary-500 transition-all duration-700"
                                style="width: {{ $progress['percent'] ?? 0 }}%"
                            ></div>
                        </div>
                    </div>

                    <p class="text-center text-sm text-gray-500">
                        Файл обрабатывается в фоне. Прогресс обновляется каждые 3 секунды.
                    </p>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        <p class="font-semibold">Если прогресс не двигается — проверьте Queue Worker:</p>
                        <code class="mt-1 block break-all rounded bg-amber-100 px-3 py-2 text-xs">
                            bash artisan83 queue:work --queue=imports-high,imports,imports-low --stop-when-empty
                        </code>
                    </div>
                </div>
            </x-filament::section>
        @endif

    </div>
@endif

</x-filament-panels::page>
