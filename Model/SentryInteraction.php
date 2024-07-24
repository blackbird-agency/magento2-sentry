<?php

declare(strict_types=1);

namespace JustBetter\Sentry\Model;

// phpcs:disable Magento2.Functions.DiscouragedFunction

use function Sentry\captureException;
use function Sentry\init;

class SentryInteraction
{

    public function __construct(
        protected PageType $pageType
    )
    {
    }

    public function initialize($config)
    {
        init($config);
        if($this->pageType->isEnabled()) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
                $scope->setTag('page_type', $this->pageType->getPageType());
            });
        }
    }

    public function captureException(\Throwable $ex)
    {
        ob_start();
        captureException($ex);
        ob_end_clean();
    }
}
