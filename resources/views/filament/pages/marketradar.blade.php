<x-filament-panels::page>
    <div style="display:grid;gap:16px">
        <x-filament::section heading="Информация о фиде">
            @php($lastLog = $this->lastLog())
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
                <div style="padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff">
                    <div style="font-size:12px;color:#6b7280">URL фида</div>
                    <div style="font-size:13px;font-weight:600;color:#111827;word-break:break-all">{{ $this->feedUrl() ?: 'Не задан' }}</div>
                </div>
                <div style="padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff">
                    <div style="font-size:12px;color:#6b7280">Статус последней проверки</div>
                    <div style="font-size:18px;font-weight:700;color:#111827">{{ $feedInfo['status'] ?? ($lastLog?->status ?? 'Нет данных') }}</div>
                </div>
                <div style="padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff">
                    <div style="font-size:12px;color:#6b7280">Дата последней проверки</div>
                    <div style="font-size:18px;font-weight:700;color:#111827">{{ $feedInfo['checked_at'] ?? ($lastLog?->created_at?->format('Y-m-d H:i') ?? 'Нет данных') }}</div>
                </div>
                <div style="padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff">
                    <div style="font-size:12px;color:#6b7280">Offer в XML</div>
                    <div style="font-size:18px;font-weight:700;color:#111827">{{ $feedInfo['offers_count'] ?? 'Проверьте фид' }}</div>
                </div>
                <div style="padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff">
                    <div style="font-size:12px;color:#6b7280">Matched товаров</div>
                    <div style="font-size:18px;font-weight:700;color:#16a34a">{{ $feedInfo['matched_count'] ?? 'Проверьте фид' }}</div>
                </div>
                <div style="padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff">
                    <div style="font-size:12px;color:#6b7280">Not found</div>
                    <div style="font-size:18px;font-weight:700;color:#dc2626">{{ $feedInfo['not_found_count'] ?? 'Проверьте фид' }}</div>
                </div>
            </div>
            @if(!empty($feedInfo['error']))
                <div style="margin-top:14px;padding:12px;border:1px solid #fecaca;border-radius:10px;background:#fef2f2;color:#991b1b">
                    {{ $feedInfo['error'] }}
                </div>
            @endif
        </x-filament::section>

        <x-filament::section heading="Действия">
            <div style="display:grid;gap:14px">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
                    <label style="display:grid;gap:6px">
                        <span style="font-size:13px;font-weight:600;color:#374151">SKU</span>
                        <input wire:model.defer="sku" type="text" placeholder="1361A" style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:10px 12px">
                    </label>
                    <label style="display:grid;gap:6px">
                        <span style="font-size:13px;font-weight:600;color:#374151">Лимит для массовых действий</span>
                        <input wire:model.defer="limit" type="number" min="1" max="100" style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:10px 12px">
                    </label>
                </div>

                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <x-filament::button wire:click="checkFeed" color="gray" icon="heroicon-o-signal">Проверить фид</x-filament::button>
                    <x-filament::button wire:click="dryRunSku" color="gray" icon="heroicon-o-magnifying-glass">Dry-run SKU</x-filament::button>
                    <x-filament::button wire:click="syncSku" color="success" icon="heroicon-o-arrow-path" wire:confirm="Обновить один товар из MarketRadar?">Sync SKU</x-filament::button>
                    <x-filament::button wire:click="bulkDryRun" color="gray" icon="heroicon-o-table-cells">Массовый dry-run</x-filament::button>
                    <x-filament::button wire:click="syncPricesStock" color="warning" icon="heroicon-o-banknotes" wire:confirm="Обновить цены и остатки для выбранного лимита?">Обновить цены и остатки</x-filament::button>
                    <x-filament::button wire:click="syncPhotos" color="warning" icon="heroicon-o-photo" wire:confirm="Загрузить фото для выбранного лимита?">Загрузить фото</x-filament::button>
                    <x-filament::button wire:click="syncAll" color="success" icon="heroicon-o-rocket-launch" wire:confirm="Обновить все данные MarketRadar для выбранного лимита?">Обновить все</x-filament::button>
                </div>

                <div wire:loading style="font-size:14px;color:#d97706">MarketRadar выполняет действие, подождите...</div>

                @if($commandOutput !== '')
                    <pre style="max-height:360px;overflow:auto;white-space:pre-wrap;background:#111827;color:#f9fafb;border-radius:12px;padding:14px;font-size:12px;line-height:1.5">{{ $commandOutput }}</pre>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section heading="Последние логи">
            @php($logs = $this->recentLogs())
            @if($logs->isEmpty())
                <div style="padding:16px;border:1px solid #e5e7eb;border-radius:12px;background:#f9fafb;color:#6b7280">Нет данных</div>
            @else
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:13px">
                        <thead>
                            <tr style="border-bottom:1px solid #e5e7eb;background:#f9fafb">
                                <th style="padding:10px;text-align:left">Дата</th>
                                <th style="padding:10px;text-align:left">SKU</th>
                                <th style="padding:10px;text-align:left">Status</th>
                                <th style="padding:10px;text-align:left">matched_by</th>
                                <th style="padding:10px;text-align:left">Цена</th>
                                <th style="padding:10px;text-align:left">Остаток</th>
                                <th style="padding:10px;text-align:left">Фото</th>
                                <th style="padding:10px;text-align:left">Ошибка</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr style="border-bottom:1px solid #eef2f7">
                                    <td style="padding:10px;white-space:nowrap">{{ $log->created_at?->format('Y-m-d H:i') ?? 'Нет данных' }}</td>
                                    <td style="padding:10px;font-family:monospace">{{ $log->sku ?? 'Нет данных' }}</td>
                                    <td style="padding:10px">{{ $log->status ?? 'Нет данных' }}</td>
                                    <td style="padding:10px">{{ $log->matched_by ?? 'Нет данных' }}</td>
                                    <td style="padding:10px;white-space:nowrap">{{ $log->old_price ?? '—' }} → {{ $log->new_price ?? '—' }}</td>
                                    <td style="padding:10px;white-space:nowrap">{{ $log->old_quantity ?? '—' }} → {{ $log->new_quantity ?? '—' }}</td>
                                    <td style="padding:10px;white-space:nowrap">{{ $log->photos_found ?? 0 }} / {{ $log->photos_saved ?? 0 }}</td>
                                    <td style="padding:10px;max-width:320px;color:#b91c1c">{{ \Illuminate\Support\Str::limit((string) $log->error_message, 140) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
