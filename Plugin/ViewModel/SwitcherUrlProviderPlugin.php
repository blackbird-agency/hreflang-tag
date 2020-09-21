<?php

namespace Blackbird\HrefLang\Plugin\ViewModel;

use Blackbird\HrefLang\Helper\Alternate;
use Magento\Framework\App\Config\ScopeConfigInterface;

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
        $replace = $this->scopeConfig->getValue('hreflang/general/replace_langswitcher');

        if ($replace) {
            $links = $this->alternateHelper->getAlternateLinks();
            if (isset($links['storeCodeToUrl'])
                && count($links['storeCodeToUrl']) > 0
                && isset(
                    $links['storeCodeToUrl'][$this->scopeConfig->getValue(
                        'general/locale/code',
                        'store',
                        $store->getId()
                    )])
                && $links['storeCodeToUrl'][$this->scopeConfig->getValue(
                    'general/locale/code',
                    'store',
                    $store->getId()
                )]) {
                return $links['storeCodeToUrl'][$this->scopeConfig->getValue(
                    'general/locale/code',
                    'store',
                    $store->getId()
                )];
            }
        }

        return $proceed($store);
    }
}
