<?php

declare(strict_types=1);

namespace Pixelant\PxaSocialFeed\Feed\Source;

use Pixelant\PxaSocialFeed\Exception\InvalidFeedSourceData;

/**
 * Class FacebookSource
 */
class FacebookBusinessSource extends BaseFacebookSource
{
    public function generateEndPoint(string $id, string $endPointEntry): string
    {
        $limit = $this->getConfiguration()->getMaxItems();

        $fields = $this->getEndPointFields();

        [$fields] = $this->emitSignal('facebookEndPointRequestFields', [$fields]);

        $url = $id . '/' . $endPointEntry;

        $queryParams = [
            'fields' => implode(',', $fields),
            'limit' => $limit,
            'access_token' => $this->getConfiguration()->getToken()->getAccessToken(),
            'appsecret_proof' => hash_hmac(
                'sha256',
                $this->getConfiguration()->getToken()->getAccessToken(),
                $this->getConfiguration()->getToken()->getAppSecret()
            ),
        ];

        $endPoint = $this->addFieldsAsGetParametersToUrl($url, $queryParams);

        [$endPoint] = $this->emitSignal('faceBookEndPoint', [$endPoint]);

        return $endPoint;
    }

    /**
     * Load feed source
     *
     * @return array Feed items
     * @throws InvalidFeedSourceData
     */
    public function load(): array
    {
        $endPointUrl = $this->generateEndPoint($this->getConfiguration()->getSocialId(), 'feed');
        $str = $this->getConfiguration()->getToken()->getFb()::BASE_GRAPH_URL .
            self::GRAPH_VERSION . '/' . $endPointUrl;
        // https://graph.facebook.com/v16.0/me/feed?access_token=EAATeLDdBZAKsBAKVL2dN7K5TrZBJgdDNZBETmVLwpB6TSGS9syTGG1ePZBYyf26DVO68PKNW2CuUE98Sv9OD3L1KT1UQPRC4ntIoPALsZAqA5pU6a7EBFzYtF255baaraZAE0I2spypJ1Xb9D9Q4Q5pJqnBITBlIH8OLHaZAPVrlcYWlAbVIhsLapPNvslIvOJqZCR2cTESN8baVW2q0ydib
        // https://graph.facebook.com/v12.0/me/feed?fields=reactions.summary%28true%29.limit%280%29%2Cmessage%2Cattachments%2Ccreated_time%2Cupdated_time&limit=10&access_token=EAATeLDdBZAKsBAKVL2dN7K5TrZBJgdDNZBETmVLwpB6TSGS9syTGG1ePZBYyf26DVO68PKNW2CuUE98Sv9OD3L1KT1UQPRC4ntIoPALsZAqA5pU6a7EBFzYtF255baaraZAE0I2spypJ1Xb9D9Q4Q5pJqnBITBlIH8OLHaZAPVrlcYWlAbVIhsLapPNvslIvOJqZCR2cTESN8baVW2q0ydib&appsecret_proof=192cde8bd44e0d79ed5965a93d2833abc5ce3e2798cec3da460f8a25556165c7
        //die;
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
            'access_token',
            'attachments',
            'created_time',
            'updated_time',
        ];
    }
}
