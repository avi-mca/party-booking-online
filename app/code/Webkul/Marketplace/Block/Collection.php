<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2016 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\Marketplace\Block;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;

/**
 * Seller Product's Collection Block.
 */
class Collection extends \Magento\Catalog\Block\Product\ListProduct
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;

    /** 
     * @var \Magento\Catalog\Model\Product
     */
    protected $_productlists;

    /** 
     * @var CategoryRepositoryInterface
     */
    protected $_categoryRepository;

    /**
     * @param \Magento\Catalog\Block\Product\Context    $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Framework\Url\Helper\Data        $urlHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param CollectionFactory                         $productCollectionFactory
     * @param \Magento\Catalog\Model\Layer\Resolver     $layerResolver
     * @param CategoryRepositoryInterface               $categoryRepository
     * @param array                                     $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_categoryRepository = $categoryRepository;
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );
    }

    /**
     */
    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * @return bool|\Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function _getProductCollection()
    {
        if (!$this->_productlists) {
            $paramData = $this->getRequest()->getParams();
            $partner = $this->getProfileDetail();
            $productname = $this->getRequest()->getParam('name');
            $querydata = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Product'
            )->getCollection()
            ->addFieldToFilter(
                'seller_id',
                ['eq' => $partner->getSellerId()]
            )
            ->addFieldToFilter(
                'status',
                ['eq' => 1]
            )
            ->addFieldToSelect('mageproduct_id')
            ->setOrder('mageproduct_id');

            $layer = $this->getLayer();

            $origCategory = null;            
            if (isset($paramData['c'])) {
                try {
                    $category = $this->_categoryRepository->get($paramData['c']);
                } catch (\Exception $e) {
                    $category = null;
                }

                if ($category) {
                    $origCategory = $layer->getCurrentCategory();
                    $layer->setCurrentCategory($category);
                }
            }
            $collection = $layer->getProductCollection();
            $collection->addAttributeToFilter(
                'entity_id',
                ['in' => $querydata->getData()]
            );

            $this->_productlists = $collection;

            if ($origCategory) {
                $layer->setCurrentCategory($origCategory);
            }
        }
        $this->_productlists->getSize();

        return $this->_productlists;
    }

    public function getDefaultDirection()
    {
        return 'asc';
    }

    public function getSortBy()
    {
        return 'entity_id';
    }

    /**
     * @return array
     */
    public function getProfileDetail($value = '')
    {
        $shopUrl = $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Data'
        )->getCollectionUrl();
        if (!$shopUrl) {
            $shopUrl = $this->getRequest()->getParam('shop');
        }
        if ($shopUrl) {
            $data = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Seller'
            )
            ->getCollection()
            ->addFieldToFilter('shop_url', $shopUrl);
            foreach ($data as $seller) {
                return $seller;
            }
        }
    }
}
