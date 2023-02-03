<?php
declare(strict_types=1);

namespace Pixelant\PxaSocialFeed\Feed\Update;

use Pixelant\PxaSocialFeed\Domain\Model\Configuration;
use Pixelant\PxaSocialFeed\Domain\Model\Feed;
use Pixelant\PxaSocialFeed\Domain\Model\Token;
use Pixelant\PxaSocialFeed\Feed\Source\FeedSourceInterface;

/**
 * Class TwitterFeedUpdater
 * @package Pixelant\PxaSocialFeed\Feed\Update
 */
class TwitterFeedUpdater extends BaseUpdater
{

    /**
     * Create/Update feed items
     *
     * @param FeedSourceInterface $source
     */
    public function update(FeedSourceInterface $source): void
    {
        $items = $source->load();

        if (!empty($items['data'])) {
            foreach ($items['data'] as $rawData) {
                $feedItem = $this->feedRepository->findOneByExternalIdentifier(
                    $rawData['id'],
                    $source->getConfiguration()->getStorage()
                );

                // get image url from data
                $rawData['imgUrl'] = '';
                if (isset($rawData['attachments']) && isset($rawData['attachments']['media_keys']) && !empty($rawData['attachments']['media_keys'][0])) {
                    foreach ($items['includes']['media'] as $media) {
                        if ($media['media_key'] === $rawData['attachments']['media_keys'][0]) {
                            $rawData['imgUrl'] = $media['url'];
                            break;
                        }
                    }
                }

                // get username from data
                $rawData['username'] = '';
                if (!empty($rawData['author_id'])) {
                    foreach ($items['includes']['users'] as $users) {
                        if ($users['id'] === $rawData['author_id']) {
                            $rawData['username'] = $users['username'];
                        }
                    }
                }

                if ($feedItem === null) {
                    $feedItem = $this->createFeedItem($rawData, $source->getConfiguration());
                }

                $this->updateFeedItem($feedItem, $rawData);

                // Call hook
                $this->emitSignal('beforeUpdateTwitterFeed', [$feedItem, $rawData, $source->getConfiguration()]);

                $this->addOrUpdateFeedItem($feedItem);
            }
        }
    }

    /**
     * Create new twitter feed
     *
     * @param array $rawData
     * @param Configuration $configuration
     * @return Feed
     */
    protected function createFeedItem(array $rawData, Configuration $configuration): Feed
    {
        $feedItem = $this->objectManager->get(Feed::class);
        $date = new \DateTime($rawData['created_at']);

        $feedItem->setPostDate($date);
        $feedItem->setPostUrl(
            'https://twitter.com/' . $rawData['username'] . '/status/' . $rawData['id']
        );
        $feedItem->setConfiguration($configuration);
        $feedItem->setExternalIdentifier($rawData['id']);
        $feedItem->setPid($configuration->getStorage());
        $feedItem->setType(Token::TWITTER);

        return $feedItem;
    }

    /**
     * Update feed item properties with raw data
     *
     * @param Feed $feedItem
     * @param array $rawData
     * @return void
     */
    protected function updateFeedItem(Feed $feedItem, array $rawData): void
    {
        // Update text
        $text = $rawData['full_text'] ?: $rawData['text'] ?: '';
        if ($feedItem->getMessage() != $text) {
            $feedItem->setMessage($this->encodeMessage($text));
        }

        // Media
        $image = $rawData['imgUrl'] ?? '';
        if ($feedItem->getImageUrl() != $image) {
            $feedItem->setImageUrl($image);
        }

        $likes = intval($rawData['public_metrics']['like_count'] ?? 0);
        if ($likes != $feedItem->getLikes()) {
            $feedItem->setLikes($likes);
        }
    }
}
