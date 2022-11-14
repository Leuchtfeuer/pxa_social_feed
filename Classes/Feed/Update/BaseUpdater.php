<?php
declare(strict_types=1);

namespace Pixelant\PxaSocialFeed\Feed\Update;

use Exception;
use GuzzleHttp\Client;
use Pixelant\PxaSocialFeed\Domain\Model\Configuration;
use Pixelant\PxaSocialFeed\Domain\Model\Feed;
use Pixelant\PxaSocialFeed\Domain\Repository\FeedRepository;
use Pixelant\PxaSocialFeed\SignalSlot\EmitSignalTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Class BaseUpdater
 * @package Pixelant\PxaSocialFeed\Feed\Update
 */
abstract class BaseUpdater implements FeedUpdaterInterface
{
    use EmitSignalTrait;

    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * @var FeedRepository
     */
    protected $feedRepository = null;

    /**
     * Keep all processed feed items
     *
     * @var ObjectStorage
     */
    protected $feeds = null;

    protected $ranAlready = false;

    /**
     * BaseUpdater constructor.
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->feedRepository = $this->objectManager->get(FeedRepository::class);
        $this->feeds = new ObjectStorage();
    }

    public function createImageRelations(Feed $feed)
    {
        try {
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
            $fileObject = $resourceFactory->getFileObject($feed->getImage());
            $contentElement = BackendUtility::getRecord(
                'tx_pxasocialfeed_domain_model_feed',
                (int)$feed->getUid()
            );
// Assemble DataHandler data
            $newId = 'NEW1234';
            $data = [];
            $data['sys_file_reference'][$newId] = [
                'table_local' => 'sys_file',
                'uid_local' => $fileObject->getUid(),
                'tablenames' => 'tx_pxasocialfeed_domain_model_feed',
                'uid_foreign' => $contentElement['uid'],
                'fieldname' => 'image',
                'pid' => $contentElement['pid']
            ];
            $data['tx_pxasocialfeed_domain_model_feed'][$contentElement['uid']] = [
                'image' => $newId
            ];
            // Get an instance of the DataHandler and process the data
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($data, []);
            $dataHandler->process_datamap();
            // Error or success reporting
            if (count($dataHandler->errorLog) === 0) {
                // Handle success
            } else {
                // Handle errors
            }
        } catch (FileDoesNotExistException $e) {
            // TODO: ignore in WIP
        }

    }

    public function storeImg($url, BaseUpdater $instance, Feed $feeditem)
    {
        $resourceFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Resource\ResourceFactory::class
        );
        $storage = $resourceFactory->getDefaultStorage();

        // create folder if it does not exist
        $folderNormal = 'socialmedia/instacontent/normal';
        if (!$storage->hasFolder($folderNormal)) {
            $storage->createFolder($folderNormal);
        };
        $downloadFolderNormal = $storage->getFolder($folderNormal);

        $folderSmall = 'socialmedia/instacontent/small';
        if (!$storage->hasFolder($folderSmall)) {
            $storage->createFolder($folderSmall);
        };
        $downloadFolderSmall = $storage->getFolder($folderSmall);


        $filenameOriginal = explode('?', basename($url), 2);
        // create unique filename
        if (is_string($filenameOriginal[0])) {
            $filename = md5($url) . "." . pathinfo($filenameOriginal[0], PATHINFO_EXTENSION);
        } else {
            // assume jpg
            $filename = md5($url) . ".jpg";
        }
        $normal_f_name = $filename;
        $small_f_name = 'small_' . $filename;


        $httpClient = $instance->objectManager->get(Client::class);
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);

        try {
            $response = $requestFactory->request($url, 'GET');

            if ($response->getStatusCode() === 200) {

                $file_normal = $downloadFolderNormal->createFile($normal_f_name);
                $file_normal->setContents($response->getBody()->getContents());
                $feeditem->setImage($file_normal->getUid());

            } else if ($response->getStatusCode() === 404) {
                // not found
            } else {
                throw new \RuntimeException('Could not download file. Maybe the token is not valid.', 1667409548);
            }
        } catch (Exception $exception) {

        }

        $conf = $storage->getConfiguration();

        // need to minify the image here, dunno how
        //$file_small->setContents($response->getBody()->getContents());


        return [
            'normal_image' => '/' . $conf['basePath'] . 'socialmedia/instacontent/normal/' . $normal_f_name,
            'small_image' => '/' . $conf['basePath'] . 'socialmedia/instacontent/small/MHGEI_' . $normal_f_name
            //'small_image' => '/' . $conf['basePath'] . 'socialmedia/instacontent/small/' . $small_f_name
        ];
    }

    /**
     * Persist changes
     */
    public function persist(): void
    {
        $this->objectManager->get(PersistenceManagerInterface::class)->persistAll();
        if (!$this->ranAlready) {
            $this->createImageRelation();
            $this->ranAlready = true;
        }
    }

    /**
     * Clean all outdated records
     *
     * @param Configuration $configuration
     */
    public function cleanUp(Configuration $configuration): void
    {
        if (count($this->feeds) > 0) {
            /** @var Feed $feedToRemove */
            foreach ($this->feedRepository->findNotInStorage($this->feeds, $configuration) as $feedToRemove) {
                $this->getSignalSlotDispatcher()->dispatch(__CLASS__, 'changedFeedItem', [$feedToRemove]);
                $this->feedRepository->remove($feedToRemove);
            }
        }
    }

    /**
     * Creates relation between feed and image
     * @return void
     */
    public function createImageRelation(): void
    {
        foreach ($this->feeds as $feed) {
            $this->createImageRelations($feed);
        }
    }

    /**
     * Add or update feed object.
     * Save all processed items
     *
     * @param Feed $feed
     */
    protected function addOrUpdateFeedItem(Feed $feed): void
    {
        // Check if $feed is new or modified and emit change event
        if ($feed->_isDirty() || $feed->_isNew()) {
            $this->getSignalSlotDispatcher()->dispatch(__CLASS__, 'changedFeedItem', [$feed]);
        }

        $this->feeds->attach($feed);
        $this->feedRepository->{$feed->_isNew() ? 'add' : 'update'}($feed);
    }

    /**
     * Use json_encode to get emoji character convert to unicode
     * @TODO is there better way to do this ?
     *
     * @param $message
     * @return string
     */
    protected function encodeMessage(string $message): string
    {
        return filter_var($message,
            FILTER_SANITIZE_STRING);
    }
}
