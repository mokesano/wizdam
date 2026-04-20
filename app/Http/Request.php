<?php

declare(strict_types=1);

namespace Wizdam\Http;

/**
 * Wrapper sederhana untuk PSR-7 style request.
 */
class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $body = [],
        public readonly array $server = [],
        public readonly array $cookies = []
    ) {}

    public static function fromGlobals(): self
    {
        return new self(
            method: $_SERVER['REQUEST_METHOD'] ?? 'GET',
            path: parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
            query: $_GET ?? [],
            body: $_POST ?? [],
            server: $_SERVER ?? [],
            cookies: $_COOKIE ?? []
        );
    }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function getBody(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function getServer(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }
}
