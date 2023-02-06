<?php

namespace Blackbird\HrefLang\Model\Provider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\ScopeInterface;

abstract class AbstractProvider
{
    const CONFIG_XML_PATH_HREFLANG_GENERAL_REMOVE_STORE_PARAM = 'hreflang/general/remove_store_param';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @var RequestInterface
     */
    protected $request;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LayoutInterface $layout,
        RequestInterface $request
    )
    {
        $this->scopeConfig                 = $scopeConfig;
        $this->layout                      = $layout;
        $this->request           = $request;
    }

    /**
     * Return x-default locale code
     * @return mixed
     */
    protected function getRemoveStoreTag(): string
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_HREFLANG_GENERAL_REMOVE_STORE_PARAM,
            ScopeInterface::SCOPE_STORE);
    }
}
