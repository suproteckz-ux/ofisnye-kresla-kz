<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;

/**
 * ImportFileParser
 *
 * Читает файлы XLS / XLSX / CSV и возвращает массив ассоциативных строк.
 *
 * ──────────────────────────────────────────────────────────────────
 * ИСТОРИЯ ИСПРАВЛЕНИЙ
 * ──────────────────────────────────────────────────────────────────
 *
 * ПРОБЛЕМА (найдена по реальному файлу 1С):
 *
 *   Файл "Остатки__Розница_Экспорт__XLS___4_.xls" является бинарным
 *   форматом BIFF8 (OLE2 Compound Document), а не ZIP-архивом.
 *
 *   FastExcel / openspout поддерживает только: csv, xlsx, ods.
 *   При передаче .xls он открывает файл как XLSX (ZIP), получает мусор,
 *   и случайно парсит только 3 из 5 колонок.
 *
 *   ПЛЮС: файл 1С имеет ДВУСТРОЧНЫЙ заголовок:
 *
 *     Строка 0: "Склад"    [пусто] [пусто] "Остаток на складе" "Розничная цена"
 *     Строка 1: "Ед. изм." "Номенклатура" "Номенклатура.Код"  [пусто]  [пусто]
 *     Строка 2: "Магазин Основной склад" [итоговая строка — пропускаем]
 *     Строка 3+: реальные товары
 *
 *   FastExcel использует строку 0 как заголовок → теряет Номенклатура и
 *   Номенклатура.Код → маппинг SKU невозможен.
 *
 * ИСПРАВЛЕНИЕ:
 *
 *   1. Для .xls — нативный PHP-парсер BIFF8/OLE2 (без новых зависимостей).
 *   2. Объединение строк 0 и 1 в единый заголовок по непустым ячейкам.
 *   3. Данные начинаются с строки 2+ (пропускаем итоговую строку склада).
 *   4. Для .xlsx — FastExcel с той же логикой двойного заголовка.
 *   5. Фильтрация пустых и итоговых строк (без SKU и названия).
 *
 * Результат: array of associative arrays
 *   [
 *     ['Ед. изм.' => 'шт', 'Номенклатура' => 'BOMBA FOAM...', 'Номенклатура.Код' => 'РТ-00001272',
 *      'Остаток на складе' => 17, 'Розничная цена' => 7300],
 *     ...
 *   ]
 */
class ImportFileParser
{
    public static array $allowedExtensions = ['xls', 'xlsx', 'csv'];

    /**
     * Парсит файл из storage/public и возвращает все строки данных.
     *
     * @param  string $storagePath  Путь относительно storage/public (например, 'imports/file.xls')
     * @return array[]
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function parse(string $storagePath): array
    {
        $fullPath  = Storage::disk('public')->path($storagePath);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        if (! file_exists($fullPath)) {
            throw new \RuntimeException(
                "Файл не найден: {$fullPath}\n" .
                "Убедитесь что storage:link выполнен и папка storage/app/public/imports доступна."
            );
        }

        if (! in_array($extension, self::$allowedExtensions, true)) {
            throw new \InvalidArgumentException(
                "Неподдерживаемый формат: .{$extension}. Допустимы: " .
                implode(', ', self::$allowedExtensions)
            );
        }

        return match ($extension) {
            'csv'  => $this->parseCsv($fullPath),
            'xls'  => $this->parseXls($fullPath),
            'xlsx' => $this->parseXlsx($fullPath),
        };
    }

    /**
     * Список заголовков колонок (первая строка результата parse()).
     */
    public function getColumns(string $storagePath): array
    {
        $rows = $this->parse($storagePath);
        return ! empty($rows) ? array_keys($rows[0]) : [];
    }

    /**
     * Первые $limit строк для предпросмотра.
     */
    public function preview(string $storagePath, int $limit = 20): array
    {
        return array_slice($this->parse($storagePath), 0, $limit);
    }

    // ═══════════════════════════════════════════════════════════════
    // XLS — нативный BIFF8/OLE2 парсер (без внешних зависимостей)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Читает бинарный .xls (BIFF8/OLE2) нативно через PHP.
     *
     * FastExcel/openspout не поддерживает BIFF8 — он умеет только xlsx/csv/ods.
     * Этот метод не требует ext-gd, ext-zip, phpspreadsheet или xlrd.
     */
    private function parseXls(string $fullPath): array
    {
        $data = file_get_contents($fullPath);

        // Проверяем сигнатуру OLE2 (D0 CF 11 E0 A1 B1 1A E1)
        if (strlen($data) < 8 || substr($data, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            // Не OLE2 — возможно это на самом деле XLSX с неправильным расширением
            // Пробуем через FastExcel
            try {
                return $this->parseXlsx($fullPath);
            } catch (\Throwable) {
                throw new \RuntimeException(
                    "Файл .xls не является корректным форматом Excel (BIFF8/OLE2). " .
                    "Пересохраните файл в формате .xlsx из 1С."
                );
            }
        }

        $workbookData = $this->readOle2Stream($data, 'Workbook')
            ?? $this->readOle2Stream($data, 'Book');

        if ($workbookData === null) {
            throw new \RuntimeException(
                "Не удалось прочитать Workbook stream из файла .xls. " .
                "Файл повреждён или имеет нестандартную структуру."
            );
        }

        $rawRows = $this->parseBiff8($workbookData);
        return $this->normalizeRows($rawRows);
    }

    /**
     * Читает конкретный поток из OLE2 Compound Document.
     *
     * OLE2 структура:
     *  - Заголовок 512 байт
     *  - FAT (File Allocation Table) — таблица цепочек секторов
     *  - Directory — дерево потоков
     *  - Data sectors
     */
    private function readOle2Stream(string $data, string $streamName): ?string
    {
        if (strlen($data) < 512) return null;

        $sectorSize = 1 << $this->readU16($data, 30); // обычно 512
        if ($sectorSize < 64) $sectorSize = 512;

        $fatSectorCount  = $this->readU32($data, 44);
        $firstDirSector  = $this->readU32($data, 48);

        // Читаем DIFAT из заголовка (первые 109 секторов FAT)
        $difat = [];
        for ($j = 0; $j < 109 && $j < $fatSectorCount; $j++) {
            $sec = $this->readU32($data, 76 + $j * 4);
            if ($sec < 0xFFFFFFFA) {
                $difat[] = $sec;
            }
        }

        // Строим FAT таблицу
        $fat = [];
        foreach ($difat as $fatSec) {
            $offset = 512 + $fatSec * $sectorSize;
            for ($j = 0; $j + 3 < $sectorSize && $offset + $j + 3 < strlen($data); $j += 4) {
                $fat[] = $this->readU32($data, $offset + $j);
            }
        }

        // Читаем Directory
        $dirData  = $this->readChain($data, $fat, $firstDirSector, $sectorSize);
        $targetSector = null;
        $targetSize   = 0;

        for ($j = 0; $j + 127 < strlen($dirData); $j += 128) {
            $nameLen = $this->readU16($dirData, $j + 64);
            if ($nameLen < 2 || $nameLen > 64) continue;

            $name = mb_convert_encoding(
                substr($dirData, $j, min($nameLen - 2, 62)),
                'UTF-8', 'UTF-16LE'
            );

            $entryType    = ord($dirData[$j + 66]);
            $startSector  = $this->readU32($dirData, $j + 116);
            $size         = $this->readU32($dirData, $j + 120);

            if ($entryType > 0 && $name === $streamName) {
                $targetSector = $startSector;
                $targetSize   = $size;
                break;
            }
        }

        if ($targetSector === null) return null;

        $streamData = $this->readChain($data, $fat, $targetSector, $sectorSize);
        return substr($streamData, 0, $targetSize);
    }

    /**
     * Читает цепочку секторов по FAT.
     */
    private function readChain(string $data, array $fat, int $startSector, int $sectorSize): string
    {
        $result  = '';
        $sector  = $startSector;
        $visited = [];
        $maxIter = count($fat) + 10;
        $i       = 0;

        while ($sector < 0xFFFFFFFA && ! isset($visited[$sector]) && $i < $maxIter) {
            $visited[$sector] = true;
            $offset = 512 + $sector * $sectorSize;
            if ($offset + $sectorSize <= strlen($data)) {
                $result .= substr($data, $offset, $sectorSize);
            }
            $sector = isset($fat[$sector]) ? $fat[$sector] : 0xFFFFFFFE;
            $i++;
        }

        return $result;
    }

    /**
     * Парсит BIFF8 записи из Workbook stream.
     * Возвращает массив [rowNum => [colNum => value]].
     */
    private function parseBiff8(string $wb): array
    {
        $rows = [];
        $sst  = [];   // Shared String Table
        $i    = 0;
        $len  = strlen($wb);

        while ($i + 4 <= $len) {
            $recType = $this->readU16($wb, $i);
            $recLen  = $this->readU16($wb, $i + 2);

            if ($recLen > 65535 || $i + 4 + $recLen > $len) {
                $i += 2;
                continue;
            }

            $rd = substr($wb, $i + 4, $recLen);

            switch ($recType) {

                // ── SST (Shared String Table) 0x00FC ─────────────
                case 0x00FC:
                    $sst = $this->parseSst($rd);
                    break;

                // ── LABELSST 0x00FD ───────────────────────────────
                case 0x00FD:
                    if ($recLen >= 10) {
                        $row = $this->readU16($rd, 0);
                        $col = $this->readU16($rd, 2);
                        $idx = $this->readU32($rd, 6);
                        $rows[$row][$col] = isset($sst[$idx]) ? $sst[$idx] : '';
                    }
                    break;

                // ── NUMBER 0x0203 ─────────────────────────────────
                case 0x0203:
                    if ($recLen >= 14) {
                        $row = $this->readU16($rd, 0);
                        $col = $this->readU16($rd, 2);
                        $rows[$row][$col] = $this->unpackDouble(substr($rd, 6, 8));
                    }
                    break;

                // ── RK 0x027E ─────────────────────────────────────
                case 0x027E:
                    if ($recLen >= 10) {
                        $row = $this->readU16($rd, 0);
                        $col = $this->readU16($rd, 2);
                        $rows[$row][$col] = $this->decodeRk($this->readU32($rd, 6));
                    }
                    break;

                // ── MULRK 0x00BD ──────────────────────────────────
                case 0x00BD:
                    if ($recLen >= 6) {
                        $row      = $this->readU16($rd, 0);
                        $firstCol = $this->readU16($rd, 2);
                        $n        = (int)(($recLen - 6) / 6);
                        for ($k = 0; $k < $n; $k++) {
                            $off = 4 + $k * 6;
                            if ($off + 5 >= $recLen) break;
                            $rk = $this->readU32($rd, $off + 2);
                            $rows[$row][$firstCol + $k] = $this->decodeRk($rk);
                        }
                    }
                    break;

                // ── LABEL 0x0204 (строки напрямую, без SST) ───────
                case 0x0204:
                    if ($recLen >= 8) {
                        $row     = $this->readU16($rd, 0);
                        $col     = $this->readU16($rd, 2);
                        $charLen = $this->readU16($rd, 6);
                        $str     = substr($rd, 8, $charLen);
                        $rows[$row][$col] = mb_convert_encoding($str, 'UTF-8', 'Windows-1252');
                    }
                    break;

                // ── BLANK 0x0201 ───────────────────────────────────
                case 0x0201:
                    if ($recLen >= 4) {
                        $row = $this->readU16($rd, 0);
                        $col = $this->readU16($rd, 2);
                        $rows[$row][$col] = '';
                    }
                    break;
            }

            $i += 4 + $recLen;
        }

        return $rows;
    }

    /**
     * Парсит SST (Shared String Table).
     * Возвращает массив строк.
     */
    private function parseSst(string $rd): array
    {
        if (strlen($rd) < 8) return [];

        $unique = $this->readU32($rd, 4);
        $pos    = 8;
        $rdLen  = strlen($rd);
        $sst    = [];

        for ($n = 0; $n < $unique; $n++) {
            if ($pos + 2 >= $rdLen) break;

            $charCount = $this->readU16($rd, $pos);
            if ($pos + 2 >= $rdLen) break;
            $flags = ord($rd[$pos + 2]);
            $pos  += 3;

            $isUtf16 = (bool)($flags & 0x01);
            $hasRich = (bool)($flags & 0x08);
            $hasEast = (bool)($flags & 0x04);

            $byteCount = $charCount * ($isUtf16 ? 2 : 1);
            if ($pos + $byteCount > $rdLen) break;

            $chunk = substr($rd, $pos, $byteCount);
            $str   = $isUtf16
                ? mb_convert_encoding($chunk, 'UTF-8', 'UTF-16LE')
                : mb_convert_encoding($chunk, 'UTF-8', 'Windows-1252');

            $pos += $byteCount;

            if ($hasRich && $pos + 1 < $rdLen) {
                $rtCount = $this->readU16($rd, $pos);
                $pos += 2 + $rtCount * 4;
            }
            if ($hasEast && $pos + 3 < $rdLen) {
                $eaSize = $this->readU32($rd, $pos);
                $pos += 4 + $eaSize;
            }

            $sst[] = $str;
        }

        return $sst;
    }

    /**
     * Нормализует массив [rowNum => [colNum => value]] в массив ассоциативных строк.
     *
     * Логика объединения двойного заголовка 1С:
     *  - Строка 0: Склад, Остаток на складе, Розничная цена
     *  - Строка 1: Ед. изм., Номенклатура, Номенклатура.Код
     *  → Объединяем по непустым ячейкам → 5 колонок
     *  - Строка 2+: данные (пропускаем пустые и итоговые строки без SKU)
     */
    private function normalizeRows(array $rawRows): array
    {
        if (empty($rawRows)) return [];

        ksort($rawRows);
        $sortedNums = array_keys($rawRows);

        if (count($sortedNums) < 2) return [];

        $row0 = $rawRows[$sortedNums[0]] ?? [];
        $row1 = $rawRows[$sortedNums[1]] ?? [];

        // Определяем ширину (максимальный индекс колонки)
        $maxCol = 0;
        foreach ([$row0, $row1] as $r) {
            if (! empty($r)) $maxCol = max($maxCol, max(array_keys($r)));
        }

        // Объединяем строки 0 и 1 в один заголовок
        $headers = [];
        for ($c = 0; $c <= $maxCol; $c++) {
            $val0 = isset($row0[$c]) ? trim((string)$row0[$c]) : '';
            $val1 = isset($row1[$c]) ? trim((string)$row1[$c]) : '';
            // Приоритет у строки 1, если она не пустая (содержит Номенклатуру и Код)
            $headers[$c] = $val1 !== '' ? $val1 : $val0;
        }

        // Проверяем — есть ли реально два разных заголовка (двойная строка 1С)
        // Если все заголовки из строки 0 (нет различий) — это обычный однострочный файл
        $hasDoubleHeader = false;
        foreach (range(0, $maxCol) as $c) {
            $v0 = isset($row0[$c]) ? trim((string)$row0[$c]) : '';
            $v1 = isset($row1[$c]) ? trim((string)$row1[$c]) : '';
            if ($v0 !== '' && $v1 !== '' && $v0 !== $v1) {
                $hasDoubleHeader = true;
                break;
            }
        }

        // Начало данных
        $dataStartIdx = $hasDoubleHeader ? 2 : 1;

        // Собираем строки данных
        $result = [];
        foreach ($sortedNums as $i => $rowNum) {
            if ($i < $dataStartIdx) continue;

            $rawRow = $rawRows[$rowNum];
            $mapped = [];

            for ($c = 0; $c <= $maxCol; $c++) {
                $header = $headers[$c] ?? '';
                if ($header === '') continue;  // Пропускаем безымянные колонки
                $val = isset($rawRow[$c]) ? $rawRow[$c] : null;
                $mapped[$header] = is_float($val) ? $this->formatNumber($val) : (string)($val ?? '');
            }

            // Пропускаем полностью пустые строки
            $nonEmpty = array_filter($mapped, fn($v) => $v !== '' && $v !== null);
            if (empty($nonEmpty)) continue;

            // Пропускаем итоговые строки (в 1С бывают строки "Итого" или строки склада)
            // Признак: есть числа, но нет ни Номенклатуры, ни Кода
            $hasText = false;
            foreach (['Номенклатура', 'Номенклатура.Код', 'SKU', 'Артикул', 'Код', 'Наименование'] as $k) {
                if (! empty($mapped[$k])) {
                    $hasText = true;
                    break;
                }
            }
            // Если строка не имеет никаких текстовых идентификаторов — это итог, пропускаем
            // НО только если есть числовые колонки (чтобы не пропускать строки без чисел)
            $hasNumbers = false;
            foreach (['Остаток на складе', 'Розничная цена', 'Цена', 'Остаток'] as $k) {
                if (isset($mapped[$k]) && is_numeric(str_replace(' ', '', $mapped[$k]))) {
                    $hasNumbers = true;
                    break;
                }
            }
            if ($hasNumbers && ! $hasText) continue;

            $result[] = $mapped;
        }

        return $result;
    }

    /**
     * Форматирует float — убирает лишние знаки если это целое.
     */
    private function formatNumber(float $val): string
    {
        return (floor($val) === $val) ? (string)(int)$val : (string)$val;
    }

    // ═══════════════════════════════════════════════════════════════
    // XLSX — FastExcel с обработкой двойного заголовка
    // ═══════════════════════════════════════════════════════════════

    /**
     * Читает .xlsx через FastExcel (openspout).
     * Применяет ту же логику нормализации двойного заголовка.
     */
    private function parseXlsx(string $fullPath): array
    {
        // Читаем сырые строки без автозаголовков
        $rawRows = [];
        $rowNum  = 0;

        (new FastExcel())->import($fullPath, function (array $line) use (&$rawRows, &$rowNum) {
            $rawRows[$rowNum] = array_values($line);
            $rowNum++;
            return null;
        });

        // Конвертируем в формат [rowNum => [colNum => value]]
        $indexed = [];
        foreach ($rawRows as $rn => $cols) {
            foreach ($cols as $cn => $val) {
                if ($val !== null && $val !== '') {
                    $indexed[$rn][$cn] = $val;
                }
            }
        }

        return $this->normalizeRows($indexed);
    }

    // ═══════════════════════════════════════════════════════════════
    // CSV
    // ═══════════════════════════════════════════════════════════════

    /**
     * Читает CSV через FastExcel с определением кодировки и разделителя.
     */
    private function parseCsv(string $fullPath): array
    {
        $content = file_get_contents($fullPath);

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1251');
            file_put_contents($fullPath, $content);
        }

        $delimiter = $this->detectDelimiter($fullPath);
        $rows      = [];

        (new FastExcel())->configureCsv($delimiter)->import(
            $fullPath,
            function (array $line) use (&$rows) {
                $nonEmpty = array_filter($line, fn($v) => $v !== null && $v !== '');
                if (! empty($nonEmpty)) {
                    $rows[] = $line;
                }
                return null;
            }
        );

        return $rows;
    }

    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        $first  = (string) fgets($handle);
        fclose($handle);

        $counts = [];
        foreach ([';', ',', "\t", '|'] as $d) {
            $counts[$d] = substr_count($first, $d);
        }
        arsort($counts);
        return (string) array_key_first($counts);
    }

    // ═══════════════════════════════════════════════════════════════
    // Вспомогательные методы для BIFF8
    // ═══════════════════════════════════════════════════════════════

    private function readU16(string $data, int $offset): int
    {
        if ($offset + 1 >= strlen($data)) return 0;
        return unpack('v', substr($data, $offset, 2))[1];
    }

    private function readU32(string $data, int $offset): int
    {
        if ($offset + 3 >= strlen($data)) return 0;
        return unpack('V', substr($data, $offset, 4))[1];
    }

    private function unpackDouble(string $bytes): float
    {
        if (strlen($bytes) < 8) return 0.0;
        return unpack('d', $bytes)[1];
    }

    /**
     * Декодирует RK значение (сжатое число BIFF8).
     */
    private function decodeRk(int $rk): float
    {
        if ($rk & 2) {
            // Целое
            $val = $rk >> 2;
        } else {
            // IEEE 754 double (только старшие 30 бит)
            $packed = pack('VV', 0, $rk & 0xFFFFFFFC);
            $val    = unpack('d', $packed)[1];
        }

        return ($rk & 1) ? $val / 100 : (float)$val;
    }
}
