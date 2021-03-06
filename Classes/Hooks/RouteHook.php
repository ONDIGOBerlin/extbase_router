<?php
namespace Ondigo\ExtbaseRouter\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RouteHook {

    /**
     * @var \Ondigo\ExtbaseRouter\Routing\Router
     */
    protected $router;

    public function __construct() {
        $this->router = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Ondigo\ExtbaseRouter\Routing\Router::class);
    }

    public function setup() {
        if (!is_array($GLOBALS['TCA'])) {
            \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadCachedTca();
        }

        $GLOBALS['TSFE']->sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        $GLOBALS['TSFE']->sys_page->init(FALSE);
        $firstPage = $GLOBALS['TSFE']->sys_page->getFirstWebPage(0);
        $GLOBALS['TSFE']->page = $firstPage;

        $rootPageUid = $firstPage ? $firstPage['uid'] : 1;
        $typeNum = 0;

        /** @var \TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager $configurationManager */
        $configurationManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager::class);
        $configurationManager->setContentObject(
            new \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer()
        );

        if (!is_object($GLOBALS['TT'])) {
            $GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\NullTimeTracker;
            $GLOBALS['TT']->start();
        }

        $GLOBALS['TSFE'] = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', $GLOBALS['TYPO3_CONF_VARS'], $rootPageUid, $typeNum);
        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->getConfigArray();
        $GLOBALS['TSFE']->absRefPrefix = '/';

        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['extbase_router']);
        $availableLanguages = [];

        foreach (explode(',', $extConf['accept_language_values']) as $lang) {
            list($key, $uid) = explode('=', $lang);
            $availableLanguages[$key] = (int)$uid;
        }

        $headerKey = 'HTTP_' . str_replace('-', '_', strtoupper($extConf['accept_language_header']));
        $acceptLanguageHeader = $_SERVER[$headerKey];

        $sys_language_uid = isset($availableLanguages[$acceptLanguageHeader]) ? $availableLanguages[$acceptLanguageHeader] : 0;
        $GLOBALS['TSFE']->sys_language_uid = $sys_language_uid;

        $GLOBALS['TSFE']->config = \TYPO3\CMS\Extbase\Utility\ArrayUtility::arrayMergeRecursiveOverrule(
            $GLOBALS['TSFE']->config,
            [
                'config' => [
                    'sys_language_uid' => isset($availableLanguages[$acceptLanguageHeader]) ? $availableLanguages[$acceptLanguageHeader] : 0,
                    'language' => $acceptLanguageHeader ?: $GLOBALS['TSFE']->config['config']['language'],
                    'linkVars' => 'L(int)'
                ]
            ]
        );

        $GLOBALS['TSFE']->linkVars .= '&L=' . $sys_language_uid;
        $GLOBALS['TSFE']->settingLanguage();
        $GLOBALS['TSFE']->settingLocale();
    }

    public function attemptRouting() {
        $requestUri = strtok($_SERVER['REQUEST_URI'],'?');
        $match = $this->router->match($requestUri, $_SERVER['REQUEST_METHOD']);

        if ($match === FALSE) {
            return;
        }

        $this->setup();
        $this->router->route($match);

        exit;
    }

}