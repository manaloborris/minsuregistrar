<?php

class BaseController
{
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        include APP_ROOT . '/app/views/' . $view . '.php';
    }

    protected function post(string $key, string $default = ''): string
    {
        return trim($_POST[$key] ?? $default);
    }

    protected function normalizeQuarterHourTime(string $time): ?string
    {
        $time = trim($time);
        if ($time === '') {
            return null;
        }

        $parsed = DateTime::createFromFormat('H:i', $time)
            ?: DateTime::createFromFormat('H:i:s', $time);

        if (!$parsed) {
            return null;
        }

        if ((int) $parsed->format('s') !== 0) {
            return null;
        }

        $minutes = (int) $parsed->format('i');
        if ($minutes % 15 !== 0) {
            return null;
        }

        return $parsed->format('H:i:s');
    }
}
