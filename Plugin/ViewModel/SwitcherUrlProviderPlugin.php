<?php

namespace Blackbird\HrefLang\Plugin\ViewModel;

use Blackbird\HrefLang\Helper\Alternate;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class SwitcherUrlProviderPlugin
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Alternate
     */
    protected $alternateHelper;

    /**
     * SwitcherUrlProviderPlugin constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Alternate            $alternateHelper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Alternate $alternateHelper
    ) {
        $this->alternateHelper = $alternateHelper;
        $this->scopeConfig     = $scopeConfig;
    }

    /**
     * @param \Magento\Store\ViewModel\SwitcherUrlProvider $subject
     * @param callable                                     $proceed
     * @param \Magento\Store\Model\Store                   $store
     */
    public function aroundGetTargetStoreRedirectUrl(\Magento\Store\ViewModel\SwitcherUrlProvider $subject, callable $proceed, \Magento\Store\Model\Store $store)
    {
        $replace = $this->scopeConfig->getValue('hreflang/general/replace_langswitcher', ScopeInterface::SCOPE_STORE);

        if ($replace) {
            $links = $this->alternateHelper->getAlternateLinks();

            //Get $store language code iso (fr_Fr, en_US, ...)
            $localeForStore = $this->scopeConfig->getValue(
                Alternate::CONFIG_XML_PATH_HREFLANG_GENERAL_LOCALE_FOR_HREFLANG_TAG,
                ScopeInterface::SCOPE_STORE,
                $store->getId());

            if(!$localeForStore)
            {
                $localeForStore = $this->scopeConfig->getValue(
                    Alternate::CONFIG_XML_PATH_GENERAL_LOCALE_CODE,
                    ScopeInterface::SCOPE_STORE,
                    $store->getId());
            }

            if (isset($links['storeCodeToUrl'])
                && count($links['storeCodeToUrl']) > 0
                && isset($links['storeCodeToUrl'][$localeForStore])
                && $links['storeCodeToUrl'][$localeForStore]) {
                return $links['storeCodeToUrl'][$localeForStore];
            }
        }

        return $proceed($store);
    }
}
