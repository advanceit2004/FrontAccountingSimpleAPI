<?php

declare(strict_types=1);

namespace FAAPI\FrontAccounting;

use RuntimeException;

final class Kernel
{
    private static string $apiRoot;
    private static string $faRoot;
    private static bool $booted = false;

    public static function configure(string $apiRoot): void
    {
        self::$apiRoot = realpath($apiRoot) ?: $apiRoot;
        self::$faRoot = self::detectFaRoot(self::$apiRoot);
    }

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        if (!isset(self::$apiRoot)) {
            self::configure(dirname(__DIR__, 2));
        }

        $initialBufferLevel = ob_get_level();
        ob_start();

        try {
            self::includeFrontAccounting();
            self::$booted = true;
        } finally {
            while (ob_get_level() > $initialBufferLevel) {
                ob_end_clean();
            }
        }
    }

    public static function apiRoot(): string
    {
        return self::$apiRoot;
    }

    public static function faRoot(): string
    {
        return self::$faRoot;
    }

    public static function version(): string
    {
        self::boot();
        return $GLOBALS['version'] ?? $GLOBALS['src_version'] ?? 'unknown';
    }

    public static function defaultSecret(): string
    {
        self::boot();
        return hash('sha256', self::$apiRoot . '|' . serialize($GLOBALS['db_connections'] ?? []) . '|frontaccounting-api');
    }

    public static function selectCompany(int $company): void
    {
        self::boot();
        if (!isset($GLOBALS['db_connections'][$company])) {
            throw new RuntimeException('Unknown company index: ' . $company);
        }
        \set_global_connection($company);
    }

    private static function detectFaRoot(string $apiRoot): string
    {
        if (is_dir($apiRoot . '/_frontaccounting')) {
            return realpath($apiRoot . '/_frontaccounting') ?: $apiRoot . '/_frontaccounting';
        }

        $candidate = realpath($apiRoot . '/../..');
        if ($candidate !== false && is_file($candidate . '/version.php')) {
            return $candidate;
        }

        $candidate = realpath(dirname($apiRoot, 2));
        if ($candidate !== false && is_file($candidate . '/version.php')) {
            return $candidate;
        }

        throw new RuntimeException('Unable to detect FrontAccounting root from API root: ' . $apiRoot);
    }

    private static function includeFrontAccounting(): void
    {
        $path_to_root = self::$faRoot;
        $GLOBALS['path_to_root'] = $path_to_root;

        if (!defined('API_ROOT')) {
            define('API_ROOT', self::$apiRoot);
        }
        if (!defined('FA_ROOT')) {
            define('FA_ROOT', self::$faRoot);
        }

        if (isset($_GET['path_to_root']) || isset($_POST['path_to_root'])) {
            throw new RuntimeException('Restricted access');
        }

        self::includeOnce($path_to_root . '/includes/errors.inc');
        set_error_handler('error_handler');

        self::includeOnce(self::$apiRoot . '/session_utils.inc');
        self::includeOnce($path_to_root . '/includes/current_user.inc');
        self::includeOnce($path_to_root . '/frontaccounting.php');
        self::includeOnce($path_to_root . '/admin/db/security_db.inc');
        self::includeOnce($path_to_root . '/includes/lang/language.inc');
        self::includeOnce($path_to_root . '/includes/lang/gettext.inc');
        self::includeOnce($path_to_root . '/config_db.php');
        self::includeOnce($path_to_root . '/includes/ajax.inc');
        self::includeOnce($path_to_root . '/includes/ui/ui_msgs.inc');
        self::includeOnce($path_to_root . '/includes/prefs/sysprefs.inc');
        self::includeOnce($path_to_root . '/includes/hooks.inc');

        foreach (($GLOBALS['installed_extensions'] ?? []) as $ext) {
            $hookFile = $path_to_root . '/' . $ext['path'] . '/hooks.php';
            if (is_file($hookFile)) {
                self::includeOnce($hookFile);
            }
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.gc_maxlifetime', '36000');
            session_name('FAAPI' . md5(self::$apiRoot));
            session_start();
        }

        $_SESSION['SysPrefs'] = new \sys_prefs();
        $GLOBALS['SysPrefs'] = &$_SESSION['SysPrefs'];

        if (($GLOBALS['SysPrefs']->login_delay ?? -1) < 0) {
            $GLOBALS['SysPrefs']->login_delay = 10;
        }
        if (($GLOBALS['SysPrefs']->login_max_attempts ?? -1) < 0) {
            $GLOBALS['SysPrefs']->login_max_attempts = 3;
        }

        if (function_exists('hook_session_start')) {
            \hook_session_start($_POST['company_login_name'] ?? null);
        }

        \get_text_init();

        if (($GLOBALS['SysPrefs']->login_delay ?? 0) > 0 && is_file($path_to_root . '/tmp/faillog.php')) {
            include_once $path_to_root . '/tmp/faillog.php';
        }

        self::initializeLanguage();

        self::includeOnce($path_to_root . '/includes/access_levels.inc');
        self::includeOnce($path_to_root . '/version.php');
        self::includeOnce($path_to_root . '/includes/main.inc');
        self::includeOnce($path_to_root . '/includes/app_entries.inc');

        $GLOBALS['Ajax'] = new \Ajax();
        $GLOBALS['Validate'] = [];
        $GLOBALS['Editors'] = [];
        $GLOBALS['Pagehelp'] = [];
        $GLOBALS['Refs'] = new \references();

        if (!isset($_SESSION['wa_current_user'])) {
            $_SESSION['wa_current_user'] = new \current_user();
        }

        if (function_exists('install_hooks')) {
            \install_hooks();
        }
    }

    private static function initializeLanguage(): void
    {
        $dfltLang = $GLOBALS['dflt_lang'] ?? 'C';
        $installed = $GLOBALS['installed_languages'] ?? [];

        if (!isset($_SESSION['language']) || !method_exists($_SESSION['language'], 'set_language')) {
            $language = null;
            foreach ($installed as $entry) {
                if (($entry['code'] ?? null) === $dfltLang) {
                    $language = $entry;
                    break;
                }
            }
            $language ??= $installed[0] ?? ['name' => 'English', 'code' => 'C', 'encoding' => 'UTF-8'];
            $_SESSION['language'] = new \language(
                $language['name'],
                $language['code'],
                $language['encoding'],
                (($language['rtl'] ?? false) === true) ? 'rtl' : 'ltr'
            );
        }

        $_SESSION['language']->set_language($_SESSION['language']->code);
    }

    private static function includeOnce(string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException('Required FrontAccounting file not found: ' . $path);
        }

        // FrontAccounting include files expect many legacy variables in global scope.
        global $path_to_root, $db_connections, $def_coy, $tb_pref_counter, $db;
        global $installed_extensions, $installed_languages, $dflt_lang;
        global $version, $src_version, $db_version;
        global $security_areas, $security_sections, $security_groups, $security_headings;
        global $document_child_types;
        global $Hooks, $SysPrefs, $Ajax, $Refs, $Validate, $Editors, $Pagehelp, $login_faillog;
        global $go_debug, $go_debug_db;

        $path_to_root = $GLOBALS['path_to_root'] ?? self::$faRoot;
        include_once $path;
    }
}
