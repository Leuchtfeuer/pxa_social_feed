<?php
declare(strict_types=1);

namespace Pixelant\PxaSocialFeed\Feed\Source;

/**
 * Class FacebookSource
 * @package Pixelant\PxaSocialFeed\Feed\Source
 */
class FacebookSource extends BaseFacebookSource
{
    /**
     * Load feed source
     *
     * @return array Feed items
     * @throws InvalidFeedSourceData
     */
    public function load(): array
    {
        // get PageAccessToken


        $endPointUrl = $this->generateEndPoint($this->getConfiguration()->getSocialId(), 'feed');
        $str = $this->getConfiguration()->getToken()->getFb()::BASE_GRAPH_URL .
            self::GRAPH_VERSION . '/' . $endPointUrl;
        $response = file_get_contents(
            $str
        );
        $response = json_decode($response, true);

        return $this->getDataFromResponse($response);
    }

    /**
     * Return fields for endpoint request
     *
     * @return array
     */
    protected function getEndPointFields(): array
    {
        return [
            'reactions.summary(true).limit(0)',
            'message',
            'attachments',
            'created_time',
            'updated_time',
            'access_token',
        ];
    }
}
