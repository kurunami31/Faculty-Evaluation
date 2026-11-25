<?php

declare(strict_types=1);

namespace App\Auth\Service;

final class RateLimiter
{
    private string $storagePath;
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(
        int $maxAttempts = 5,
        int $windowSeconds = 900,
        ?string $storagePath = null
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
        $this->storagePath = $storagePath ?? sys_get_temp_dir() . "/faculty_eval_ratelimit/";

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function checkLimit(string $key): bool
    {
        $file = $this->storagePath . md5($key) . ".json";

        if (!file_exists($file)) {
            return true;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return true;
        }

        $data = json_decode($content, true);

        if (!$data || !isset($data["attempts"], $data["window_start"])) {
            return true;
        }

        $now = time();

        if ($now - $data["window_start"] >= $this->windowSeconds) {
            return true;
        }

        return $data["attempts"] < $this->maxAttempts;
    }

    public function recordAttempt(string $key): void
    {
        $file = $this->storagePath . md5($key) . ".json";
        $now = time();

        $data = [
            "attempts" => 1,
            "window_start" => $now,
        ];

        if (file_exists($file)) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $existing = json_decode($content, true);

                if ($existing && isset($existing["attempts"], $existing["window_start"])) {
                    if ($now - $existing["window_start"] < $this->windowSeconds) {
                        $data["attempts"] = $existing["attempts"] + 1;
                        $data["window_start"] = $existing["window_start"];
                    }
                }
            }
        }

        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    public function reset(string $key): void
    {
        $file = $this->storagePath . md5($key) . ".json";

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function getRemainingAttempts(string $key): int
    {
        $file = $this->storagePath . md5($key) . ".json";

        if (!file_exists($file)) {
            return $this->maxAttempts;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return $this->maxAttempts;
        }

        $data = json_decode($content, true);

        if (!$data || !isset($data["attempts"], $data["window_start"])) {
            return $this->maxAttempts;
        }

        $now = time();

        if ($now - $data["window_start"] >= $this->windowSeconds) {
            return $this->maxAttempts;
        }

        return max(0, $this->maxAttempts - $data["attempts"]);
    }

    public function getResetTime(string $key): int
    {
        $file = $this->storagePath . md5($key) . ".json";

        if (!file_exists($file)) {
            return 0;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return 0;
        }

        $data = json_decode($content, true);

        if (!$data || !isset($data["window_start"])) {
            return 0;
        }

        $resetTime = $data["window_start"] + $this->windowSeconds;
        return max(0, (int)($resetTime - time()));
    }
}
