<?php
declare(strict_types=1);

namespace Pixelant\PxaSocialFeed\Feed;

use Pixelant\PxaSocialFeed\Domain\Model\Configuration;
use Pixelant\PxaSocialFeed\Feed\Source\FeedSourceInterface;
use Pixelant\PxaSocialFeed\Feed\Source\LinkedInSource;
use Pixelant\PxaSocialFeed\Feed\Source\YoutubeSource;
use Pixelant\PxaSocialFeed\Feed\Update\FeedUpdaterInterface;
use Pixelant\PxaSocialFeed\Feed\Update\LinkedInFeedUpdater;
use Pixelant\PxaSocialFeed\Feed\Update\YoutubeFeedUpdater;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LinkedInFactory
 * @package Pixelant\PxaSocialFeed\Feed
 */
class LinkedInFactory implements FeedFactoryInterface
{

    /**
     * Feed source allow to fetch feed items
     *
     * @param Configuration $configuration
     * @return FeedSourceInterface
     */
    public function getFeedSource(Configuration $configuration): FeedSourceInterface
    {
        return GeneralUtility::makeInstance(LinkedInSource::class, $configuration);
    }

    /**
     * Feed updater. Will create/update feed items
     *
     * @return FeedUpdaterInterface
     */
    public function getFeedUpdater(): FeedUpdaterInterface
    {
        return GeneralUtility::makeInstance(LinkedInFeedUpdater::class);
    }
}
