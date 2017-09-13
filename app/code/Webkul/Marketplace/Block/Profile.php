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
 * Webkul Marketplace Seller Collection Block
 */
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Block\Product\AbstractProduct;

class Profile extends AbstractProduct
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\Data\Helper\PostHelper
     */
    protected $_postDataHelper;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $urlHelper;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $session;

    /**
     * @param Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param Customer $customer
     * @param array $data
    */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Customer $customer,
        \Magento\Customer\Model\Session $session,
        array $data = []
    ) {
        $this->_postDataHelper = $postDataHelper;
        $this->_objectManager = $objectManager;
        $this->urlHelper = $urlHelper;
        $this->Customer = $customer;
        $this->Session = $session;
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

    /**
     * @return array
     */
    public function getProfileDetail($value='')
    {
        $shop_url = $this->_objectManager->create('Webkul\Marketplace\Helper\Data')->getProfileUrl();
        if(!$shop_url){
            $shop_url = $this->getRequest()->getParam('shop');            
        }
        if($shop_url){
            $data=$this->_objectManager->create('Webkul\Marketplace\Model\Seller')
                    ->getCollection()
                    ->addFieldToFilter('shop_url',array('eq'=>$shop_url));
            foreach($data as $seller){ return $seller;}
        }
    } 
    
    public function getFeed()
    {
        $partner = $this->getProfileDetail();
        if(count($partner)){
            return $this->_objectManager->create('Webkul\Marketplace\Helper\Data')->getFeedTotal($partner->getSellerId());
        }else{
            return array();
        }
    }

    public function getFeedCollection()
    {
        $collection = array();
        $partner = $this->getProfileDetail();
        if(count($partner)){
            $collection = $this->_objectManager->create('Webkul\Marketplace\Model\Feedback')
                        ->getCollection()
                        ->addFieldToFilter('status',array('neq'=>0))
                        ->addFieldToFilter('seller_id',array('eq'=>$partner->getSellerId()))
                        ->setOrder('entity_id','DESC')
                        ->setPageSize(2)
                        ->setCurPage(1);
        }
        return $collection;
    }
    
    public function getBestsellProducts()
    {      
        $products=array();
        $partner = $this->getProfileDetail();
        if(count($partner)){
            $querydata = $this->_objectManager->create('Webkul\Marketplace\Model\Product')
                                ->getCollection()
                                ->addFieldToFilter(
                                    'seller_id',
                                    ['eq' => $partner->getSellerId()]
                                )
                                ->addFieldToFilter(
                                    'status',
                                    ['neq' =>2]
                                )
                                ->addFieldToSelect('mageproduct_id')
                                ->setOrder('mageproduct_id');
            $products = $this->_objectManager->create('Magento\Catalog\Model\Product')->getCollection();
            $products->addAttributeToSelect('*');
            
            $products->addAttributeToFilter('entity_id', array('in' => $querydata->getData()));
            $products->addAttributeToFilter('visibility', array('in' => array(4) ));
            $products->setPageSize(6)->setCurPage(1)->setOrder('entity_id');
        }
        return $products;
    }
}
