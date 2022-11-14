<?php

namespace Pixelant\PxaSocialFeed\Controller;

use Pixelant\PxaSocialFeed\Domain\Model\Token;
use Pixelant\PxaSocialFeed\Domain\Repository\FeedRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * FeedsController
 */
class FeedsController extends ActionController
{
    /**
     * @var FeedRepository
     */
    protected $feedRepository = null;

    /**
     * @param FeedRepository $feedRepository
     */
    public function injectFeedRepository(FeedRepository $feedRepository)
    {
        $this->feedRepository = $feedRepository;
    }

    /**
     * List action
     *
     * @return void
     */
    public function listAction()
    {
        $limit = $this->settings['feedsLimit'] ? intval($this->settings['feedsLimit']) : 10;
        $configurations = GeneralUtility::intExplode(',', $this->settings['configuration'], true);

        $feeds = $this->feedRepository->findByConfigurations($configurations, $limit);
        foreach ($feeds as $feed) {
            $fileRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\FileRepository::class);
            $fileObjects = $fileRepository->findByRelation(
                'tx_pxasocialfeed_domain_model_feed',
                'image',
                $feed->getUid()
            );

            if (!empty($fileObjects)) {
                // consider first image only
                $this->view->assign('images', $fileObjects[0]);
                $feed->setSmallImage(
                    'fileadmin' .
                    $fileObjects[0]->getOriginalFile()->getIdentifier());

                //foreach ($fileObjects as $key => $value) {
                //    $feed->setSmallImage(
                //        'fileadmin' .
                //        $value->getOriginalFile()->getIdentifier());
                //}

            } else {
                //default image path.
                $feed->setSmallImage("DEFAULT");
            }
        }

        $this->view->assign('feeds', $feeds);

        $filters = [
            [
                "id" => 0,
                "type" => "all"
            ],
            [
                "id" => Token::FACEBOOK,
                "type" => "facebook"
            ], [
                "id" => Token::INSTAGRAM,
                "type" => "instagram"
            ], [
                "id" => Token::TWITTER,
                "type" => "twitter"
            ], [
                "id" => Token::YOUTUBE,
                "type" => "youtube"
            ], [
                "id" => Token::LINKEDIN,
                "type" => "linkedin"
            ]
        ];
        $this->view->assign('filters', $filters);
    }

    /**
     * List ajax action
     * Prepare view for later ajax request
     *
     * @return void
     */
    public function listAjaxAction()
    {
    }

    /**
     * Load feed with ajax
     *
     * @param string $configuration
     * @param int $feedsLimit
     * @param string $partial
     * @param string $presentation
     * @return void
     */
    public function loadFeedAjaxAction(
        string $configuration,
        int    $feedsLimit = 10,
        string $partial = '',
        string $presentation = ''
    )
    {
        $feeds = $this->feedRepository->findByConfigurations(
            GeneralUtility::intExplode(',', $configuration, true),
            $feedsLimit
        );
        $settings = array_merge(
            $this->settings,
            compact('configuration', 'feedsLimit', 'partial', 'presentation')
        );

        $this->view->assignMultiple(compact('feeds', 'settings'));

        header('Content-Type: application/json');

        echo json_encode(
            [
                'success' => true,
                'html' => $this->view->render()
            ]
        );

        exit(0);
    }
}
