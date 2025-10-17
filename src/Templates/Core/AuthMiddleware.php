<?php

namespace App\Core;

class AuthMiddleware
{
    /**
     * Check if user is authenticated, redirect to login if not
     */
    public static function requireAuth(): void
    {
        if (!Session::isAuthenticated()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Check if user is guest (not authenticated), redirect to home if authenticated
     */
    public static function requireGuest(): void
    {
        if (Session::isAuthenticated()) {
            header('Location: /');
            exit;
        }
    }
}