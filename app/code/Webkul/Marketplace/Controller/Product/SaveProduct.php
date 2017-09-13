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

use Magento\Framework\App\Filesystem\DirectoryList;
use Webkul\Marketplace\Helper\Data as MarketplaceHelperData;
use Webkul\Marketplace\Model\Product as SellerProduct;

/**
 * Webkul Marketplace SaveProduct controller.
 */
class SaveProduct
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_mediaDirectory;

    /**
     * @var Builder
     */
    protected $_marketplaceProductBuilder;

    /**
     * @var \Magento\Catalog\Model\Product\TypeTransitionManager
     */
    protected $_catalogProductTypeManager;

    /** 
    * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable 
    */
    protected $_catalogProductTypeConfigurable;

    /** 
    * @var \Magento\ConfigurableProduct\Model\Product\VariationHandler 
    */
    protected $_variationHandler;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface  */
    protected $_productRepositoryInterface;

    /**
     * @var eventManager
     */
    protected $_eventManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Catalog\Model\Product\Initialization\Helper\ProductLinks
     */
    protected $_productLinks;

    /**
     * @var \Magento\Backend\Helper\Js
     */
    protected $_jsHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
     */
    protected $_dateFilter;

    /**
     * @var MarketplaceHelperData
     */
    protected $_marketplaceHelperData;

    /**
     * @param \Magento\Framework\Event\Manager                                  $eventManager
     * @param \Magento\Framework\ObjectManagerInterface                         $objectManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                       $date
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date                    $dateFilter,
     * @param \Magento\Catalog\Model\Product\TypeTransitionManager              $catalogProductTypeManager
     * @param \Magento\ConfigurableProduct\Model\Product\VariationHandler       $variationHandler
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable      $catalogProductTypeConfigurable
     * @param \Magento\Catalog\Api\ProductRepositoryInterface                   $productRepositoryInterface
     * @param \Magento\Catalog\Model\Product\Initialization\Helper\ProductLinks $productLinks
     * @param Filesystem                                                        $filesystem
     * @param Builder                                                           $catalogProductBuilder
     * @param MarketplaceHelperData                                             $marketplaceHelperData
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Magento\Catalog\Model\Product\TypeTransitionManager $catalogProductTypeManager,
        \Magento\ConfigurableProduct\Model\Product\VariationHandler $variationHandler,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $catalogProductTypeConfigurable,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Magento\Catalog\Model\Product\Initialization\Helper\ProductLinks $productLinks,
        \Magento\Backend\Helper\Js $jsHelper,
        \Magento\Framework\Filesystem $filesystem,
        Builder $marketplaceProductBuilder,
        MarketplaceHelperData $marketplaceHelperData
    ) {
        $this->_eventManager = $eventManager;
        $this->_objectManager = $objectManager;
        $this->_date = $date;
        $this->_dateFilter = $dateFilter;
        $this->_catalogProductTypeManager = $catalogProductTypeManager;
        $this->_variationHandler = $variationHandler;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->_productRepositoryInterface = $productRepositoryInterface;
        $this->_productLinks = $productLinks;
        $this->_jsHelper = $jsHelper;
        $this->_marketplaceProductBuilder = $marketplaceProductBuilder;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_marketplaceHelperData = $marketplaceHelperData;
    }

    /**
     * saveProductData method for seller's product save action.
     *
     * @param $sellerId
     * @param $wholdata
     *
     * @return array
     */
    public function saveProductData($sellerId, $wholedata)
    {
        $returnArr = [];
        $returnArr['error'] = 0;
        $returnArr['product_id'] = '';
        $returnArr['message'] = '';
        $wholedata['new-variations-attribute-set-id'] = $wholedata['set'];

        $helper = $this->_marketplaceHelperData;

        $sellerId = $sellerId;

        if (!empty($wholedata['id'])) {
            $mageProductId = $wholedata['id'];
            $editFlag = 1;
            $storeId = $helper->getCurrentStoreId();
            $this->_eventManager->dispatch(
                'mp_customattribute_deletetierpricedata',
                [$wholedata]
            );
        } else {
            $mageProductId = '';
            $editFlag = 0;
            $storeId = 0;
            $wholedata['product']['website_ids'][] = $helper->getWebsiteId();
            $wholedata['product']['url_key'] = '';
        }

        if (isset($wholedata['status']) && $wholedata['status'] && $mageProductId) {
            $status = $wholedata['status'];
            if ($helper->getIsProductEditApproval()) {
                $status = $helper->getIsProductEditApproval() ? 
                SellerProduct::STATUS_DISABLED : SellerProduct::STATUS_ENABLED;
            }
        } else {
            $status = $helper->getIsProductApproval() ? 
            SellerProduct::STATUS_DISABLED : SellerProduct::STATUS_ENABLED;
        }
        $wholedata['status'] = $status;

        $wholedata['store'] = $storeId;
        /*
        * Marketplace Product save before Observer
        */
        $this->_eventManager->dispatch(
            'mp_product_save_before',
            [$wholedata]
        );

        $catalogProductTypeId = $wholedata['type'];

        /* 
        * Product Initialize method to set product data
        */
        $catalogProduct = $this->productInitialize(
            $this->_marketplaceProductBuilder->build($wholedata, $storeId),
            $wholedata
        );

        /*for downloadable products start*/

        if (!empty($wholedata['downloadable']) && $downloadable = $wholedata['downloadable']) {
            $catalogProduct->setDownloadableData($downloadable);
        }

        /*for downloadable products end*/

        /*for configurable products start*/

        $associatedProductIds = [];

        if (!empty($wholedata['attributes'])) {
            $attributes = $wholedata['attributes'];
            $setId = $wholedata['set'];
            $catalogProduct->setAttributeSetId($setId);
            $this->_catalogProductTypeConfigurable->setUsedProductAttributeIds(
                $attributes, $catalogProduct
            );

            $catalogProduct->setNewVariationsAttributeSetId($setId);
            $associatedProductIds = [];
            if (!empty($wholedata['associated_product_ids'])) {
                $associatedProductIds = $wholedata['associated_product_ids'];
            }
            if (!empty($wholedata['variations-matrix'])) {
                $generatedProductIds = $this->_variationHandler
                ->generateSimpleProducts(
                    $catalogProduct, $wholedata['variations-matrix']
                );
                $associatedProductIds = array_merge(
                    $associatedProductIds, 
                    $generatedProductIds
                );
            }
            $catalogProduct->setAssociatedProductIds(
                array_filter($associatedProductIds)
            );

            $catalogProduct->setCanSaveConfigurableAttributes(
                (bool) $wholedata['affect_configurable_product_attributes']
            );
        }

        /*for configurable products end*/

        $this->_catalogProductTypeManager->processProduct($catalogProduct);

        $set = $catalogProduct->getAttributeSetId();

        $type = $catalogProduct->getTypeId();

        if (isset($set) && isset($type)) {
            $allowedsets = explode(',', $helper->getAllowedAttributesetIds());
            $allowedtypes = explode(',', $helper->getAllowedProductType());
            if (!in_array($type, $allowedtypes) || !in_array($set, $allowedsets)) {
                $returnArr['error'] = 1;
                $returnArr['message'] = __('Product Type Invalid Or Not Allowed');

                return $returnArr;
            }
        } else {
            $returnArr['error'] = 1;
            $returnArr['message'] = __('Product Type Invalid Or Not Allowed');

            return $returnArr;
        }

        if (isset($data[$catalogProduct->getIdFieldName()])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Unable to save product')
            );
        }

        $originalSku = $catalogProduct->getSku();
        $catalogProduct->save();
        $mageProductId = $catalogProduct->getId();

        /*for configurable associated products save start*/

        $configurations = [];
        if (!empty($wholedata['configurations'])) {
            $configurations = $wholedata['configurations'];
        }

        if (!empty($configurations)) {
            $configurations = $this->_variationHandler
            ->duplicateImagesForVariations($configurations);
            foreach ($configurations as $associtedProductId => $associtedProductData) {
                /* @var \Magento\Catalog\Model\Product $catalogProduct */
                $associtedProduct = $this->_productRepositoryInterface
                ->getById(
                    $associtedProductId, false, $storeId
                );
                $associtedProductData = $this->_variationHandler
                ->processMediaGallery(
                    $associtedProduct, $associtedProductData
                );
                $associtedProduct->addData($associtedProductData);
                if ($associtedProduct->hasDataChanges()) {
                    $associtedProduct->save();
                }
            }
        }

        /*for configurable associated products save end*/

        $wholedata['id'] = $mageProductId;
        $this->_eventManager->dispatch(
            'mp_customoption_setdata',
            [$wholedata]
        );
        $sellerProductId = 0;

        /* Set Product status */

        $this->_objectManager->create('Magento\Catalog\Model\Product')
        ->load($mageProductId)->setStatus($status)->save();

        /* Update marketplace product*/
        if ($mageProductId) {
            $sellerProductColls = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Product'
            )
            ->getCollection()
            ->addFieldToFilter(
                'mageproduct_id',
                $mageProductId
            )->addFieldToFilter(
                'seller_id',
                $sellerId
            );
            foreach ($sellerProductColls as $sellerProductColl) {
                $sellerProductId = $sellerProductColl->getId();
            }
            $collection1 = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Product'
            )->load($sellerProductId);
            $collection1->setMageproductId($mageProductId);
            $collection1->setSellerId($sellerId);
            $collection1->setStatus($status);
            if (!$editFlag) {
                $collection1->setCreatedAt($this->_date->gmtDate());
            }
            $collection1->setUpdatedAt($this->_date->gmtDate());
            $collection1->save();
        }

        foreach ($associatedProductIds as $associatedProductId) {
            if ($associatedProductId) {
                $sellerAssociatedProductId = 0;
                $sellerProductColls = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Product'
                )
                ->getCollection()
                ->addFieldToFilter(
                    'mageproduct_id',
                    $associatedProductId
                )
                ->addFieldToFilter(
                    'seller_id',
                    $sellerId
                );
                foreach ($sellerProductColls as $sellerProductColl) {
                    $sellerAssociatedProductId = $sellerProductColl->getId();
                }
                $collection1 = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Product'
                )
                ->load($sellerAssociatedProductId);
                $collection1->setMageproductId($associatedProductId);
                if (!$editFlag) {
                    /* If new product is added*/
                    $collection1->setStatus(SellerProduct::STATUS_ENABLED);
                    $collection1->setCreatedAt($this->_date->gmtDate());
                }
                $collection1->setUpdatedAt($this->_date->gmtDate());
                $collection1->setSellerId($sellerId);
                $collection1->save();
            }
        }

        /*
        * Marketplace Custom Attribute Set Tier Price Observer
        */
        $this->_eventManager->dispatch(
            'mp_customattribute_settierpricedata',
            [$wholedata]
        );

        /*
        * Marketplace Product save before Observer
        */
        $this->_eventManager->dispatch(
            'mp_product_save_after',
            [$wholedata]
        );
        
        $this->sendProductMail($wholedata, $sellerId, $editFlag);

        $returnArr['product_id'] = $mageProductId;

        return $returnArr;
    }

    /**
     * Product initialize function before saving.
     *
     * @param \Magento\Catalog\Model\Product $catalogProduct
     * @param $requestData
     *
     * @return \Magento\Catalog\Model\Product
     */
    protected function productInitialize(\Magento\Catalog\Model\Product $catalogProduct, $requestData) 
    {
        $helper = $this->_marketplaceHelperData;
        $requestProductData = $requestData['product'];
        unset($requestProductData['custom_attributes']);
        unset($requestProductData['extension_attributes']);

        /*
        * Manage seller product Stock data
        */
        if ($requestProductData) {
            $stockData = isset($requestProductData['stock_data']) ? 
            $requestProductData['stock_data'] : [];
            if (isset($stockData['qty']) && (double) $stockData['qty'] > 99999999.9999) {
                $stockData['qty'] = 99999999.9999;
            }
            if (isset($stockData['min_qty']) && (int) $stockData['min_qty'] < 0) {
                $stockData['min_qty'] = 0;
            }
            if (!isset($stockData['use_config_manage_stock'])) {
                $stockData['use_config_manage_stock'] = 0;
            }
            if ($stockData['use_config_manage_stock'] == 1 && !isset($stockData['manage_stock'])) {
                $stockData['manage_stock'] = $this->stockConfiguration
                ->getManageStock();
            }
            if (!isset($stockData['is_decimal_divided']) || $stockData['is_qty_decimal'] == 0) {
                $stockData['is_decimal_divided'] = 0;
            }
            $requestProductData['stock_data'] = $stockData;
        }

        foreach (['category_ids', 'website_ids'] as $field) {
            if (!isset($requestProductData[$field])) {
                $requestProductData[$field] = [];
            }
        }

        $dateFieldFilters = [];
        $attributes = $catalogProduct->getAttributes();
        foreach ($attributes as $attrKey => $attribute) {
            if ($attribute->getBackend()->getType() == 'datetime') {
                if (
                    array_key_exists($attrKey, $requestProductData) 
                    && $requestProductData[$attrKey] != ''
                ) {
                    $dateFieldFilters[$attrKey] = $this->_dateFilter;
                }
            }
        }

        $inputFilter = new \Zend_Filter_Input(
            $dateFieldFilters, [], $requestProductData
        );
        $requestProductData = $inputFilter->getUnescaped();

        $catalogProduct->addData($requestProductData);

        if ($helper->getSingleStoreStatus()) {
            $catalogProduct->setWebsiteIds([$helper->getWebsiteId()]);
        }

        /*
         * Check for "Use Default Value" field value
         */
        if (!empty($requestData['use_default'])) {
            foreach ($requestData['use_default'] as $attributeCode) {
                $catalogProduct->setData($attributeCode, false);
            }
        }

        /*
         * Set Downloadable links if available
         */
        if (!empty($requestData['links'])) {
            $links = $requestData['links'];
            $links = is_array($links) ? $links : [];
            $linkTypes = ['related', 'upsell', 'crosssell'];
            foreach ($linkTypes as $type) {
                if (isset($links[$type])) {
                    $links[$type] = $this->_jsHelper
                    ->decodeGridSerializedInput($links[$type]);
                }
            }
            $catalogProduct = $this->_productLinks->initializeLinks(
                $catalogProduct, 
                $links
            );
        }

        /*
         * Set Product options to product if exist
         */
        if (isset($requestProductData['options']) && !$catalogProduct->getOptionsReadonly()) {
            if (!is_array($requestProductData['options'])) {
                $requestProductData['options'] = [];
            }
            if (
                !empty($requestData['options_use_default']) 
                && is_array($requestData['options_use_default'])
            ) {
                $productOptions = array_replace_recursive(
                    $requestProductData['options'],
                    $requestData['options_use_default']
                );
                array_walk_recursive(
                    $productOptions, function (&$item) {
                        if ($item === '') {
                            $item = null;
                        }
                    }
                );
            } else {
                $productOptions = $requestProductData['options'];
            }

            $catalogProduct->setProductOptions($productOptions);
        }

        /*
         * Set Product Custom options status to product
         */
        if (empty($requestData['affect_product_custom_options'])) {
            $requestData['affect_product_custom_options'] = '';
        }

        $catalogProduct->setCanSaveCustomOptions(
            (bool) $requestData['affect_product_custom_options'] 
            && !$catalogProduct->getOptionsReadonly()
        );

        return $catalogProduct;
    }

    /**
     * @param array  $data
     * @param string $sellerId
     * @param bool   $editFlag
     */
    private function sendProductMail($data, $sellerId, $editFlag = null)
    {
        $helper = $this->_marketplaceHelperData;

        $customer = $this->_objectManager->get(
            'Magento\Customer\Model\Customer'
        )->load($sellerId);

        $sellerName = $customer->getFirstname().' '.$customer->getLastname();
        $sellerEmail = $customer->getEmail();

        if (isset($data['product']) && !empty($data['product']['category_ids'])) {
            $categoriesy = $this->_objectManager->get(
                'Magento\Catalog\Model\Category'
            )->load(
                $data['product']['category_ids'][0]
            );
            $categoryname = $categoriesy->getName();
        } else {
            $categoryname = '';
        }

        $emailTempVariables = [];
        $adminStoremail = $helper->getAdminEmailId();
        $adminEmail = $adminStoremail ? 
        $adminStoremail : $helper->getDefaultTransEmailId();
        $adminUsername = 'Admin';

        $emailTempVariables['myvar1'] = $data['product']['name'];
        $emailTempVariables['myvar2'] = $categoryname;
        $emailTempVariables['myvar3'] = $adminUsername;
        if ($editFlag == null) {
            $emailTempVariables['myvar4'] = __(
                'I would like to inform you that recently I have added a new product in the store.'
            );
        } else {
            $emailTempVariables['myvar4'] = __(
                'I would like to inform you that recently I have updated a  product in the store.'
            );
        }
        $senderInfo = [
            'name' => $sellerName,
            'email' => $sellerEmail,
        ];
        $receiverInfo = [
            'name' => $adminUsername,
            'email' => $adminEmail,
        ];
        if (($editFlag == null && $helper->getIsProductApproval()==1) 
            || ($editFlag && $helper->getIsProductApproval()==1)) {
            $this->_objectManager->create(
                'Webkul\Marketplace\Helper\Email'
            )->sendNewProductMail(
                $emailTempVariables, 
                $senderInfo, 
                $receiverInfo, 
                $editFlag
            );
        }
    }
}
