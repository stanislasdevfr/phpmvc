<?php

namespace App\Core;

class Session
{
    /**
     * Start session if not already started
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Set a session value
     */
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value
     */
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if key exists
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Destroy session
     */
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated(): bool
    {
        return self::has('user_id');
    }

    /**
     * Get authenticated user ID
     */
    public static function getUserId(): ?int
    {
        return self::get('user_id');
    }
}