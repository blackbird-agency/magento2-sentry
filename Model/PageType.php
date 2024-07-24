<?php

namespace JustBetter\Sentry\Model;

use JustBetter\Sentry\Helper\Data;
use Magento\Framework\App\Request\Http;

class PageType
{
    public const NAME = 'name';
    public const CONTROLLER_FULL_NAMES = 'controller_full_names';
    public const URLS = 'urls';
    public const DEFAULT_TYPE = 'default';

    /**
     * @param Http $httpRequest
     * @param Data $dataHelper
     */
    public function __construct(
        protected readonly Http $httpRequest,
        protected readonly Data $dataHelper
    )
    {
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool{
        return $this->dataHelper->isPageTypeTagEnabled();
    }

    /**
     * @return string
     */
    public function getPageType():string{
        $actionName = $this->httpRequest->getFullActionName();
        $urlString = $this->httpRequest->getRequestUri();
        foreach($this->dataHelper->getPageTypeMapping() as $type){
            $result = $this->getTypeFromUrls($type, $urlString);
            if(isset($result)){
                return $result;
            }
            $result = $this->getTypeFromActionName($type, $actionName);
            if(isset($result)){
                return $result;
            }
        };
        return self::DEFAULT_TYPE;
    }

    /**
     * @param array $type
     * @param string $urlString
     * @return string|null
     */
    protected function getTypeFromUrls(array $type, string $urlString): ?string{
        $urls = $type[static::URLS] ?? "";
        $urlsArray = explode('\n', $urls);
        foreach($urlsArray as $url){
            if(preg_match("#^" . $url . "$#",$urlString)){
                return $type[static::NAME] ?? self::DEFAULT_TYPE;
            }
        }
        return null;
    }

    /**
     * @param array $type
     * @param string $actionName
     * @return string|null
     */
    protected function getTypeFromActionName(array $type, string $actionName): ?string
    {
        $controllers = $type[static::CONTROLLER_FULL_NAMES] ?? "";
        $controllerArray = explode('\n', $controllers ?? "");
        foreach($controllerArray as $controller){
            if(preg_match("#^" . $controller . "$#",$actionName)){
                return $type[static::NAME] ?? self::DEFAULT_TYPE;
            }
        }

        return null;
    }
}


