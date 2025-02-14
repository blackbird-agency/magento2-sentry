<?php

namespace JustBetter\Sentry\Helper;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\App\View\Deployment\Version\StorageInterface;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

/**
 * Deployment version of static files.
 */
class Version extends AbstractHelper
{
    public const VERSION_FILE = 'version.txt';

    /**
     * @var string
     */
    private $cachedValue;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $flagDir;

    /**
     * @param State $appState
     * @param StorageInterface $versionStorage
     * @param Filesystem $filesystem
     * @param DeploymentConfig|null $deploymentConfig
     * @throws FileSystemException
     */
    public function __construct(
        private \Magento\Framework\App\State $appState,
        private \Magento\Framework\App\View\Deployment\Version\StorageInterface $versionStorage,
        private Filesystem $filesystem,
        DeploymentConfig $deploymentConfig = null
    ) {
        $this->flagDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->deploymentConfig = $deploymentConfig ?: ObjectManager::getInstance()->get(DeploymentConfig::class);
    }

    /**
     * Retrieve deployment version of static files.
     *
     * @return string
     */
    public function getValue()
    {
        if (!$this->cachedValue) {
            // Get version from file if exists or from static version otherwise
            if($this->flagDir->isExist(static::VERSION_FILE)) {
                try {
                    $this->cachedValue = $this->flagDir->readFile(static::VERSION_FILE);
                } catch (FileSystemException $e) {
                }
            }
            if(!isset($this->cachedValue)){
                $this->cachedValue = $this->readValue($this->appState->getMode());
            }
        }

        return $this->cachedValue;
    }

    /**
     * Load or generate deployment version of static files depending on the application mode.
     *
     * @param string $appMode
     *
     * @return string
     */
    protected function readValue($appMode)
    {
        $result = $this->versionStorage->load();
        if (!$result) {
            if ($appMode == \Magento\Framework\App\State::MODE_PRODUCTION
                && !$this->deploymentConfig->getConfigData(
                    ConfigOptionsListConstants::CONFIG_PATH_SCD_ON_DEMAND_IN_PRODUCTION
                )
            ) {
                $this->getLogger()->critical('Can not load static content version.');

                throw new \UnexpectedValueException(
                    'Unable to retrieve deployment version of static files from the file system.'
                );
            }
            $result = $this->generateVersion();
            $this->versionStorage->save($result);
        }

        return $result;
    }

    /**
     * Generate version of static content.
     *
     * @return int
     */
    private function generateVersion()
    {
        return time();
    }

    /**
     * Get logger.
     *
     * @return LoggerInterface
     */
    private function getLogger()
    {
        if ($this->logger == null) {
            $this->logger = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(LoggerInterface::class);
        }

        return $this->logger;
    }
}
