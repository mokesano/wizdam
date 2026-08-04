<?php

/**
 * @file library/Helpers/helpers.php
 *
 * Copyright (c) 2024-2026 Sangia Lumera Publishing
 * Copyright (c) 2017-2026 Rochmady and Code Lumera Teams
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Helpers
 * @ingroup Helpers
 *
 * @brief Collection of helper functions for common tasks.
 */

declare(strict_types=1);

use Sangia\Library\Helpers\Helpers;

if (!function_exists('uuid')) {
    function uuid(): string
    {
        return Helpers::uuid(); // Undefined type 'Sangia\Library\Helpers\Helpers'.
    }
}

if (!function_exists('format_number')) {
    function format_number(int|float $number, int $decimals = 0): string
    {
        return Helpers::formatNumber($number, $decimals); // Undefined type 'Sangia\Library\Helpers\Helpers'.
    }
}

if (!function_exists('truncate')) {
    function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        return Helpers::truncate($text, $length, $suffix); // Undefined type 'Sangia\Library\Helpers\Helpers'.
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        return Helpers::slugify($text); // Undefined type 'Sangia\Library\Helpers\Helpers'.
    }
}

if (!function_exists('time_ago')) {
    function time_ago(\DateTimeInterface $date): string
    {
        return Helpers::timeAgo($date); // Undefined type 'Sangia\Library\Helpers\Helpers'.
    }
}

if (!function_exists('sanitize')) {
    function sanitize(string $input): string
    {
        return Helpers::sanitize($input); // Undefined type 'Sangia\Library\Helpers\Helpers'.
    }
}

if (!function_exists('is_valid_email')) {
    function is_valid_email(string $email): bool
    {
        return Helpers::isValidEmail($email); // Undefined type 'Sangia\Library\Helpers\Helpers'.
    }
}

if (!function_exists('random_string')) {
    function random_string(
        int $length = 32,
        string $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
    ): string {
        return Helpers::randomString($length, $charset); // Undefined type 'Sangia\Library\Helpers\Helpers'.
    }
}

if (!function_exists('human_file_size')) {
    function human_file_size(int $bytes): string
    {
        return Helpers::humanFileSize($bytes); // Undefined type 'Sangia\Library\Helpers\Helpers'.
    }
}

if (!function_exists('is_ajax')) {
    function is_ajax(): bool
    {
        return Helpers::isAjax(); // Undefined type 'Sangia\Library\Helpers\Helpers'.
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        static $configs = [];

        if (empty($configs)) {
            $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
            $configFiles = glob($basePath . '/config/*.php');

            foreach ($configFiles as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                $configs[$name] = require $file;
            }
        }

        $keys = explode('.', $key);
        $value = $configs;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        // Convert boolean strings
        if (strtolower($value) === 'true') {
            return true;
        }

        if (strtolower($value) === 'false') {
            return false;
        }

        // Convert numeric strings
        if (is_numeric($value)) {
            return (int) $value;
        }

        return $value;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $statusCode = 302): \Sangia\Http\Response
    {
        return \Sangia\Http\Response::redirect($url, $statusCode);
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): \Sangia\Http\Response
    {
        return \Sangia\Http\Response::view($template, $data); // Undefined method 'view'.
    }
}

if (!function_exists('json_response')) {
    function json_response(array $data, int $statusCode = 200): \Sangia\Http\Response
    {
        return \Sangia\Http\Response::json($data, $statusCode);
    }
}

if (!function_exists('error_response')) {
    function error_response(string $message, int $statusCode = 400): \Sangia\Http\Response
    {
        return \Sangia\Http\Response::error($message, $statusCode);
    }
}

if (!function_exists('auth')) {
    function auth()
    {
        return \Sangia\Core\App::getInstance()->getAuthService();
    }
}

if (!function_exists('db')) {
    function db()
    {
        return \Sangia\Core\App::getInstance()->getDatabase();
    }
}

if (!function_exists('queue')) {
    function queue()
    {
        return \Sangia\Core\App::getInstance()->getQueueManager();
    }
}

if (!function_exists('api_client')) {
    function api_client()
    {
        return \Sangia\Core\App::getInstance()->getApiClient();
    }
}
