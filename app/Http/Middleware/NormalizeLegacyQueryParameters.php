<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class NormalizeLegacyQueryParameters
{
    private const CURRENT_SORTS = [
        'price_asc',
        'price_desc',
        'new',
        'popular',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $path = rawurldecode($request->getPathInfo());
        if (
            preg_match('/[\x{0400}-\x{04FF}]/u', $path) === 1
            || $request->is('p112599607-ofisnoe-kreslo-podgolovnikom.html')
        ) {
            return $next($request);
        }

        $query = $request->query();
        $legacyTag = $query['tag'] ?? null;
        $changed = false;

        foreach (['order', 'limit', 'tag'] as $parameter) {
            if (array_key_exists($parameter, $query)) {
                unset($query[$parameter]);
                $changed = true;
            }
        }

        if (
            array_key_exists('sort', $query)
            && ! in_array($query['sort'], self::CURRENT_SORTS, true)
        ) {
            unset($query['sort']);
            $changed = true;
        }

        if (! $changed) {
            return $next($request);
        }

        if (
            $request->is('search')
            && ! array_key_exists('q', $query)
            && is_string($legacyTag)
            && trim($legacyTag) !== ''
        ) {
            $query['q'] = trim($legacyTag);
        }

        $target = $request->getPathInfo();
        $queryString = Arr::query($query);

        if ($queryString !== '') {
            $target .= '?' . $queryString;
        }

        return redirect($target, 301);
    }
}
