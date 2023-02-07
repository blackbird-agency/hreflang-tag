<?php

namespace Blackbird\HrefLang\Helper;

use Blackbird\HrefLang\Api\HrefLangProvidersInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Locale\Deployed\Options;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Alternate extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_XML_PATH_GENERAL_LOCALE_CODE = 'general/locale/code';
    const CONFIG_XML_PATH_HREFLANG_GENERAL_LOCALE_FOR_HREFLANG_TAG = 'hreflang/general/locale_for_hreflang_tag';
    const CONFIG_XML_PATH_HREFLANG_GENERAL_SAME_WEBSITE_ONLY = 'hreflang/general/same_website_only';
    const CONFIG_XML_PATH_HREFLANG_GENERAL_ENABLED_STORE = 'hreflang/general/enabled_store';
    const CONFIG_XML_PATH_HREFLANG_GENERAL_DEFAULT_LOCALE = 'hreflang/general/default_locale';
    /**
     * @var null
     */
    protected $_alternateLinks = null;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Options
     */
    protected $localeOptions;

    /**
     * @var HrefLangProvidersInterface
     */
    protected $hrefLangProviders;

    public function __construct(
        HrefLangProvidersInterface $hrefLangProviders,
        StoreManagerInterface $storeManager,
        Options $localeOptions,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->hrefLangProviders = $hrefLangProviders;
        $this->storeManager      = $storeManager;
        $this->localeOptions     = $localeOptions;
        $this->scopeConfig       = $scopeConfig;
    }

    /**
     * Return alternate links for language
     */
    public function getAlternateLinks()
    {
        if (!$this->_alternateLinks) {

            $storeCodeToUrl = [];
            $currentStore   = $this->storeManager->getStore();
            $currentLocaleStoreCode = '';

            foreach ($this->storeManager->getStores() as $store) {

                //Check store root category is the same (necessary for catalog switch)
                if ($store->getRootCategoryId() !== $currentStore->getRootCategoryId()) {
                    continue;
                }

                //Check samewebsite store only
                if (
                    $this->scopeConfig->getValue(
                        self::CONFIG_XML_PATH_HREFLANG_GENERAL_SAME_WEBSITE_ONLY,
                        ScopeInterface::SCOPE_STORE)
                    && $store->getWebsiteId() !== $currentStore->getWebsiteId()
                ) {
                    continue;
                }

                //Check Href lang is enable for $store
                if (!$this->scopeConfig->getValue(
                    self::CONFIG_XML_PATH_HREFLANG_GENERAL_ENABLED_STORE,
                    ScopeInterface::SCOPE_STORE,
                    $store->getId())) {
                    continue;
                }

                //Get $store language code iso (fr_Fr, en_US, ...)
                $localeForStore = $this->scopeConfig->getValue(
                    self::CONFIG_XML_PATH_HREFLANG_GENERAL_LOCALE_FOR_HREFLANG_TAG,
                    ScopeInterface::SCOPE_STORE,
                    $store->getId());

                if(!$localeForStore)
                {
                    $localeForStore = $this->scopeConfig->getValue(
                        self::CONFIG_XML_PATH_GENERAL_LOCALE_CODE,
                        ScopeInterface::SCOPE_STORE,
                        $store->getId());
                }

                if($currentStore->getId() === $store->getId())
                {
                    $currentLocaleStoreCode = $localeForStore;
                }
                /**
                 *  Href Lang provider comes from  DI.xml, you can add  more if needed
                 */
                foreach ($this->hrefLangProviders->getSortedProviders() as $provider) {
                    $alternatedUrl = $provider->getAlternativeUrlForStore($store);
                    if (!is_null($alternatedUrl)) {
                        $storeCodeToUrl[$localeForStore] = $alternatedUrl;
                        break;
                    }
                }
            }

            if (count($storeCodeToUrl) > 0) {
                $this->_alternateLinks = [
                    'storeCodeToUrl'  => $storeCodeToUrl,
                    'currentStoreUrl' => $storeCodeToUrl[$currentLocaleStoreCode]
                ];
            }
        }

        return $this->_alternateLinks;
    }


    /**
     * Return x-default locale code
     * @return mixed
     */
    public function getXDefault(): string
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_HREFLANG_GENERAL_DEFAULT_LOCALE,
            ScopeInterface::SCOPE_STORE);
    }
}
