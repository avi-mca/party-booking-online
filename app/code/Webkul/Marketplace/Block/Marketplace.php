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
 * Webkul Marketplace Landing Page Block
 */
use Magento\Catalog\Model\Product;
use Magento\Sales\Model\Order;
use Webkul\Marketplace\Model\Seller;
use Magento\Customer\Model\Customer;

class Marketplace extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    protected $seller;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $product;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * 
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    public $_productRepository;

    /**
    * @param Context $context
    * @param array $data
    * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
    * @param \Magento\Framework\ObjectManagerInterface $objectManager
    */
    public function __construct(
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Product $product,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        Order $order,
        Customer $customer,
        Seller $seller,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {
        $this->Product = $product;
        $this->Customer = $customer;
        $this->_productRepository = $productRepository;
        $this->Seller = $seller;
        $this->Order = $order;
        $this->_filterProvider = $filterProvider;
        $this->_objectManager = $objectManager;
        $this->imageHelper = $context->getImageHelper();
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
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
    }

    public function imageHelperObj() {
        return $this->imageHelper;
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

    public function getBestSaleSellers()
    {
        $marketplace_userdata = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Seller\Collection'
        )->getTable('marketplace_userdata');
        $catalog_product_entity_int = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Seller\Collection'
        )->getTable('catalog_product_entity_int');
        $marketplace_product = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Seller\Collection'
        )->getTable('marketplace_product');
        $eavAttribute = $this->_objectManager->get(
            'Magento\Eav\Model\ResourceModel\Entity\Attribute'
        );
        $pro_att_id = $eavAttribute->getIdByCode("catalog_product","visibility");

        $helper = $this->_objectManager->create('Webkul\Marketplace\Helper\Data');
        $sellers_order = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Orders'
        )
        ->getCollection()
        ->addFieldToSelect('seller_id');
        $prefix = '';

        if (count($helper->getAllStores())==1) {
            $storeId = 0;
        } else {
            $storeId = $helper->getCurrentStoreId();
        }
        $storeId = 0;

        $sellers_order->getSelect()
        ->join(
            ["ccp" => $marketplace_userdata],
            "ccp.seller_id = main_table.seller_id",
            ["is_seller" => "is_seller"]
        )->where(
            "main_table.invoice_id!=0 AND ccp.is_seller = 1"
        );

        $sellers_order ->getSelect()
                        ->columns('COUNT(*) as countOrder')
                        ->group('seller_id'); 
        $seller_arr = [];
        foreach ($sellers_order as $value) {
            if ($helper->getSellerProCount($value['seller_id'])) {
                $seller_arr[$value['seller_id']] = [];
                $seller_products = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Saleslist'
                )->getCollection()
                ->addFieldToSelect('mageproduct_id')
                ->addFieldToSelect('magequantity')
                ->addFieldToSelect('seller_id')
                ->addFieldToSelect('cpprostatus');                
                $seller_products->getSelect()
                ->join(
                    ["mpro" => $marketplace_product],
                    "mpro.mageproduct_id = main_table.mageproduct_id",
                    ["status" => "status"]
                )->where(
                    "main_table.seller_id=".$value['seller_id']." 
                    AND main_table.cpprostatus=1 
                    AND mpro.status = 1"
                );
                $seller_products->getSelect()
                ->columns('SUM(magequantity) as countOrderedProduct')
                ->group('mageproduct_id');
                $seller_products->setOrder('countOrderedProduct', 'DESC');

                $seller_products->getSelect()
                ->join(
                    ["cpei" => $catalog_product_entity_int],
                    "cpei.entity_id = main_table.mageproduct_id",
                    ["value" => "value"]
                )->where(
                    "cpei.value=4 
                    AND cpei.attribute_id = ".$pro_att_id." 
                    AND cpei.store_id = ".$storeId
                );

                $seller_products->getSelect()->limit(3);
                foreach ($seller_products as $seller_product) {
                    array_push(
                        $seller_arr[$value['seller_id']], 
                        $seller_product['mageproduct_id']
                    );
                }

                if (count($seller_arr[$value['seller_id']])<3) {
                    $seller_pro_count = count($seller_arr[$value['seller_id']]);
                    $seller_product_coll = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Product'
                    )->getCollection()
                    ->addFieldToFilter(
                        'seller_id',
                        ['eq' => $value['seller_id']]
                    )
                    ->addFieldToFilter(
                        'mageproduct_id',
                        ['nin' => $seller_arr[$value['seller_id']]]
                    )
                    ->addFieldToFilter(
                        'status',
                        ['eq' => 1]
                    );
                    $seller_product_coll->getSelect()
                    ->join(
                        ["cpei" => $catalog_product_entity_int],
                        "cpei.entity_id = main_table.mageproduct_id",
                        ["value" => "value"]
                    )->where(
                        "cpei.value=4 AND 
                        cpei.attribute_id = ".$pro_att_id." AND 
                        cpei.store_id = ".$storeId
                    );
                    $seller_products->getSelect()->limit(3);
                    foreach ($seller_product_coll as $value) {
                        if ($seller_pro_count<3) {
                            array_push(
                                $seller_arr[$value['seller_id']],
                                $value['mageproduct_id']
                            );
                            $seller_pro_count++;
                        }
                    }
                }
            }
        }
        if (count($seller_arr)!=4) {
            $i = count($seller_arr);
            $count_pro_arr = [];
            $seller_product_coll = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Product'
            )->getCollection();
            if (count($seller_arr)) {
                $seller_product_coll->getSelect()
                ->join(
                    array("mmu" => $marketplace_userdata),
                    "mmu.seller_id = main_table.seller_id",
                    array("is_seller" => "is_seller")
                )->where(
                    "main_table.status!=0 
                    AND mmu.is_seller = 1 
                    AND mmu.seller_id NOT IN (".implode(',', array_keys($seller_arr)).")"
                );
            } else {
                $seller_product_coll->getSelect()
                ->join(
                    array("mmu" => $marketplace_userdata),
                    "mmu.seller_id = main_table.seller_id",
                    array("is_seller" => "is_seller")
                )->where("main_table.status!=0 AND mmu.is_seller = 1");
            }

            $seller_product_coll->getSelect()
                             ->columns('COUNT(*) as countOrder')
                             ->group('seller_id'); 
            foreach ($seller_product_coll as $value) {
                if (!isset($count_pro_arr[$value['seller_id']])) {
                    $count_pro_arr[$value['seller_id']] = [];
                }
                $count_pro_arr[$value['seller_id']] = $value['countOrder'];
            }

            arsort($count_pro_arr);

            foreach ($count_pro_arr as $procount_seller_id=>$procount) { 
                if ($i<=4) {
                    if ($helper->getSellerProCount($procount_seller_id)) {
                        if (!isset($seller_arr[$procount_seller_id])) {
                            $seller_arr[$procount_seller_id] = [];
                        }
                        $seller_product_coll = $this->_objectManager->create(
                            'Webkul\Marketplace\Model\Product'
                        )
                        ->getCollection()
                        ->addFieldToFilter(
                            'seller_id',
                            ['eq' => $procount_seller_id]
                        )
                        ->addFieldToFilter(
                            'status',
                            ['eq' => 1]
                        );
                        $seller_product_coll->getSelect()
                        ->join(
                            array("cpei" => $catalog_product_entity_int),
                            "cpei.entity_id = main_table.mageproduct_id",
                            array("value" => "value")
                        )->where(
                            "cpei.value=4 AND 
                            cpei.attribute_id = ".$pro_att_id." AND 
                            cpei.store_id = ".$storeId
                        );
                        $seller_product_coll->getSelect()->limit(3);
                        foreach ($seller_product_coll as $value) {
                            array_push($seller_arr[$procount_seller_id],$value['mageproduct_id']);
                        }
                    }   
                }
                $i++;
            }
        }
        return $seller_arr;
    }
}