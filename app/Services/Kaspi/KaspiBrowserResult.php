<?php

namespace App\Services\Kaspi;

class KaspiBrowserResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly array $data = [],
        public readonly ?string $error = null,
    ) {}
}
