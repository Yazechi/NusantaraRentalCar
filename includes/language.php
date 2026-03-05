<?php
/**
 * Language Helper
 * Handles language switching and translation
 * Default: Indonesian (id)
 */

// Handle language switch request
if (isset($_GET['lang']) && in_array($_GET['lang'], ['id', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_GET['lang'], time() + (365 * 24 * 60 * 60), '/');
}

// Determine current language
function get_current_lang() {
    if (isset($_SESSION['lang'])) {
        return $_SESSION['lang'];
    }
    if (isset($_COOKIE['lang'])) {
        $_SESSION['lang'] = $_COOKIE['lang'];
        return $_COOKIE['lang'];
    }
    return 'id'; // Default: Indonesian
}

// Load language file
function load_lang() {
    static $translations = null;
    if ($translations !== null) return $translations;
    
    $lang = get_current_lang();
    $file = __DIR__ . '/lang/' . $lang . '.php';
    
    if (file_exists($file)) {
        $translations = require $file;
    } else {
        $translations = require __DIR__ . '/lang/id.php';
    }
    
    return $translations;
}

// Translation function
function __($key, $default = null) {
    $translations = load_lang();
    return $translations[$key] ?? $default ?? $key;
}
