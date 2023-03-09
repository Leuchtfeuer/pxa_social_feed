<?php
declare(strict_types=1);

namespace Pixelant\PxaSocialFeed\Feed\Source;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Pixelant\PxaSocialFeed\Exception\InvalidFeedSourceData;

/**
 * Class FacebookBusinessSource
 * @package Pixelant\PxaSocialFeed\Feed\Source
 */
class FacebookBusinessSource extends BaseFacebookSource
{
    /**
     * Load feed source
     *
     * This implementation uses the saved (personal) access_token to get an ephemeral page access_token for
     * the configured social id (page id). This is necessary because the personal access_token is not allowed
     * to access the page feed.
     *
     * @return array Feed items
     * @throws InvalidFeedSourceData
     * @throws IdentityProviderException
     */
    public function load(): array
    {
        // get ephemeral page access token for this request
        $config = $this->getConfiguration();
        $fb = $config->getToken()->getFb();
        $pageAccessToken = $fb->getPageAccessToken($this->getConfiguration()->getToken(), $this->getConfiguration()->getSocialId());

        $response = file_get_contents(
            $fb::BASE_GRAPH_URL .
            self::GRAPH_VERSION . '/' .$this->generateBusinessEndPoint($this->getConfiguration()->getSocialId(), 'feed', $pageAccessToken)
        );
        $response = json_decode($response, true,512, JSON_INVALID_UTF8_SUBSTITUTE  );

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
     * Generate facebookbusiness endpoint
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

        [$fields] = $this->emitSignal('facebookBusinessEndPointRequestFields', [$fields]);

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

        [$endPoint] = $this->emitSignal('faceBookBusinessEndPoint', [$endPoint]);

        return $endPoint;
    }
}
