<?php

namespace App\Filament\Pages;

use App\Jobs\Import\ProcessImportJob;
use App\Models\ImportBatch;
use App\Models\ImportColumnTemplate;
use App\Services\Import\ColumnMapper;
use App\Services\Import\ImportFileParser;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

/**
 * ImportWizardPage — мастер импорта товаров из Excel/CSV (1С).
 *
 * ИСПРАВЛЕНИЯ v3 (по аудиту скриншота):
 * - Удалён кастомный dropzone с inline SVG — они рендерились без ограничений размера
 * - Убраны все inline <svg> теги — причина огромных иконок на весь экран
 * - Файловый инпут через Livewire WithFileUploads (wire:model="uploadedFile")
 * - Blade использует только x-filament::section и x-filament::button
 * - Никакого кастомного HTML, который конфликтует с Filament rendering
 */
class ImportWizardPage extends Page
{
    use WithFileUploads;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-arrow-up-tray';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Импорт';
    }

    public static function getNavigationLabel(): string
    {
        return 'Импорт товаров';
    }

    public function getTitle(): string
    {
        return 'Импорт товаров';
    }

    public static function getNavigationSort(): int
    {
        return 1;
    }

    protected string $view = 'filament.pages.import-wizard';

    // ── Состояние мастера ──────────────────────────────────────────────

    /** @var mixed TemporaryUploadedFile от Livewire WithFileUploads */
    public $uploadedFile = null;

    public int     $step            = 1;
    public ?string $filePath        = null;
    public string  $fileName        = '';
    public array   $previewRows     = [];
    public array   $fileColumns     = [];
    public int     $totalRows       = 0;
    public string  $importMode      = 'prices_only';
    public array   $columnMap       = [];
    public ?int    $batchId         = null;
    public array   $progress        = ['percent' => 0, 'status' => 'idle'];
    public ?int    $templateId      = null;
    public string  $templateName    = '';
    public bool    $showSaveForm    = false;

    // ── Вспомогательные методы ─────────────────────────────────────────

    public function getSystemFields(): array
    {
        return $this->importMode === 'full'
            ? ColumnMapper::FULL_FIELDS
            : ColumnMapper::PRICES_ONLY_FIELDS;
    }

    public function getMappingLabel(string $field): string
    {
        return match ($field) {
            'sku'               => 'SKU / Артикул *',
            'name'              => 'Название товара *',
            'price'             => 'Цена (розничная) *',
            'old_price'         => 'Старая цена',
            'quantity'          => 'Остаток на складе',
            'category'          => 'Категория',
            'brand'             => 'Бренд',
            'unit'              => 'Единица измерения',
            'short_description' => 'Краткое описание',
            'description'       => 'Полное описание',
            'image_url'         => 'URL изображения',
            'meta_title'        => 'Meta Title',
            'meta_description'  => 'Meta Description',
            default             => $field,
        };
    }

    public function isRequired(string $field): bool
    {
        return $this->importMode === 'prices_only'
            ? in_array($field, ['sku', 'price'])
            : in_array($field, ['sku', 'name']);
    }

    // ════════════════════════════════════════════════════════════════
    // ШАГ 1: Загрузка файла
    // ════════════════════════════════════════════════════════════════

    public function uploadFile(): void
    {
        $this->validate(
            [
                'uploadedFile' => [
                    'required',
                    'file',
                    'max:51200',
                    function ($attr, $value, $fail) {
                        $ext = strtolower($value->getClientOriginalExtension());
                        if (! in_array($ext, ['xls', 'xlsx', 'csv'])) {
                            $fail('Допустимые форматы: XLS, XLSX, CSV.');
                        }
                    },
                ],
            ],
            ['uploadedFile.required' => 'Выберите файл для загрузки.']
        );

        Storage::disk('public')->makeDirectory('imports');

        $ext      = strtolower($this->uploadedFile->getClientOriginalExtension());
        $safeName = 'import_' . now()->format('Ymd_His') . '_' . uniqid() . '.' . $ext;
        $stored   = $this->uploadedFile->storeAs('imports', $safeName, 'public');

        if (! $stored) {
            Notification::make()
                ->title('Ошибка сохранения файла')
                ->body('Не удалось сохранить файл. Проверьте права на папку storage/app/public/imports.')
                ->danger()
                ->persistent()
                ->send();
            return;
        }

        $this->filePath = $stored;
        $this->fileName = $this->uploadedFile->getClientOriginalName();

        $this->parseFile();
    }

    private function parseFile(): void
    {
        try {
            $parser = new ImportFileParser();

            $allRows           = $parser->parse($this->filePath);
            $this->totalRows   = count($allRows);
            $this->fileColumns = $parser->getColumns($this->filePath);
            $this->previewRows = array_slice($allRows, 0, 20);

            if (empty($this->fileColumns)) {
                throw new \RuntimeException(
                    'Файл пустой или первая строка не содержит заголовков колонок.'
                );
            }

            $mapper          = new ColumnMapper();
            $this->columnMap = $mapper->autoDetect($this->fileColumns, $this->importMode);

            $this->step = 2;

        } catch (\Throwable $e) {
            if ($this->filePath) {
                Storage::disk('public')->delete($this->filePath);
                $this->filePath = null;
            }
            Notification::make()
                ->title('Ошибка чтения файла')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    // ════════════════════════════════════════════════════════════════
    // ШАГ 2: Предпросмотр
    // ════════════════════════════════════════════════════════════════

    public function confirmPreview(): void
    {
        if (empty($this->fileColumns)) {
            Notification::make()->title('Нет данных для предпросмотра.')->danger()->send();
            return;
        }
        $this->step = 3;
    }

    public function backToStep1(): void
    {
        if ($this->filePath) {
            Storage::disk('public')->delete($this->filePath);
        }
        $this->uploadedFile = null;
        $this->filePath     = null;
        $this->fileName     = '';
        $this->previewRows  = [];
        $this->fileColumns  = [];
        $this->step         = 1;
    }

    // ════════════════════════════════════════════════════════════════
    // ШАГ 3: Режим импорта
    // ════════════════════════════════════════════════════════════════

    public function selectMode(): void
    {
        $mapper          = new ColumnMapper();
        $this->columnMap = $mapper->autoDetect($this->fileColumns, $this->importMode);

        $default = ImportColumnTemplate::getDefault($this->importMode);
        if ($default) {
            $this->columnMap  = $default->column_map;
            $this->templateId = $default->id;
        }

        $this->step = 4;
    }

    public function backToStep2(): void
    {
        $this->step = 2;
    }

    // ════════════════════════════════════════════════════════════════
    // ШАГ 4: Маппинг
    // ════════════════════════════════════════════════════════════════

    public function loadTemplate(int $id): void
    {
        $tpl = ImportColumnTemplate::find($id);
        if ($tpl) {
            $this->columnMap  = $tpl->column_map;
            $this->templateId = $id;
        }
    }

    public function toggleSaveForm(): void
    {
        $this->showSaveForm  = ! $this->showSaveForm;
        $this->templateName  = '';
    }

    public function saveTemplate(): void
    {
        $this->validate(['templateName' => 'required|string|max:100']);

        ImportColumnTemplate::create([
            'name'       => $this->templateName,
            'type'       => $this->importMode,
            'column_map' => $this->columnMap,
            'is_default' => false,
        ]);

        Notification::make()->title("Шаблон «{$this->templateName}» сохранён.")->success()->send();
        $this->showSaveForm = false;
        $this->templateName = '';
    }

    public function setDefaultTemplate(): void
    {
        if (! $this->templateId) return;
        ImportColumnTemplate::where('type', $this->importMode)->update(['is_default' => false]);
        ImportColumnTemplate::where('id', $this->templateId)->update(['is_default' => true]);
        Notification::make()->title('Шаблон установлен по умолчанию.')->success()->send();
    }

    public function startImport(): void
    {
        $required = $this->importMode === 'prices_only' ? ['sku', 'price'] : ['sku', 'name'];

        foreach ($required as $f) {
            if (empty($this->columnMap[$f])) {
                Notification::make()
                    ->title("Обязательное поле не сопоставлено: «{$f}»")
                    ->body('Выберите колонку из файла.')
                    ->danger()
                    ->send();
                return;
            }
        }

        if (! $this->filePath || ! Storage::disk('public')->exists($this->filePath)) {
            Notification::make()
                ->title('Файл не найден')
                ->body('Сессия истекла — начните заново.')
                ->danger()
                ->send();
            $this->resetWizard();
            return;
        }

        try {
            $batch = ImportBatch::create([
                'type'       => $this->importMode,
                'filename'   => $this->fileName,
                'filepath'   => $this->filePath,
                'column_map' => $this->columnMap,
                'status'     => 'pending',
                'user_id'    => Auth::id(),
            ]);

            $this->batchId = $batch->id;

            Cache::put("import_progress_{$batch->id}", [
                'percent' => 0,
                'status'  => 'processing',
            ], 7200);

            ProcessImportJob::dispatch($batch->id)->onQueue('imports-high');

            $this->step     = 5;
            $this->progress = ['percent' => 0, 'status' => 'processing'];

            Notification::make()
                ->title('Импорт запущен')
                ->body("{$this->fileName} — {$this->totalRows} строк")
                ->success()
                ->send();

            Log::info("Import batch #{$batch->id} dispatched", [
                'type'     => $this->importMode,
                'filename' => $this->fileName,
                'rows'     => $this->totalRows,
                'user_id'  => Auth::id(),
            ]);

        } catch (\Throwable $e) {
            Log::error('Import dispatch failed', ['error' => $e->getMessage()]);
            Notification::make()
                ->title('Не удалось запустить импорт')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    // ════════════════════════════════════════════════════════════════
    // ШАГ 5: Прогресс
    // ════════════════════════════════════════════════════════════════

    public function pollProgress(): void
    {
        if (! $this->batchId) return;

        $cached = Cache::get("import_progress_{$this->batchId}");

        if ($cached) {
            $this->progress = $cached;
        } else {
            $b = ImportBatch::find($this->batchId);
            if ($b) {
                $this->progress = [
                    'percent' => 100,
                    'status'  => $b->status === 'done' ? 'done' : $b->status,
                ];
            }
        }
    }

    public function resetWizard(): void
    {
        if ($this->filePath) {
            Storage::disk('public')->delete($this->filePath);
        }
        $this->uploadedFile = null;
        $this->filePath     = null;
        $this->fileName     = '';
        $this->step         = 1;
        $this->previewRows  = [];
        $this->fileColumns  = [];
        $this->totalRows    = 0;
        $this->importMode   = 'prices_only';
        $this->columnMap    = [];
        $this->batchId      = null;
        $this->progress     = ['percent' => 0, 'status' => 'idle'];
        $this->templateId   = null;
        $this->showSaveForm = false;
        $this->templateName = '';
    }
}
