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
namespace Webkul\Marketplace\Controller\Account;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Session;

/**
 * Webkul Marketplace Account DeleteSellerLogo Controller.
 */
class DeleteSellerLogo extends Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @param Context     $context
     * @param Session     $customerSession
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory
    ) 
    {
        $this->_customerSession = $customerSession;
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Retrieve customer session object.
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_customerSession;
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->_objectManager->get(
            'Magento\Customer\Model\Url'
        )->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * DeleteSellerLogo action.
     *
     * @return \Magento\Framework\Controller\Result\RedirectFactory
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        try {
            $autoId = '';
            $sellerId = $this->_getSession()->getCustomerId();
            $collection = $this->_objectManager
            ->create('Webkul\Marketplace\Model\Seller')
            ->getCollection()
            ->addFieldToFilter(
                'seller_id', 
                $sellerId
            );
            foreach ($collection as  $value) {
                $autoId = $value->getId();
            }
            if ($autoId != '') {
                $value = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Seller'
                )->load($autoId);
                $value->setLogoPic('');
                $value->save();
            }
            $this->getResponse()->representJson(
                $this->_objectManager->get('Magento\Framework\Json\Helper\Data')
                ->jsonEncode(true)
            );
        } catch (\Exception $e) {
            $this->getResponse()->representJson(
                $this->_objectManager->get('Magento\Framework\Json\Helper\Data')
                ->jsonEncode($e->getMessage())
            );
        }
    }
}
