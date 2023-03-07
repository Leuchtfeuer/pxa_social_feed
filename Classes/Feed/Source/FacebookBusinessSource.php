<?php
declare(strict_types=1);

namespace Pixelant\PxaSocialFeed\Feed\Source;

use Pixelant\PxaSocialFeed\Exception\InvalidFeedSourceData;

/**
 * Class FacebookSource
 * @package Pixelant\PxaSocialFeed\Feed\Source
 */
class FacebookBusinessSource extends BaseFacebookSource
{
    /**
     * Load feed source
     *
     * @return array Feed items
     * @throws InvalidFeedSourceData
     */
    public function load(): array
    {
        // get ephemeral page access token for this request
        $config = $this->getConfiguration();
        $fb = $config->getToken()->getFb();
        $pageAccessToken = $fb->getPageAccessToken($this->getConfiguration()->getToken(), $this->getConfiguration()->getSocialId());

        $fields = implode(',', $this->getEndPointFields());

        $str = $fb::BASE_GRAPH_URL .
            self::GRAPH_VERSION . '/' .$this->generateBusinessEndPoint($this->getConfiguration()->getSocialId(), 'feed', $pageAccessToken);

        $response = file_get_contents(
            $str
        );
//        https://graph.facebook.com/v12.0/263697775505133/feed?limit=10&access_token=EAATeLDdBZAKsBAMkLYkz4VwGmjT38U3oRi5eL9dEnRQeqvf4E3b4PnFEfZB1gnOi4oysFYy76PelyFMENM9lOzxClvZC82RZCdED6OclWHIAPOyPkwolGk8VxIMjTbeJimY4MFm6ejFR6DEGKUB5e8HLDcstEcsfZCHfI6FFYLersqMhWBQHuv6LfJWSuzgUZD
//        https://graph.facebook.com/v12.0/263697775505133/feeds?fields=reactions.summary%28true%29.limit%280%29%2Cmessage%2Cattachments%2Ccreated_time%2Cupdated_time%2Caccess_token&limit=10&access_token=EAATeLDdBZAKsBAHnfIUzFHFw2jlcPQwNOUt9b3hIDPT9OaWFCZB8IxZATpanZCcLBDHYDm0ZBe6Am0EgEJrri1zDL3WuKF2ZC0eBNqZC788oNavRMnr5NpYIEw3L5MgTOO24ZAF8zZC07mcQ53xIB0ObFMZA2lGhbvBzZCF8t1zhc2fzEdk7oPobpNz3M8r81mYn9YZD&appsecret_proof=d2748e6cbd0426d1df67563d7ba4ae86ab9049654efbebe042082aed72a5da55
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

    /**
     * Generate facebook endpoint
     *
     * @param string $id
     * @param string $endPointEntry
     * @param string $accessToken
     * @return string
     */
    protected function generateBusinessEndPoint(string $id, string $endPointEntry, string $accessToken): string
    {
        $limit = $this->getConfiguration()->getMaxItems();

        $fields = $this->getEndPointFields();

        [$fields] = $this->emitSignal('facebookEndPointRequestFields', [$fields]);

        $url = $id . '/' . $endPointEntry;

        $queryParams = [
            'fields' => implode(',', $fields),
            'limit' => $limit,
            'access_token' => $accessToken,
            'appsecret_proof' => hash_hmac(
                'sha256',
                $accessToken,
                $this->getConfiguration()->getToken()->getAppSecret()
            ),
        ];

        $endPoint = $this->addFieldsAsGetParametersToUrl($url, $queryParams);

        [$endPoint] = $this->emitSignal('faceBookEndPoint', [$endPoint]);

        return $endPoint;
    }
}
