<?php

namespace App\Support;

use App\Models\BlogPost;
use Illuminate\Support\Str;

class BlogContent
{
    public static function excerpt(BlogPost $post, int $limit = 180): string
    {
        $source = $post->meta_description ?: strip_tags((string) $post->content);
        $source = trim(preg_replace('/\s+/u', ' ', $source) ?? $source);

        return Str::limit($source ?: $post->title, $limit);
    }

    public static function readingTime(?string $content): int
    {
        $words = str_word_count(strip_tags((string) $content), 0, 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдеёжзийклмнопрстуфхцчшщъыьэюя');

        return max(1, (int) ceil($words / 180));
    }

    public static function topic(BlogPost $post): string
    {
        $text = mb_strtolower($post->title.' '.$post->seoDescription().' '.strip_tags((string) $post->content));

        return match (true) {
            str_contains($text, 'эргоном') => 'Эргономика',
            str_contains($text, 'руковод') || str_contains($text, 'директор') => 'Руководителям',
            str_contains($text, 'обзор') || str_contains($text, 'сравнен') => 'Обзор',
            str_contains($text, 'совет') || str_contains($text, 'как ') => 'Советы',
            default => 'Выбор',
        };
    }

    public static function withAnchors(?string $content): array
    {
        $html = (string) $content;
        $toc = [];
        $used = [];

        $html = preg_replace_callback(
            '/<h([23])([^>]*)>(.*?)<\/h\1>/isu',
            function (array $matches) use (&$toc, &$used): string {
                $level = (int) $matches[1];
                $attrs = $matches[2] ?? '';
                $titleHtml = $matches[3] ?? '';
                $title = trim(strip_tags($titleHtml));
                $id = '';

                if (preg_match('/\sid=["\']([^"\']+)["\']/i', $attrs, $idMatch)) {
                    $id = $idMatch[1];
                }

                if ($id === '') {
                    $base = Str::slug($title) ?: 'section';
                    $id = $base;
                    $counter = 2;

                    while (isset($used[$id])) {
                        $id = $base.'-'.$counter;
                        $counter++;
                    }

                    $attrs .= ' id="'.$id.'"';
                }

                $used[$id] = true;

                if ($title !== '') {
                    $toc[] = [
                        'id' => $id,
                        'title' => $title,
                        'level' => $level,
                    ];
                }

                return '<h'.$level.$attrs.'>'.$titleHtml.'</h'.$level.'>';
            },
            $html
        ) ?? $html;

        return [$html, $toc];
    }

    public static function faq(BlogPost $post): array
    {
        $faq = $post->faq ?? [];
        if (! is_array($faq)) {
            return [];
        }

        return array_values(array_filter($faq, fn ($item) =>
            is_array($item)
            && trim((string) ($item['question'] ?? '')) !== ''
            && trim((string) ($item['answer'] ?? '')) !== ''
        ));
    }
}
