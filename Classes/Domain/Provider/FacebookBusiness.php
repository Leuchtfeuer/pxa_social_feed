<?php

namespace Pixelant\PxaSocialFeed\Domain\Provider;

use FacebookAds\Api;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\AppSecretProof;
use League\OAuth2\Client\Provider\Exception\FacebookProviderException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Pixelant\PxaSocialFeed\Domain\Model\Token;
use Pixelant\PxaSocialFeed\Exception\InvalidFeedSourceData;
use Psr\Http\Message\ResponseInterface;

/**
 * @method FacebookBusinessUser getResourceOwner(AccessToken $token)
 */
class FacebookBusiness extends AbstractProvider
{
    /**
     * Production Graph API URL.
     *
     * @const string
     */
    const BASE_FACEBOOK_URL = 'https://www.facebook.com/';

    /**
     * Beta tier URL of the Graph API.
     *
     * @const string
     */
    const BASE_FACEBOOK_URL_BETA = 'https://www.beta.facebook.com/';

    /**
     * Production Graph API URL.
     *
     * @const string
     */
    const BASE_GRAPH_URL = 'https://graph.facebook.com/';

    /**
     * Beta tier URL of the Graph API.
     *
     * @const string
     */
    const BASE_GRAPH_URL_BETA = 'https://graph.beta.facebook.com/';

    /**
     * Regular expression used to check for graph API version format
     *
     * @const string
     */
    const GRAPH_API_VERSION_REGEX = '~^v\d+\.\d+$~';

    /**
     * The Graph API version to use for requests.
     *
     * @var string
     */
    protected $graphApiVersion;

    /**
     * A toggle to enable the beta tier URL's.
     *
     * @var bool
     */
    private $enableBetaMode = false;

    /**
     * @param array $options
     * @param array $collaborators
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        if (empty($options['graphApiVersion'])) {
            $message = 'The "graphApiVersion" option not set. Please set a default Graph API version.';
            throw new \InvalidArgumentException($message);
        }
        if (!preg_match(self::GRAPH_API_VERSION_REGEX, $options['graphApiVersion'])) {
            $message = 'The "graphApiVersion" must start with letter "v" followed by version number, ie: "v2.4".';
            throw new \InvalidArgumentException($message);
        }

        $this->graphApiVersion = $options['graphApiVersion'];

        if (!empty($options['enableBetaTier']) && $options['enableBetaTier'] === true) {
            $this->enableBetaMode = true;
        }
    }

    public function getBaseAuthorizationUrl()
    {
        return $this->getBaseFacebookUrl() . $this->graphApiVersion . '/dialog/oauth';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getBaseGraphUrl() . $this->graphApiVersion . '/oauth/access_token';
    }

    public function getDefaultScopes()
    {
        return ['public_profile', 'email'];
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        $fields = [
            'id', 'name', 'first_name', 'last_name',
            'email', 'hometown', 'picture.type(large){url,is_silhouette}',
            'gender', 'age_range, accounts, ids_for_apps',
        ];

        // backwards compatibility less than 2.8
        if (version_compare(substr($this->graphApiVersion, 1), '2.8') < 0) {
            $fields[] = 'bio';
        }

        $appSecretProof = AppSecretProof::create($this->clientSecret, $token->getToken());

        return $this->getBaseGraphUrl() . $this->graphApiVersion . '/me?fields=' . implode(',', $fields)
                        . '&access_token=' . $token . '&appsecret_proof=' . $appSecretProof;
    }

    public function getAccessToken($grant = 'authorization_code', array $params = [])
    {
        if (isset($params['refresh_token'])) {
            throw new FacebookProviderException('Facebook does not support token refreshing.');
        }

        return parent::getAccessToken($grant, $params);
    }

    /**
     * Exchanges a short-lived access token with a long-lived access-token.
     *
     * @param string $accessToken
     *
     * @return AccessToken
     *
     * @throws FacebookProviderException
     */
    public function getLongLivedAccessToken($accessToken)
    {
        $params = [
            'fb_exchange_token' => (string)$accessToken,
        ];

        return $this->getAccessToken('fb_exchange_token', $params);
    }

    /**
     * @throws IdentityProviderException
     * @throws \Exception
     */
    public function getPageAccessToken(Token $accessToken, string $socialId): string
    {
        $fields = [
            'name',
            'access_token',
        ];

        $str = $this->getBaseGraphUrl() . $this->graphApiVersion . '/me/accounts?fields=' . implode(',', $fields)
            . '&access_token=' . $accessToken->getAccessToken();
        $response = file_get_contents(
            $str
        );
        $response = json_decode($response, true);
        $data = $this->getDataFromResponse($response);

        foreach ($data as $item) {
            if ($item['id'] === $socialId) {
                return $item['access_token'];
            }
        }

        return "";
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new FacebookBusinessUser($response);
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            $message = $data['error']['type'] . ': ' . $data['error']['message'];
            throw new IdentityProviderException($message, $data['error']['code'], $data);
        }
    }

    /**
     * @inheritdoc
     */
    protected function getContentType(ResponseInterface $response)
    {
        $type = parent::getContentType($response);

        // Fix for Facebook's pseudo-JSONP support
        if (strpos($type, 'javascript') !== false) {
            return 'application/json';
        }

        // Fix for Facebook's pseudo-urlencoded support
        if (strpos($type, 'plain') !== false) {
            return 'application/x-www-form-urlencoded';
        }

        return $type;
    }

    /**
     * Get the base Facebook URL.
     *
     * @return string
     */
    private function getBaseFacebookUrl()
    {
        return $this->enableBetaMode ? static::BASE_FACEBOOK_URL_BETA : static::BASE_FACEBOOK_URL;
    }

    /**
     * Get the base Graph API URL.
     *
     * @return string
     */
    private function getBaseGraphUrl()
    {
        return $this->enableBetaMode ? static::BASE_GRAPH_URL_BETA : static::BASE_GRAPH_URL;
    }
    /**
     * Get data from facebook
     *
     * @param array $response
     * @return array
     */
    protected function getDataFromResponse(array $response): array
    {
        if (!is_array($response) || !isset($response['data'])) {
            throw new \Exception (
                'Invalid data received for configuration ' . $this->getConfiguration()->getName() . '.',
                1562842385128
            );
        }

        return $response['data'];
    }
}
