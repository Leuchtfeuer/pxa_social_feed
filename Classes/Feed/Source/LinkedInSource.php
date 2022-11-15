<?php
declare(strict_types=1);

namespace Pixelant\PxaSocialFeed\Feed\Source;

use Pixelant\PxaSocialFeed\Domain\Model\Configuration;
use Pixelant\PxaSocialFeed\Exception\BadResponseException;
use Pixelant\PxaSocialFeed\Exception\InvalidFeedSourceData;
use Psr\Http\Message\ResponseInterface;

/**
 * Class LinkedInSource
 * @package Pixelant\PxaSocialFeed\Feed\Source
 */
class LinkedInSource extends BaseSource
{
    /**
     * Load feed source
     *
     * @return array Feed items
     */
    public function load(): array
    {
        // no source of feeds for linkedin
        return [];
    }

    /**
     * Request youtube api
     *
     * @param string $url
     * @return ResponseInterface
     * @throws BadResponseException
     */
    protected function requestYoutubeApi(string $url): ResponseInterface
    {
        return $this->performApiGetRequest($url);
    }

    /**
     * Youtube api endpoint url
     *
     * @param string $endPoint
     * @return string
     */
    protected function generateEndPointUrl(string $endPoint): string
    {
        return $this->getUrl() . $endPoint;
    }

    /**
     * Get api url
     *
     * @return string
     */
    protected function getUrl(): string
    {
        return self::API_URL;
    }

    /**
     * Get youtube fields for request
     *
     * @param Configuration $configuration
     * @return array
     */
    protected function getFields(Configuration $configuration): array
    {
        $fields = [
            'order' => 'date',
            'part' => 'snippet',
            'type' => 'video',
            'maxResults' => $configuration->getMaxItems(),
            //'channelId' => $configuration->getSocialId(),
            'channelId' => '[Not needed]',
            'key' => $configuration->getToken()->getApiKey()
        ];

        list($fields) = $this->emitSignal('youtubeEndPointRequestFields', [$fields]);

        return $fields;
    }
}
