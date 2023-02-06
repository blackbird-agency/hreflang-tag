<?php

namespace Blackbird\HrefLang\Model\Provider\Catalog;

use Blackbird\HrefLang\Api\ProviderInterface;
use Blackbird\HrefLang\Model\Provider\AbstractProvider;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;

class Category extends AbstractProvider implements ProviderInterface
{

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var UrlRewriteCollectionFactory
     */
    protected $urlRewriteCollectionFactory;

    /**
     * @var Emulation
     */
    protected $emulation;

    public function __construct(
        UrlRewriteCollectionFactory $urlRewriteCollectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        Emulation $emulation,
        ScopeConfigInterface $scopeConfig,
        LayoutInterface $layout,
        RequestInterface $request
    )
    {
        $this->categoryRepository          = $categoryRepository;
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;
        $this->emulation                   = $emulation;

        parent::__construct($scopeConfig, $layout, $request);
    }


    public function getAlternativeUrlForStore(StoreInterface $store): ?string
        {
            if ($_category = $this->getCurrentCategory()) {
            return $this->getCategoryUrl(
                $_category,
                $store);
        }

            return null;
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

        //check if category is visible in corresponding store
        try {
            $categoryInStore = $this->categoryRepository->get(
                $_category->getId(),
                $store->getId());
            $active          = $categoryInStore->getIsActive();
            if (!$active) {
                return $url;
            }

            $urlRewriteCollection = $this->urlRewriteCollectionFactory->create();
            $urlRewriteCollection->addStoreFilter($store);
            $urlRewriteCollection->addFieldToFilter(
                'entity_id',
                $_category->getId());
            $urlRewriteCollection->addFieldToFilter(
                'entity_type',
                'category');
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

        } catch (NoSuchEntityException $e) {
            //silence is golden
        }

        return $url;
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
        if ($categoryId && in_array(
                'catalog_category_view',
                $this->layout->getUpdate()->getHandles())) {
            try {
                $category = $this->categoryRepository->get($categoryId);
            } catch (NoSuchEntityException $e) {
                //silence is golden
            }
        }

        return $category;
    }
}
