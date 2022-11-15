<?php
defined('TYPO3') || die();

call_user_func(function () {
    /**
      * Extension key
      */
    $extensionKey = 'pxa_social_feed';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $extensionKey,
        'Configuration/TypoScript',
        'Pxa Social Feed'
    );
});