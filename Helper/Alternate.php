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
    const CONFIG_XML_PATH_HREFLANG_GENERAL_SAME_WEBSITE_ONLY = 'hreflang/general/same_website_only';
    const CONFIG_XML_PATH_HREFLANG_GENERAL_ENABLED_STORE = 'hreflang/general/enabled_store';
    const CONFIG_XML_PATH_WEB_SECURE_BASE_URL = 'web/secure/base_url';
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

            $otherCodes     = [];
            $storeCodeToUrl = [];
            $currentStore   = $this->storeManager->getStore();

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
                    self::CONFIG_XML_PATH_GENERAL_LOCALE_CODE,
                    ScopeInterface::SCOPE_STORE,
                    $store->getId());

                $otherCodes[] = $localeForStore;

                foreach ($this->hrefLangProviders->getSortedProviders() as $provider) {
                    $alternatedUrl = $provider->getAlternativeUrlForStore($store);
                    if (!is_null($alternatedUrl)) {
                        $storeCodeToUrl[$localeForStore] = $alternatedUrl;
                        break;
                    }
                }
                /*
                                elseif ($this->request->getFullActionName() == 'cms_index_index') {
                                    $storeCodeToUrl[$localeForStore] = $this->scopeConfig->getValue(
                                        self::CONFIG_XML_PATH_WEB_SECURE_BASE_URL,
                                        'store',
                                        $store->getId());
                                }*/
            }

            $allLanguages    = $this->localeOptions->getTranslatedOptionLocales();
            $currentLangCode = $this->scopeConfig->getValue(
                self::CONFIG_XML_PATH_GENERAL_LOCALE_CODE,
                ScopeInterface::SCOPE_STORE);

            $otherLangs = [];
            foreach ($allLanguages as $lang) {
                if ($lang['value'] == $currentLangCode) {
                    $currentLang = explode(
                        ' (',
                        $lang['label']);
                    $currentLang = $currentLang[0];
                }

                if (in_array(
                    $lang['value'],
                    $otherCodes)) {
                    if (strpos(
                            $lang['value'],
                            'US') !== false) {
                        $language = explode(
                            ' /',
                            $lang['label']);
                    } else {
                        $language = explode(
                            ' (',
                            $lang['label']);
                    }
                    $language = $language[0];

                    $otherLangs[] = ['label' => $language, 'code' => $lang['value']];
                }
            }

            if (count($storeCodeToUrl) > 0) {
                $this->_alternateLinks = [
                    'otherLangs'      => $otherLangs,
                    'currentLang'     => $currentLang,
                    'storeCodeToUrl'  => $storeCodeToUrl,
                    'currentStoreUrl' => $storeCodeToUrl[$this->scopeConfig->getValue(
                        self::CONFIG_XML_PATH_GENERAL_LOCALE_CODE,
                        'store',
                        $currentStore->getId())]
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
