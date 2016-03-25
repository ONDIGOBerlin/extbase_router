<?php
namespace Ondigo\ExtbaseRouter\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;

class RouteHook {

    /**
     * @var \Ondigo\ExtbaseRouter\Routing\Router
     */
    protected $router;

    public function __construct() {
        $this->router = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Ondigo\ExtbaseRouter\Routing\Router::class);
    }

    public function setup() {
        $GLOBALS['TSFE']->sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        $GLOBALS['TSFE']->sys_page->init(FALSE);
        $firstPage = $GLOBALS['TSFE']->sys_page->getFirstWebPage(0);
        $rootPageUid = $firstPage ? $firstPage['uid'] : 0;

        if (!is_array($GLOBALS['TCA'])) {
            \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadCachedTca();
        }

        /** @var \TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager $configurationManager */
        $configurationManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager::class);
        $configurationManager->setContentObject(
            new \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer()
        );

        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->id = $rootPageUid;
        $GLOBALS['TSFE']->type = 0;
        $GLOBALS['TSFE']->rootLine = BackendUtility::BEgetRootLine($rootPageUid);
        $GLOBALS['TSFE']->getConfigArray();
    }

    public function attemptRouting() {
        $match = $this->router->match($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

        if ($match === FALSE) {
            return;
        }

        $this->setup();
        $this->router->route($match);

        exit;
    }

}