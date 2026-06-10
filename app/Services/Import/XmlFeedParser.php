<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * XmlFeedParser — парсер Google Merchant XML (RSS 2.0 + g: namespace).
 * Поддерживает загрузку по URL и парсинг из строки.
 */
class XmlFeedParser
{
    private const READ_TIMEOUT = 60;

    /** Загружает XML по URL и возвращает массив строк для FullProductImporter */
    public function fetchAndParse(string $url): array
    {
        $response = Http::timeout(self::READ_TIMEOUT)
            ->withHeaders(['Accept' => 'application/xml, text/xml, */*'])
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()} при загрузке XML: {$url}");
        }

        return $this->parseXml($response->body());
    }

    /** Парсит XML-строку в массив строк */
    public function parseXml(string $xmlContent): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $err = implode('; ', array_map(fn($e) => trim($e->message), libxml_get_errors()));
            libxml_clear_errors();
            throw new \RuntimeException("Ошибка XML: {$err}");
        }
        libxml_clear_errors();

        $rows = [];
        foreach ($xml->channel->item ?? [] as $item) {
            try {
                $row = $this->parseItem($item);
                if ($row !== null) $rows[] = $row;
            } catch (\Throwable $e) {
                Log::warning('XmlFeedParser item error: ' . $e->getMessage());
            }
        }

        Log::info('XmlFeedParser: parsed ' . count($rows) . ' items');
        return $rows;
    }

    private function parseItem(\SimpleXMLElement $item): ?array
    {
        $g = $item->children('http://base.google.com/ns/1.0');

        $sku = trim((string) $g->id);
        if (empty($sku)) return null;

        $name        = trim((string) $g->title);
        $description = trim((string) $g->description);
        $mainImage   = trim((string) $g->image_link);

        $addImages = [];
        foreach ($g->additional_image_link as $img) {
            $u = trim((string) $img);
            if (!empty($u)) $addImages[] = $u;
        }

        // Цена: "146960.00 KZT" → 146960.00
        $priceRaw = trim((string) $g->price);
        $price    = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', $priceRaw));

        $availability = strtolower(trim((string) $g->availability));
        $inStock      = in_array($availability, ['in stock', 'preorder'], true);

        // Категория: "дом и сад > мебель > Стулья > офисные кресла"
        $productType  = trim((string) $g->product_type);
        $categoryPath = array_map('trim', explode('>', $productType));

        $color = trim((string) $g->color);

        $attributes = [];
        foreach ($g->product_detail as $detail) {
            $k = trim((string) $detail->attribute_name);
            $v = trim((string) $detail->attribute_value);
            if ($k && $v) $attributes[$k] = $v;
        }
        if ($color) $attributes['Цвет'] = $color;

        $brand = $this->extractBrand($name, $attributes);

        return [
            'sku'               => $sku,
            'name'              => $name,
            'description'       => $description,
            'price'             => $price,
            'old_price'         => null,
            'in_stock'          => $inStock ? '1' : '0',
            'main_image'        => $mainImage,
            'additional_images' => $addImages,
            'category_path'     => $categoryPath,
            'brand'             => $brand,
            'attributes'        => $attributes,
            'is_active'         => '1',
        ];
    }

    private function extractBrand(string $name, array $attributes): string
    {
        foreach (['Производитель', 'Бренд', 'Brand'] as $key) {
            if (!empty($attributes[$key])) return $attributes[$key];
        }

        $known = [
            'Арт Строй Мебель', 'ART', 'CHAIRMAN', 'BRABIX', 'EVERPROF',
            'COLLEGE', 'МЕТТА', 'Metta', 'VMMGAME', 'Бюрократ',
            'NOWY STYL', 'KRESLA-LUX',
        ];
        foreach ($known as $b) {
            if (mb_stripos($name, $b) !== false) return $b;
        }
        return '';
    }
}
