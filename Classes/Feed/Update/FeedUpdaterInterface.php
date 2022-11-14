<?php
declare(strict_types=1);

namespace Pixelant\PxaSocialFeed\Feed\Update;

use Pixelant\PxaSocialFeed\Domain\Model\Configuration;
use Pixelant\PxaSocialFeed\Feed\Source\FeedSourceInterface;

/**
 * Class FeedUpdaterInterface
 * @package Pixelant\PxaSocialFeed\Feed\Update
 */
interface FeedUpdaterInterface
{
    /**
     * Create/Update feed items
     *
     * @param FeedSourceInterface $source
     */
    public function update(FeedSourceInterface $source): void;

    /**
     * Persist all updates
     */
    public function persist(): void;

    /**
     * Get images
     */
    public function loadImages(): void;

    /**
     * Clean outdated records after persist
     *
     * @param Configuration $configuration Import configuration
     */
    public function cleanUp(Configuration $configuration): void;

}
