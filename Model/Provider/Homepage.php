<?php

namespace Blackbird\HrefLang\Model\Provider;

use Blackbird\HrefLang\Api\ProviderInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

class Homepage extends AbstractProvider implements ProviderInterface
{
    const CONFIG_XML_PATH_WEB_SECURE_BASE_URL = 'web/secure/base_url';

    public function getAlternativeUrlForStore(StoreInterface $store): ?string
    {
        //Check is PHP HTTP request
        if (!$this->request instanceof \Magento\Framework\App\Request\Http) {
            return null;
        }

        //Check is homepage URL
        if ($this->request->getPathInfo() !== '/') {
            return null;
        }

        return $this->scopeConfig->getValue(self::CONFIG_XML_PATH_WEB_SECURE_BASE_URL, ScopeInterface::SCOPE_STORE, $store->getId());
    }

}
