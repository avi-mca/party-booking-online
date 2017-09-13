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
namespace Webkul\Marketplace\Controller\Seller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
/**
 * Webkul Marketplace Seller Usernameverify controller.
 */
class Usernameverify extends Action
{
    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        Context $context
    ) 
    {
        parent::__construct($context);
    }

    /**
     * Verify seller shop URL exists or not action.
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )->getCollection()
        ->addFieldToFilter(
            'shop_url', 
            $params['profileurl']
        );
        $this->getResponse()->representJson(
            $this->_objectManager->get(
                'Magento\Framework\Json\Helper\Data'
            )->jsonEncode($model->getSize())
        );
    }
}
