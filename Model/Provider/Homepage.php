<?php

namespace Blackbird\HrefLang\Model\Provider;

use Blackbird\HrefLang\Api\ProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;

class Homepage extends AbstractProvider implements ProviderInterface
{

    public function __construct(ScopeConfigInterface $scopeConfig, LayoutInterface $layout, RequestInterface $request)
    {
        parent::__construct(
            $scopeConfig,
            $layout,
            $request);
    }

    public function getAlternativeUrlForStore(StoreInterface $store): ?string
    {
        return rand();
    }

}
