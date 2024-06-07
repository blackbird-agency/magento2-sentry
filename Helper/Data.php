<?php

namespace JustBetter\Sentry\Helper;

use Magento\Framework\App\Area;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\State;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\RuntimeException;

class Data extends AbstractHelper
{
    const XML_PATH_SRS = 'sentry/general/';
    const XML_PATH_SRS_ISSUE_GROUPING = 'sentry/issue_grouping/';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array
     */
    protected $configKeys = [
        'dsn',
        'logrocket_key',
        'log_level',
        'errorexception_reporting',
        'ignore_exceptions',
        'mage_mode_development',
        'environment',
    ];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var State
     */
    protected $appState;

    /**
     * Data constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param State                 $appState
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        State $appState,
        ProductMetadataInterface $productMetadataInterface,
        DeploymentConfig $deploymentConfig
    ) {
        $this->storeManager = $storeManager;
        $this->appState = $appState;
        $this->scopeConfig = $context->getScopeConfig();
        $this->productMetadataInterface = $productMetadataInterface;
        $this->deploymentConfig = $deploymentConfig;
        $this->collectModuleConfig();

        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function getDSN()
    {
        return $this->collectModuleConfig()['dsn'];
    }

    /**
     * @return mixed
     */
    public function getEnvironment()
    {
        return $this->collectModuleConfig()['environment'];
    }

    /**
     * @param      $field
     * @param null $storeId
     *
     * @return mixed
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param      $code
     * @param null $storeId
     *
     * @return mixed
     */
    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SRS.$code, $storeId);
    }

    /**
     * @return array
     */
    public function collectModuleConfig()
    {
        if (isset($this->config[$this->getStoreId()]['enabled'])) {
            return $this->config[$this->getStoreId()];
        }

        try {
            $this->config[$this->getStoreId()]['enabled'] = $this->scopeConfig->getValue('sentry/environment/enabled', ScopeInterface::SCOPE_STORE)
                ?? $this->deploymentConfig->get('sentry') !== null;
        } catch (TableNotFoundException|FileSystemException|RuntimeException $e) {
            $this->config[$this->getStoreId()]['enabled'] = null;
        }

        foreach ($this->configKeys as $value) {
            try {
                $this->config[$this->getStoreId()][$value] = $this->scopeConfig->getValue('sentry/environment/'.$value, ScopeInterface::SCOPE_STORE)
                    ?? $this->deploymentConfig->get('sentry/'.$value);
            } catch (TableNotFoundException|FileSystemException|RuntimeException $e) {
                $this->config[$this->getStoreId()][$value] = null;
            }
        }

        return $this->config[$this->getStoreId()];
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isActiveWithReason()['active'];
    }

    /**
     * @param string $reason : Reason to tell the user why it's not active (Github issue #53)
     *
     * @return bool
     */
    public function isActiveWithReason()
    {
        $reasons = [];
        $config = $this->collectModuleConfig();
        $emptyConfig = empty($config);
        $configEnabled = isset($config['enabled']) && $config['enabled'];
        $dsnNotEmpty = $this->getDSN();
        $productionMode = ($this->isProductionMode() || $this->isOverwriteProductionMode());

        if ($emptyConfig) {
            $reasons[] = __('Config is empty.');
        }
        if (!$configEnabled) {
            $reasons[] = __('Module is not enabled in config.');
        }
        if (!$dsnNotEmpty) {
            $reasons[] = __('DSN is empty.');
        }
        if (!$productionMode) {
            $reasons[] = __('Not in production and development mode is false.');
        }

        return count($reasons) > 0 ? ['active' => false, 'reasons' => $reasons] : ['active' => true];
    }

    /**
     * @return bool
     */
    public function isProductionMode()
    {
        return $this->appState->emulateAreaCode(Area::AREA_GLOBAL, [$this, 'getAppState']) == 'production';
    }

    /**
     * @return string
     */
    public function getAppState()
    {
        return $this->appState->getMode();
    }

    /**
     * @return mixed
     */
    public function isOverwriteProductionMode()
    {
        $config = $this->collectModuleConfig();

        return isset($config['mage_mode_development']) && $config['mage_mode_development'];
    }

    /**
     *  Get the current magento version.
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->productMetadataInterface->getVersion();
    }

    /**
     * Get the current store.
     */
    public function getStore()
    {
        return $this->storeManager ? $this->storeManager->getStore() : null;
    }

    /**
     * @return bool
     */
    public function isPhpTrackingEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            static::XML_PATH_SRS.'enable_php_tracking',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function useScriptTag()
    {
        return $this->scopeConfig->isSetFlag(
            static::XML_PATH_SRS.'enable_script_tag',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $blockName
     *
     * @return bool
     */
    public function showScriptTagInThisBlock($blockName)
    {
        $config = $this->getGeneralConfig('script_tag_placement');
        if (!$config) {
            return false;
        }

        $name = 'sentry.'.$config;

        return $name == $blockName;
    }

    /**
     * @return mixed
     */
    public function getLogrocketKey()
    {
        return $this->collectModuleConfig()['logrocket_key'];
    }

    /**
     * @return bool
     */
    public function useLogrocket()
    {
        return $this->scopeConfig->isSetFlag(static::XML_PATH_SRS.'use_logrocket') &&
            isset($this->collectModuleConfig()['logrocket_key']) &&
            $this->getLogrocketKey() != null;
    }

    /**
     * @return bool
     */
    public function useLogrocketIdentify()
    {
        return $this->scopeConfig->isSetFlag(
            static::XML_PATH_SRS.'logrocket_identify',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function stripStaticContentVersion()
    {
        return $this->scopeConfig->isSetFlag(
            static::XML_PATH_SRS_ISSUE_GROUPING.'strip_static_content_version',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function stripStoreCode()
    {
        return $this->scopeConfig->isSetFlag(
            static::XML_PATH_SRS_ISSUE_GROUPING.'strip_store_code',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int
     */
    public function getErrorExceptionReporting()
    {
        return (int) ($this->collectModuleConfig()['errorexception_reporting'] ?? E_ALL);
    }

    /**
     * @return int
     */
    public function getIgnoreExceptions()
    {
        $config = $this->collectModuleConfig();

        if (is_array($config['ignore_exceptions'])) {
            return $config['ignore_exceptions'];
        }

        try {
            return $this->serializer->unserialize($config['ignore_exceptions']);
        } catch (InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * @param \Throwable $ex
     *
     * @return bool
     */
    public function shouldCaptureException(\Throwable $ex)
    {
        if ($ex instanceof \ErrorException && !($ex->getSeverity() & $this->getErrorExceptionReporting())) {
            return false;
        }

        if (in_array(get_class($ex), $this->getIgnoreExceptions())) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getStoreId():int
    {
        if (!$this->getStore()) {
            return 0;
        }

        return $this->getStore()->getId() ?? 0;
    }
}
