<?php
declare(strict_types=1);

namespace Pixelant\PxaSocialFeed\Feed\Source;

use Pixelant\PxaSocialFeed\Exception\InvalidFeedSourceData;

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
        $endPointUrl = $this->generateEndPoint($this->getConfiguration()->getSocialId(), 'feed');

        $url =             $this->getConfiguration()->getToken()->getFb()::BASE_GRAPH_URL .
            self::GRAPH_VERSION . '/' . $endPointUrl
;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response ?: "", true);

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
