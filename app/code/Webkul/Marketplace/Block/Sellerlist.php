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
/**
 * Webkul Marketplace Sellerlist Block
 */
class Sellerlist extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory
     */
    protected $_sellerlistCollectionFactory;

    /** @var \Webkul\Marketplace\Model\Seller */
    protected $sellerList;

    /**
    * @param Context $context
    * @param array $data
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
    * @param \Magento\Framework\ObjectManagerInterface $objectManager
    */
    public function __construct(
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Block\Product\Context $context,
        \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerlistCollectionFactory,
        array $data = []
    ) {
        $this->_sellerlistCollectionFactory = $sellerlistCollectionFactory;
        $this->_filterProvider = $filterProvider;
        $this->_objectManager = $objectManager;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * @return bool|\Magento\Ctalog\Model\ResourceModel\Product\Collection
     */
    public function getSellerCollection()
    {
        if (!$this->sellerList) {
            $seller_arr = array();

            $seller_product_coll = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Product'
            )
            ->getCollection()
            ->addFieldToFilter(
                'status',
                ['eq' => 1]
            )
            ->addFieldToSelect('seller_id')
            ->distinct(true);
            foreach ($seller_product_coll as $value) {
                array_push($seller_arr, $value['seller_id']);
            }

            $collection = $this->_sellerlistCollectionFactory
            ->create()
            ->addFieldToSelect(
                '*'
            )
            ->addFieldToFilter(
                'seller_id',
                ['in' => $seller_arr]
            )
            ->addFieldToFilter(
                'is_seller',
                ['eq' => 1]
            )
            ->setOrder(
                'entity_id',
                'desc'
            );
            if(isset($_GET['shop']) && $_GET['shop']){
                $collection->addFieldToFilter(
                    'shop_url',
                    ['like' => "%".$_GET['shop']."%"]
                );
            }
            $this->sellerList = $collection;
        }
        return $this->sellerList;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getSellerCollection()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'marketplace.seller.list.pager'
            )
            ->setAvailableLimit(array(4=>4,8=>8,16=>16))
            ->setCollection(
                $this->getSellerCollection()
            );
            $this->setChild('pager', $pager);
            $this->getSellerCollection()->load();
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * Prepare HTML content
     *
     * @return string
     */
    public function getCmsFilterContent($value='')
    {
        $html = $this->_filterProvider->getPageFilter()->filter($value);
        return $html;
    }
}

