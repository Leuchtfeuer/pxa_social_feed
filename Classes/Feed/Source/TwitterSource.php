<?php
declare(strict_types=1);

namespace Pixelant\PxaSocialFeed\Feed\Source;

use Pixelant\PxaSocialFeed\Exception\BadResponseException;
use Pixelant\PxaSocialFeed\Exception\InvalidFeedSourceData;
use Psr\Http\Message\ResponseInterface;

/**
 * Class TwitterSource
 * @package Pixelant\PxaSocialFeed\Feed\Source
 */
class TwitterSource extends BaseSource
{
    /**
     * Twitter api
     */
    const API_URL = 'https://api.twitter.com/2/';

    /**
     * Load feed source
     *
     * @return array Feed items
     */
    public function load(): array
    {
        $configuration = $this->getConfiguration();

        $endPointUrl = $this->generateEndPointUrl('users/' . $configuration->getSocialId() . '/tweets');
        $fields = $this->getFields();

        $authHeader = $this->getAuthHeader($endPointUrl, $fields);

        $response = $this->requestTwitterApi(
            $this->addFieldsAsGetParametersToUrl($endPointUrl, $fields),
            $authHeader
        );

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new InvalidFeedSourceData(
                "Twitter response doesn't appear to be a valid json. Response return '$body'.",
                1562910457024
            );
        }

        return $data;
    }

    /**
     * Request twitter api
     *
     * @param string $url
     * @param string $autHeader
     * @return ResponseInterface
     * @throws BadResponseException
     */
    protected function requestTwitterApi(string $url, string $autHeader): ResponseInterface
    {
        $additionalOptions = [
            'headers' => [
                'Authorization' => $autHeader
            ]
        ];

        return $this->performApiGetRequest($url, $additionalOptions);
    }

    /**
     * Generate url for request
     *
     * @param string $endPoint
     * @return string
     */
    protected function generateEndPointUrl(string $endPoint)
    {
        return $this->getApiUrl() . $endPoint;
    }

    /**
     * Get API url
     *
     * @return string
     */
    protected function getApiUrl(): string
    {
        return self::API_URL;
    }

    /**
     * Query fields
     *
     * @return array
     */
    protected function getFields(): array
    {
        $configuration = $this->getConfiguration();

        // Important to pass field value as string, because it's encoded with rawurlencode
        $fields = [
            'max_results' => (string)$configuration->getMaxItems(),
            'exclude' => 'replies',
            'tweet.fields' => 'id,created_at,text,author_id,public_metrics,attachments',
            'media.fields' => 'media_key,url',
            'user.fields' => 'username',
            'expansions' => 'attachments.media_keys,author_id',
        ];

        list($fields) = $this->emitSignal('beforeReturnTwitterQueryFields', [$fields]);

        return $fields;
    }

    /**
     * Get Authorization header
     *
     * @param string $url
     * @param array $fields
     * @return string
     */
    protected function getAuthHeader(string $url, array $fields): string
    {
        $token = $this->getConfiguration()->getToken();
        $oauth = [
            'oauth_consumer_key' => $token->getApiKey(),
            'oauth_nonce' => md5((string)mt_rand()),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $token->getAccessToken(),
            'oauth_timestamp' => (string)time(),
            'oauth_version' => '1.0'
        ];

        $sigBase = $this->buildSigBase(array_merge($oauth, $fields), $url);
        $sigKey = rawurlencode($token->getApiSecretKey()) . '&' . rawurlencode($token->getAccessTokenSecret());

        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $sigBase, $sigKey, true));

        $header = 'OAuth ';
        $headerValues = [];

        foreach ($oauth as $key => $value) {
            $headerValues[] = $key . '="' . rawurlencode($value) . '"';
        }

        $header .= implode(', ', $headerValues);

        return $header;
    }

    /**
     * Generate the base string
     *
     * @param array $oauth
     * @param string $url
     * @return string Built base string
     */
    protected function buildSigBase(array $oauth, string $url)
    {
        ksort($oauth);
        $urlParts = [];

        foreach ($oauth as $key => $value) {
            $urlParts[] = $key . '=' . rawurlencode($value);
        }

        return 'GET' . '&' . rawurlencode($url) . '&' . rawurlencode(implode('&', $urlParts));
    }
}
