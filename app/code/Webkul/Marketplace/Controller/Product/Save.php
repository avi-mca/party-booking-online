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

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;

/**
 * Webkul Marketplace Product Save Controller.
 */
class Save extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var SaveProduct
     */
    protected $_saveProduct;

    /**
     * @param Context          $context
     * @param Session          $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param SaveProduct      $saveProduct
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        SaveProduct $saveProduct
    ) {
        $this->_customerSession = $customerSession;
        $this->_formKeyValidator = $formKeyValidator;
        $this->_saveProduct = $saveProduct;
        parent::__construct(
            $context
        );
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
     * seller product save action.
     *
     * @return \Magento\Framework\Controller\Result\RedirectFactory
     */
    public function execute()
    {
        $helper = $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Data'
        );
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            try {
                $productId = '';
                if ($this->getRequest()->isPost()) {
                    if (!$this->_formKeyValidator->validate($this->getRequest())) {
                        return $this->resultRedirectFactory->create()->setPath(
                            '*/*/create',
                            ['_secure' => $this->getRequest()->isSecure()]
                        );
                    }

                    $wholedata = $this->getRequest()->getParams();

                    list($datacol, $errors) = $this->validatePost();

                    if (empty($errors)) {
                        $returnArr = $this->_saveProduct->saveProductData(
                            $this->_getSession()->getCustomerId(),
                            $wholedata
                        );
                        $productId = $returnArr['product_id'];
                    } else {
                        foreach ($errors as $message) {
                            $this->messageManager->addError($message);
                        }
                    }
                }
                if ($productId != '') {
                    if (empty($errors)) {
                        $this->messageManager->addSuccess(
                            __('Your product has been successfully saved')
                        );
                    }

                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        [
                            'id' => $productId, 
                            '_secure' => $this->getRequest()->isSecure()
                        ]
                    );
                } else {
                    if ($returnArr['error'] && $returnArr['message'] != '') {
                        $this->messageManager->addError($returnArr['message']);
                    }

                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/create',
                        ['_secure' => $this->getRequest()->isSecure()]
                    );
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/create',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/create',
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

    private function validatePost()
    {
        $errors = [];
        $data = [];
        foreach ($this->getRequest()->getParams() as $code => $value) {
            switch ($code) :
                case 'name':
                    if (trim($value) == '') {
                        $errors[] = __('Name has to be completed');
                    } else {
                        $data[$code] = $value;
                    }
                    break;
                case 'description':
                        if (trim($value) == '') {
                            $errors[] = __(
                                'Description has to be completed'
                            );
                        } else {
                            $data[$code] = $value;
                        }
                    break;
                case 'short_description':
                        if (trim($value) == '') {
                            $errors[] = __(
                                'Short description has to be completed'
                            );
                        } else {
                            $data[$code] = $value;
                        }
                    break;
                case 'price':
                        if (!preg_match('/^([0-9])+?[0-9.]*$/', $value)) {
                            $errors[] = __(
                                'Price should contain only decimal numbers'
                            );
                        } else {
                            $data[$code] = $value;
                        }
                    break;
                case 'weight':
                        if (!preg_match('/^([0-9])+?[0-9.]*$/', $value)) {
                            $errors[] = __(
                                'Weight should contain only decimal numbers'
                            );
                        } else {
                            $data[$code] = $value;
                        }
                    break;
                case 'stock':
                        if (!preg_match('/^([0-9])+?[0-9.]*$/', $value)) {
                            $errors[] = __(
                                'Product stock should contain only integers'
                            );
                        } else {
                            $data[$code] = $value;
                        }
                    break;
                case 'sku_type':
                        if (trim($value) == '') {
                            $errors[] = __('Sku Type has to be selected');
                        } else {
                            $data[$code] = $value;
                        }
                    break;
                case 'price_type':
                        if (trim($value) == '') {
                            $errors[] = __('Price Type has to be selected');
                        } else {
                            $data[$code] = $value;
                        }
                    break;
                case 'weight_type':
                        if (trim($value) == '') {
                            $errors[] = __('Weight Type has to be selected');
                        } else {
                            $data[$code] = $value;
                        }
                    break;
                case 'bundle_options':
                    if (trim($value) == '') {
                        $errors[] = __('Default Title has to be completed');
                    } else {
                        $data[$code] = $value;
                    }
                    break;
            endswitch;
        }

        return array($data, $errors);
    }
}
