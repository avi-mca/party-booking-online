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
namespace Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Framework\App\Config\ScopeConfigInterface;

class PaymentInfo extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

	 const COMM_TEMPLATE = 'customer/paymentinfo.phtml';

     /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
            \Magento\Framework\Registry $registry,
            \Magento\Backend\Block\Widget\Context $context,
            \Magento\Framework\ObjectManagerInterface $objectManager,
            array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_objectManager = $objectManager;
        parent::__construct($context, $data);
    }
     
	 /**
     * Set template to itself
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::COMM_TEMPLATE);
        }
        return $this;
    }

    public function getPaymentInfo(){
        $paymentInfo = $this->_objectManager->create('Webkul\Marketplace\Block\Adminhtml\Customer\Edit')->getPaymentMode();

        return $paymentInfo;
    }

}