<?php

namespace Blackbird\HrefLang\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Locale\Deployed\Options;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;

class Alternate
{
    /**
     * @var null
     */
    protected $_alternateLinks = null;

    /**
     * @var Resolver
     */
    protected $localeResolver;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Options
     */
    protected $localeOptions;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @var Url
     */
    protected $catalogUrl;

    /**
     * @var UrlRewriteCollectionFactory
     */
    protected $urlRewriteCollectionFactory;

    /**
     * @var Emulation
     */
    protected $emulation;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Alternate constructor.
     *
     * @param StoreManagerInterface       $storeManager
     * @param Resolver                    $localeResolver
     * @param Options                     $localeOptions
     * @param ScopeConfigInterface        $scopeConfig
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface  $productRepository
     * @param LayoutInterface             $layout
     * @param Url                         $catalogUrl
     * @param UrlRewriteCollectionFactory $urlRewriteCollectionFactory
     * @param Emulation                   $emulation
     * @param RequestInterface            $request
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Resolver $localeResolver,
        Options $localeOptions,
        ScopeConfigInterface $scopeConfig,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository,
        LayoutInterface $layout,
        Url $catalogUrl,
        UrlRewriteCollectionFactory $urlRewriteCollectionFactory,
        Emulation $emulation,
        RequestInterface $request
    ) {
        $this->storeManager                = $storeManager;
        $this->localeResolver              = $localeResolver;
        $this->localeOptions               = $localeOptions;
        $this->scopeConfig                 = $scopeConfig;
        $this->categoryRepository          = $categoryRepository;
        $this->productRepository           = $productRepository;
        $this->layout                      = $layout;
        $this->catalogUrl                  = $catalogUrl;
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;
        $this->emulation                   = $emulation;
        $this->request                     = $request;
    }

    /**
     * Return alternate links for language
     */
    public function getAlternateLinks()
    {
        if (!$this->_alternateLinks) {
            $allLanguages    = $this->localeOptions->getTranslatedOptionLocales();
            $currentLangCode = $this->scopeConfig->getValue('general/locale/code', 'store');
            $otherCodes      = [];
            $storeCodeToUrl  = [];
            $currentStore    = $this->storeManager->getStore();

            foreach ($this->storeManager->getStores() as $store) {
                if (!$this->scopeConfig->getValue(
                        'same_website_only',
                        'store'
                    ) || $store->getWebsiteId() === $currentStore->getWebsiteId()) {
                    $localeForStore = $this->scopeConfig->getValue('general/locale/code', 'store', $store->getId());
                    $otherCodes[]   = $localeForStore;

                    // we are on product page
                    if ($_product = $this->getCurrentProduct()) {
                        $storeCodeToUrl[$localeForStore] = $this->getProductUrl($_product, $store);
                    } //we are on category page
                    elseif ($_category = $this->getCurrentCategory()) {
                        $storeCodeToUrl[$localeForStore] = $this->getCategoryUrl($_category, $store);
                    }
                }
            }

            $otherLangs = [];
            foreach ($allLanguages as $lang) {
                if ($lang['value'] == $currentLangCode) {
                    $currentLang = explode(' (', $lang['label']);
                    $currentLang = $currentLang[0];
                }

                if (in_array($lang['value'], $otherCodes)) {
                    if (strpos($lang['value'], 'US') !== false) {
                        $language = explode(' /', $lang['label']);
                    } else {
                        $language = explode(' (', $lang['label']);
                    }
                    $language = $language[0];

                    //todo : find a better way to get language name in corresponding locale
                    $otherLangs[] = ['label' => $language, 'code' => $lang['value']];
                }
            }

            if (count($storeCodeToUrl) > 0) {
                $this->_alternateLinks = [
                    'otherLangs'      => $otherLangs,
                    'currentLang'     => $currentLang,
                    'storeCodeToUrl'  => $storeCodeToUrl,
                    'currentStoreUrl' => $storeCodeToUrl[$this->scopeConfig->getValue(
                        'general/locale/code',
                        'store',
                        $currentStore->getId()
                    )]
                ];
            }
        }

        return $this->_alternateLinks;
    }

    /**
     * @return \Magento\Catalog\Api\Data\CategoryInterface|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCurrentCategory()
    {
        $category   = null;
        $categoryId = $this->request->getParam('id');

        //check if we are on product page
        if ($categoryId && in_array('catalog_category_view', $this->layout->getUpdate()->getHandles())) {
            $category = $this->categoryRepository->get($categoryId);
        }

        return $category;
    }

    /**
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCurrentProduct()
    {
        $product   = null;
        $productId = $this->request->getParam('id');

        //check if we are on product page
        if ($productId && in_array('catalog_product_view', $this->layout->getUpdate()->getHandles())) {
            $product = $this->productRepository->getById($productId);
        }

        return $product;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $_product
     * @param                                            $store
     *
     * @return string
     */
    protected function getProductUrl(\Magento\Catalog\Api\Data\ProductInterface $_product, $store)
    {
        $productsUrl = $this->catalogUrl->getRewriteByProductStore([$_product->getId() => $store->getId()]);
        $url         = $productsUrl[$_product->getId()];

        if ($this->getRemoveStoreTag()) {
            $this->emulation->startEnvironmentEmulation($store->getId());
            $url = $store->getUrl('/') . $url['url_rewrite'];
            $this->emulation->stopEnvironmentEmulation($store->getId());
        } else {
            $url = $store->getUrl($url['url_rewrite']);
        }

        return $url;
    }

    /**
     * @param \Magento\Catalog\Api\Data\CategoryInterface $_category
     * @param                                             $store
     *
     * @return string
     */
    protected function getCategoryUrl(\Magento\Catalog\Api\Data\CategoryInterface $_category, $store)
    {
        $url = '';

        $urlRewriteCollection = $this->urlRewriteCollectionFactory->create();
        $urlRewriteCollection->addStoreFilter($store);
        $urlRewriteCollection->addFieldToFilter('entity_id', $_category->getId());
        $urlRewriteCollection->addFieldToFilter('entity_type', 'category');
        $urlRewriteCollection->addFieldToSelect(['request_path']);

        $urlRewrite = $urlRewriteCollection->getFirstItem();

        if ($urlRewrite && $urlRewrite->getRequestPath()) {
            if ($this->getRemoveStoreTag()) {
                $this->emulation->startEnvironmentEmulation($store->getId());
                $url = $store->getUrl('/') . $urlRewrite['request_path'];
                $this->emulation->stopEnvironmentEmulation($store->getId());
            } else {
                $url = $store->getUrl($urlRewrite['request_path']);
            }
        }

        return $url;
    }

    /**
     * Return x-default locale code
     * @return mixed
     */
    public function getXDefault(): string
    {
        return $this->scopeConfig->getValue('hreflang/general/default_locale', 'store');
    }

    /**
     * Return x-default locale code
     * @return mixed
     */
    protected function getRemoveStoreTag(): string
    {
        return $this->scopeConfig->getValue('hreflang/general/remove_store_param', 'store');
    }
}
