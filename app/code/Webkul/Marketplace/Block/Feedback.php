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
 * Webkul Marketplace Seller Feedback Block
 */
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;

class Feedback extends \Magento\Framework\View\Element\Template
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

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
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param Customer $customer
     * @param array $data
    */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Customer $customer,
        \Magento\Customer\Model\Session $session,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
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
        return parent::_prepareLayout();
    }

    /**
     * @return array
     */
    public function getProfileDetail($value='')
    {
        $shop_url = $this->_objectManager->create('Webkul\Marketplace\Helper\Data')->getFeedbackUrl();
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
}
