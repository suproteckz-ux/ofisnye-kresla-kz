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
