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

namespace Webkul\Marketplace\Controller\Product;

use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory as SellerProduct;

/**
 * Webkul Marketplace Product Delete controller.
 */
class Delete extends Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var SellerProduct
     */
    protected $_sellerProductCollectionFactory;

    /**
     * @param Context               $context
     * @param Session               $customerSession
     * @param Registry              $coreRegistry
     * @param CollectionFactory     $productCollectionFactory
     * @param SellerProduct         $sellerProductCollectionFactory
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        Registry $coreRegistry,
        CollectionFactory $productCollectionFactory,
        SellerProduct $sellerProductCollectionFactory
    ) {
        $this->_customerSession = $customerSession;
        $this->_coreRegistry = $coreRegistry;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_sellerProductCollectionFactory = $sellerProductCollectionFactory;
        parent::__construct(
            $context
        );
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
     * Retrieve customer session object.
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_customerSession;
    }

    /**
     * Delete seller products action.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {        
        $helper = $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Data'
        );
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            try {
                $wholedata = $this->getRequest()->getParams();

                $this->_eventManager->dispatch(
                    'mp_delete_product',
                    [$wholedata]
                );

                $sellerId = $this->_getSession()->getCustomerId();
                $deleteFlag = 0;
                //set secure area
                $this->_coreRegistry->register('isSecureArea', 1);
                $deletedProductId = '';
                $sellerProducts = $this->_sellerProductCollectionFactory
                ->create()
                ->addFieldToFilter(
                    'mageproduct_id', 
                    $wholedata['id']
                )->addFieldToFilter(
                    'seller_id', $sellerId
                );
                foreach ($sellerProducts as $sellerProduct) {
                    $deletedProductId = $sellerProduct['mageproduct_id'];
                    $sellerProduct->delete();
                }

                $mageProducts = $this->_productCollectionFactory
                ->create()
                ->addFieldToFilter(
                    'entity_id', 
                    $deletedProductId
                );
                foreach ($mageProducts as $mageProduct) {
                    $mageProduct->delete();
                    $deleteFlag = 1;
                }
                //unset secure area
                $this->_coreRegistry->unregister('isSecureArea');
                if ($deleteFlag) {
                    $this->messageManager->addSuccess(
                        __('Product has been successfully deleted from your account.')
                    );
                } else {
                    $this->messageManager->addError(
                        __('You are not authorize to delete this product.')
                    );
                }

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/productlist',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/productlist',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
