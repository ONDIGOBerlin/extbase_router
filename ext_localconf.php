<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB']['extbase_router'] = 'Ondigo\ExtbaseRouter\Hooks\RouteHook->attemptRouting';