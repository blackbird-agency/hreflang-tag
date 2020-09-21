<?php

namespace Blackbird\HrefLang\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Catalog\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\Deployed\Options;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
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
     * @var Session
     */
    protected $catalogSession;

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
     * @var Registry
     */
    protected $registry;

    /**
     * @var Url
     */
    protected $catalogUrl;

    /**
     * @var UrlRewriteCollectionFactory
     */
    protected $urlRewriteCollectionFactory;

    /**
     * Alternate constructor.
     *
     * @param StoreManagerInterface       $storeManager
     * @param Resolver                    $localeResolver
     * @param Options                     $localeOptions
     * @param ScopeConfigInterface        $scopeConfig
     * @param Session                     $catalogSession
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface  $productRepository
     * @param LayoutInterface             $layout
     * @param Registry                    $registry
     * @param Url                         $catalogUrl
     * @param UrlRewriteCollectionFactory $urlRewriteCollectionFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Resolver $localeResolver,
        Options $localeOptions,
        ScopeConfigInterface $scopeConfig,
        Session $catalogSession,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository,
        LayoutInterface $layout,
        Registry $registry,
        Url $catalogUrl,
        UrlRewriteCollectionFactory $urlRewriteCollectionFactory
    ) {
        $this->storeManager                = $storeManager;
        $this->localeResolver              = $localeResolver;
        $this->localeOptions               = $localeOptions;
        $this->scopeConfig                 = $scopeConfig;
        $this->catalogSession              = $catalogSession;
        $this->categoryRepository          = $categoryRepository;
        $this->productRepository           = $productRepository;
        $this->layout                      = $layout;
        $this->registry                    = $registry;
        $this->catalogUrl                  = $catalogUrl;
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;
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
                if ($store->getWebsiteId() === $currentStore->getWebsiteId()) {
                    $localeForStore = $this->scopeConfig->getValue('general/locale/code', 'store', $store->getId());
                    $otherCodes[]   = $localeForStore;

                    // we are on product page
                    if ($_product = $this->getCurrentProduct()) {
                        $storeCodeToUrl[$localeForStore] = $this->getProductUrl($_product, $store);
                    } //we are on category page
                    elseif ($_category = $this->getCurrentCategory()) {
                        $storeCodeToUrl[$localeForStore] = $this->getCategoryUrl($_category, $store);
                    } //we are on home page
                    elseif ($this->registry->registry('current_content') && $this->registry->registry(
                            'current_content')->getContentType()->getIdentifier() === 'homepage') {
                        $storeCodeToUrl[$localeForStore] = $this->scopeConfig->getValue(
                            'web/secure/base_url', 'store', $store->getId());
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
                        'general/locale/code', 'store', $currentStore->getId())]
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
        $categoryId = $this->catalogSession->getLastViewedCategoryId();

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
        $productId = $this->catalogSession->getLastViewedProductId();

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
        $url         = $this->scopeConfig->getValue(
                'web/secure/base_url', 'store', $store->getId()) . $url['url_rewrite'];

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
            $url = $urlRewrite['request_path'];
            $url = $this->scopeConfig->getValue('web/secure/base_url', 'store', $store->getId()) . $url;
        }

        return $url;
    }
}
