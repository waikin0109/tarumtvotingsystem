<?php
class SessionHelper {
    public static function flash(string $key, $value = null) {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($value !== null) {
            $_SESSION['flash'][$key] = $value;
            return;
        }

        $data = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $data;
    }
}
