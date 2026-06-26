<?php

namespace App\Services\Kaspi;

use Symfony\Component\Process\Process;

class KaspiBrowser
{
    public function run(string $script, array $arguments = [], int $timeout = 35): KaspiBrowserResult
    {
        $node = env('KASPI_NODE_BINARY', 'node');
        $scriptPath = base_path("scripts/{$script}");
        $screenshotDir = storage_path('logs/kaspi-screenshots');

        $process = new Process(array_merge([$node, $scriptPath], $arguments, [$screenshotDir]), base_path());
        $process->setTimeout($timeout);
        $process->run();

        $output = trim($process->getOutput());
        $decoded = $output !== '' ? json_decode($output, true) : null;

        if (! is_array($decoded)) {
            return new KaspiBrowserResult(false, [
                'stdout' => $output,
                'stderr' => trim($process->getErrorOutput()),
                'exit_code' => $process->getExitCode(),
            ], 'browser_output_not_json');
        }

        return new KaspiBrowserResult((bool) ($decoded['ok'] ?? false), $decoded, $decoded['error'] ?? null);
    }
}
