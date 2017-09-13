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
namespace Webkul\Marketplace\Block\Transaction;

class View extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @param \Magento\Framework\ObjectManagerInterface       $objectManager
     * @param \Magento\Customer\Model\Session                 $customerSession
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\Order                      $order
     * @param array                                           $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\Order $order,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        $this->_customerSession = $customerSession;
        $this->_order = $order;
        parent::__construct($context, $data);
    }

    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function sellertransactionOrderDetails($id)
    {
        return $this->_objectManager->create('Webkul\Marketplace\Model\Saleslist')->getCollection()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $this->_customerSession->getCustomerId()]
        )
        ->addFieldToFilter(
            'trans_id',
            ['eq' => $id]
        )
        ->addFieldToFilter(
            'order_id',
            ['neq' => 0]
        );
    }

    public function getFormatedPrice($price = 0)
    {
        return $this->_order->formatPrice($price);
    }    

    public function sellertransactionDetails()
    {
        $id = $this->getRequest()->getParam('id');
        return $this->_objectManager->create(
            'Webkul\Marketplace\Model\Sellertransaction'
        )->load($id);
    }
}
