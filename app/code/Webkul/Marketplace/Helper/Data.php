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
namespace Webkul\Marketplace\Helper;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Webkul\Marketplace\Model\Product as SellerProduct;

/**
 * Webkul Marketplace Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var null|array
     */
    protected $_options;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $_product;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @param Magento\Framework\App\Helper\Context         $context
     * @param Magento\Directory\Model\Currency             $currency
     * @param Magento\Customer\Model\Session               $customerSession
     * @param CollectionFactory                            $collectionFactory
     * @param Magento\Catalog\Model\ResourceModel\Product  $product
     * @param Magento\Store\Model\StoreManagerInterface    $storeManager
     * @param Magento\Directory\Model\Currency             $currency
     * @param Magento\Framework\Locale\CurrencyInterface   $localeCurrency,
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\Session $customerSession,
        CollectionFactory $collectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product $product,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency
    ) 
    {
        $this->_objectManager = $objectManager;
        $this->_customerSession = $customerSession;
        $this->_collectionFactory = $collectionFactory;
        $this->_product = $product;
        parent::__construct($context);
        $this->_currency = $currency;
        $this->_localeCurrency = $localeCurrency;
        $this->_storeManager = $storeManager;
    }

    /**
     * Return the Customer seller status.
     *
     * @return bool|0|1
     */
    public function isSeller()
    {
        $sellerStatus = 0;
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )
        ->getCollection()
        ->addFieldToFilter(
            'seller_id', 
            $this->_customerSession->getCustomerId()
        );
        foreach ($model as $value) {
            $sellerStatus = $value->getIsSeller();
        }

        return $sellerStatus;
    }

    /**
     * Return the authorize seller status.
     *
     * @return bool|0|1
     */
    public function isRightSeller($productId = '')
    {
        $data = 0;
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )
        ->getCollection()
        ->addFieldToFilter(
            'mageproduct_id', 
            $productId
        )->addFieldToFilter(
            'seller_id', 
            $this->_customerSession->getCustomerId()
        );
        foreach ($model as $value) {
            $data = 1;
        }

        return $data;
    }

    /**
     * Return the seller Data.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerData()
    {
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )
        ->getCollection()
        ->addFieldToFilter(
            'seller_id', 
            $this->_customerSession->getCustomerId()
        );

        return $model;
    }

    /**
     * Return the seller Product Data.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Product\Collection
     */
    public function getSellerProductData()
    {
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )
        ->getCollection()
        ->addFieldToFilter(
            'seller_id', $this->_customerSession->getCustomerId()
        );

        return $model;
    }

    /**
     * Return the seller product data by product id.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Product\Collection
     */
    public function getSellerProductDataByProductId($productId = '')
    {
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )
        ->getCollection()
        ->addFieldToFilter(
            'mageproduct_id', 
            $productId
        );

        return $model;
    }

    /**
     * Return the seller data by seller id.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerDataBySellerId($sellerId = '')
    {
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )
        ->getCollection()
        ->addFieldToFilter(
            'seller_id', 
            $sellerId
        );

        return $model;
    }

    public function getRootCategoryIdByStoreId($storeId = '')
    {
        return $this->_storeManager->getStore($storeId)->getRootCategoryId();
    }

    public function getAllStores()
    {
        return $this->_storeManager->getStores();
    }

    public function getCurrentStoreId()
    {
        // give the current store id
        return $this->_storeManager->getStore()->getStoreId();
    }

    public function getWebsiteId()
    {
        // give the current store id
        return $this->_storeManager->getStore(true)->getWebsite()->getId();
    }

    public function getSingleStoreStatus()
    {
        return $this->_storeManager->hasSingleStore();
    }

    public function getSingleStoreModeStatus()
    {
        return $this->_storeManager->isSingleStoreMode();
    }

    public function setCurrentStore($storeId)
    {
        return $this->_storeManager->setCurrentStore($storeId);
    }

    public function getCurrentCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode(); 
        // give the currency code
    }

    public function getBaseCurrencyCode()
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }

    public function getConfigAllowCurrencies()
    {
        return $this->_currency->getConfigAllowCurrencies();
    }

    /**
     * Retrieve currency rates to other currencies.
     *
     * @param string     $currency
     * @param array|null $toCurrencies
     *
     * @return array
     */
    public function getCurrencyRates($currency, $toCurrencies = null)
    {
        // give the currency rate
        return $this->_currency->getCurrencyRates($currency, $toCurrencies);
    }

    /**
     * Retrieve currency Symbol.
     *
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this->_localeCurrency->getCurrency(
            $this->getBaseCurrencyCode()
        )->getSymbol();
    }

    /**
     * Retrieve price format.
     *
     * @return string
     */
    public function getPriceFormat()
    {
        return $this->_objectManager->create('Magento\Framework\Locale\Format')->getPriceFormat();
    }

    /**
     * @return array|null
     */
    public function getAllowedSets()
    {
        if (null == $this->_options) {
            $this->_options = $this->_collectionFactory->create()
            ->addFieldToFilter(
                'attribute_set_id', 
                ['in' => explode(',', $this->getAllowedAttributesetIds())]
            )
            ->setEntityTypeFilter($this->_product->getTypeId())
            ->toOptionArray();
        }

        return $this->_options;
    }

    /**
     * Options getter.
     *
     * @return array
     */
    public function getAllowedProductTypes()
    {
        $alloweds = explode(',', $this->getAllowedProductType());
        $data = [
            'simple' => __('Simple'),
            'downloadable' => __('Downloadable'),
            'virtual' => __('Virtual'),
            'configurable' => __('Configurable'),
            'grouped' => __('Grouped Product'),
            'bundle' => __('Bundle Product'),
        ];
        $allowedproducts = [];
        if (isset($alloweds)) {
            foreach ($alloweds as $allowed) {
                if (!empty($data[$allowed])) {
                    array_push(
                        $allowedproducts, 
                        ['value' => $allowed, 'label' => $data[$allowed]]
                    );
                }
            }
        }

        return $allowedproducts;
    }

    /**
     * Return the product visibilty options.
     *
     * @return \Magento\Tax\Model\ClassModel
     */
    public function getTaxClassModel()
    {
        return $this->_objectManager->create('Magento\Tax\Model\ClassModel')
        ->getCollection()
        ->addFieldToFilter('class_type', 'PRODUCT');
    }

    /**
     * Return the product visibilty options.
     *
     * @return \Magento\Catalog\Model\Product\Visibility
     */
    public function getVisibilityOptionArray()
    {
        return $this->_objectManager->create(
            'Magento\Catalog\Model\Product\Visibility'
        )->getOptionArray();
    }

    /**
     * Return the Seller existing status.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function isSellerExist()
    {
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )
        ->getCollection()
        ->addFieldToFilter(
            'seller_id', 
            $this->_customerSession->getCustomerId()
        );

        return $model->getSize();
    }

    /**
     * Return the Seller data by customer Id stored in the session.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSeller()
    {
        $data = [];
        $bannerpic = '';
        $logopic = '';
        $countrylogopic = '';
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )
        ->getCollection()
        ->addFieldToFilter(
            'seller_id', 
            $this->_customerSession->getCustomerId()
        );
        foreach ($model as $value) {
            $data = $value->getData();
            $bannerpic = $value->getBannerPic();
            $logopic = $value->getLogoPic();
            $countrylogopic = $value->getCountryPic();
            if (strlen($bannerpic) <= 0) {
                $bannerpic = 'banner-image.png';
            }
            if (strlen($logopic) <= 0) {
                $logopic = 'noimage.png';
            }
            if (strlen($countrylogopic) <= 0) {
                $countrylogopic = '';
            }
        }
        $data['banner_pic'] = $bannerpic;
        $data['logo_pic'] = $logopic;
        $data['country_pic'] = $countrylogopic;

        return $data;
    }

    public function getFeedTotal($sellerId)
    {
        $data = [];
        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Feedback'
        )
        ->getCollection()
        ->addFieldToFilter(
            'seller_id', 
            $sellerId
        );
        $collection->addFieldToFilter(
            'status', 
            ['neq' => 0]
        );
        $price = 0;
        $value = 0;
        $quality = 0;
        $totalfeed = 0;
        $feedCount = 0;
        $collectionCount = 1;
        foreach ($collection as $record) {
            $price += $record->getFeedPrice();
            $value += $record->getFeedValue();
            $quality += $record->getFeedQuality();
        }
        $collectionSize = $collection->getSize();
        if ($collectionSize != 0) {
            $feedCount = $collectionSize;
            $collectionCount = $collectionSize;
            $totalfeed = ceil(
                ($price + $value + $quality) / (3 * $collectionCount)
            );
        }

        $data = [
            'price' => $price / $collectionCount,
            'value' => $value / $collectionCount,
            'quality' => $quality / $collectionCount,
            'totalfeed' => $totalfeed, 
            'feedcount' => $feedCount
        ];

        return $data;
    }

    public function getSelleRating($sellerId)
    {
        $feeds = $this->getFeedTotal($sellerId);
        $totalRating = (
            $feeds['price'] + $feeds['value'] + $feeds['quality']
        ) / 60;

        return round($totalRating, 1, PHP_ROUND_HALF_UP);
    }

    public function getCatatlogGridPerPageValues()
    {
        return $this->scopeConfig->getValue(
            'catalog/frontend/grid_per_page_values', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCaptchaEnable()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/captcha', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDefaultTransEmailId()
    {
        return $this->scopeConfig->getValue(
            'trans_email/ident_general/email', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAdminEmailId()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/adminemail', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowedCategoryIds()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/categoryids', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsProductEditApproval()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/product_edit_approval', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsPartnerApproval()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/seller_approval', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsProductApproval()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/product_approval', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowedAttributesetIds()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/attributesetid', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowedProductType()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/allow_for_seller', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getUseCommissionRule()
    {
        return $this->scopeConfig->getValue(
            'mpadvancedcommission/options/use_commission_rule', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCommissionType()
    {
        return $this->scopeConfig->getValue(
            'mpadvancedcommission/options/commission_type', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsOrderManage()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/order_manage', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getConfigCommissionRate()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/percent', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getConfigTaxManage()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/tax_manage', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getlowStockNotification()
    {
        return $this->scopeConfig->getValue(
            'marketplace/inventory_settings/low_stock_notification', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getlowStockQty()
    {
        return $this->scopeConfig->getValue(
            'marketplace/inventory_settings/low_stock_amount', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getActiveColorPicker()
    {
        return $this->scopeConfig->getValue(
            'marketplace/profile_settings/activecolorpicker', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerPolicyApproval()
    {
        return $this->scopeConfig->getValue(
            'marketplace/profile_settings/seller_policy_approval', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getUrlRewrite()
    {
        return $this->scopeConfig->getValue(
            'marketplace/profile_settings/url_rewrite', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getReviewStatus()
    {
        return $this->scopeConfig->getValue(
            'marketplace/review_settings/review_status', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplaceHeadLabel()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel1()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel1', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel2()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel2', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel3()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel3', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel4()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel4', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayBanner()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/displaybanner', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerImage()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/banner/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/banner', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerContent()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/bannercontent', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayIcon()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/displayicons', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage1()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon1', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel1()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon1_label', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage2()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon2', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel2()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon2_label', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage3()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon3', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon3_label', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage4()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon4', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel4()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon4_label', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacebutton()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacebutton', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplaceprofile()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplaceprofile', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerlisttopLabel()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/sellerlisttop', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerlistbottomLabel()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/sellerlistbottom', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintStatus()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_hint_status', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintCategory()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_category', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintName()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_name', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintDesc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_des', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintShortDesc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_sdes', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintSku()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_sku', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintPrice()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_price', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintSpecialPrice()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_sprice', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintStartDate()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_sdate', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintEndDate()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_edate', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintQty()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_qty', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintStock()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_stock', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintTax()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_tax', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintWeight()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_weight', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintImage()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_image', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintEnable()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_enable', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintStatus()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_hint_status', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBecomeSeller()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/become_seller', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintShopurl()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/shopurl_seller', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintTw()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_tw', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintFb()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_fb', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintCn()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_cn', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_bc', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintShop()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_shop', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBanner()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_banner', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintLogo()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_logo', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintLoc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_loc', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintDesc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_desciption', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintReturnPolicy()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/returnpolicy', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintShippingPolicy()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/shippingpolicy', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintCountry()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_country', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintMeta()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_meta', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintMetaDesc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_mdesc', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBank()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_bank', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileUrl()
    {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/profile/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if ($temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getCollectionUrl()
    {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/collection/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if ($temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getLocationUrl()
    {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/location/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if ($temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getFeedbackUrl()
    {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/feedback/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if ($temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getRewriteUrl($targetUrl)
    {
        $requestUrl = $this->_urlBuilder->getUrl(
            '', 
            [
                '_direct' => $targetUrl, 
                '_secure' => $this->_request->isSecure()
            ]
        );
        $urlColl = $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite')
        ->getCollection()
        ->addFieldToFilter('target_path', $targetUrl)
        ->addFieldToFilter('store_id', $this->getCurrentStoreId());
        foreach ($urlColl as $value) {
            $requestUrl = $this->_urlBuilder->getUrl(
                '', 
                [
                    '_direct' => $value->getRequestPath(), 
                    '_secure' => $this->_request->isSecure()
                ]
            );
        }

        return $requestUrl;
    }

    public function getRewriteUrlPath($targetUrl)
    {
        $requestPath = '';
        $urlColl = $this->_objectManager->create(
            'Magento\UrlRewrite\Model\UrlRewrite'
        )
        ->getCollection()
        ->addFieldToFilter(
            'target_path',
            $targetUrl
        )
        ->addFieldToFilter(
            'store_id',
            $this->getCurrentStoreId()
        );
        foreach ($urlColl as $value) {
            $requestPath = $value->getRequestPath();
        }

        return $requestPath;
    }

    public function getTargetUrlPath()
    {
        $urls = explode(
            $this->_urlBuilder->getUrl(
                '', 
                ['_secure' => $this->_request->isSecure()]
            ), 
            $this->_urlBuilder->getCurrentUrl()
        );
        $targetUrl = '';
        $temp = explode('/?', $urls[1]);
        if (!isset($temp[1])) {
            $temp[1] = '';
        }
        if (!$temp[1]) {
            $temp = explode('?', $temp[0]);
        }
        $requestPath = $temp[0];
        $urlColl = $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite')
                    ->getCollection()
                    ->addFieldToFilter(
                        'request_path',
                        ['eq' => $requestPath]
                    )
                    ->addFieldToFilter(
                        'store_id',
                        ['eq' => $this->getCurrentStoreId()]
                    );
        foreach ($urlColl as $value) {
            $targetUrl = $value->getTargetPath();
        }

        return $targetUrl;
    }

    public function getPlaceholderImage()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/placeholder/image.jpg';
    }

    public function getSellerProCount($sellerId)
    {
        $querydata = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )
        ->getCollection()
        ->addFieldToFilter('seller_id', $sellerId)
        ->addFieldToFilter('status', ['neq' => SellerProduct::STATUS_DISABLED])
        ->addFieldToSelect('mageproduct_id')
        ->setOrder('mageproduct_id');
        $collection = $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        )
        ->getCollection();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('entity_id', ['in' => $querydata->getData()]);
        $collection->addAttributeToFilter('visibility', ['in' => array(4)]);
        $collection->addStoreFilter();

        $collectionConfigurable = $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        )
        ->getCollection()
        ->addAttributeToFilter('type_id', 'configurable')
        ->addAttributeToFilter('entity_id', ['in' => $querydata->getData()]);

        $outOfStockConfis = [];
        foreach ($collectionConfigurable as $_configurableproduct) {
            $product = $this->_objectManager->create(
                'Magento\Catalog\Model\Product'
            )->load($_configurableproduct->getId());
            if (!$product->getData('is_salable')) {
                $outOfStockConfis[] = $product->getId();
            }
        }
        if (count($outOfStockConfis)) {
            $collection->addAttributeToFilter('entity_id', ['nin' => $outOfStockConfis]);
        }

        $collectionBundle = $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        )
        ->getCollection()
        ->addAttributeToFilter('type_id', 'bundle')
        ->addAttributeToFilter('entity_id', ['in' => $querydata->getData()]);
        $outOfStockConfis = [];
        foreach ($collectionBundle as $_bundleproduct) {
            $product = $this->_objectManager->create(
                'Magento\Catalog\Model\Product'
            )->load($_bundleproduct->getId());
            if (!$product->getData('is_salable')) {
                $outOfStockConfis[] = $product->getId();
            }
        }
        if (count($outOfStockConfis)) {
            $collection->addAttributeToFilter('entity_id', ['nin' => $outOfStockConfis]);
        }

        $collectionGrouped = $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        )
        ->getCollection()
        ->addAttributeToFilter('type_id', 'grouped')
        ->addAttributeToFilter('entity_id', ['in' => $querydata->getData()]);
        $outOfStockConfis = [];
        foreach ($collectionGrouped as $_groupedproduct) {
            $product = $this->_objectManager->create(
                'Magento\Catalog\Model\Product'
            )->load($_groupedproduct->getId());
            if (!$product->getData('is_salable')) {
                $outOfStockConfis[] = $product->getId();
            }
        }
        if (count($outOfStockConfis)) {
            $collection->addAttributeToFilter(
                'entity_id', 
                ['nin' => $outOfStockConfis]
            );
        }

        return $collection->getSize();
    }

    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );
    }

    public function getMaxDownloads()
    {
        return $this->scopeConfig->getValue(
            \Magento\Downloadable\Model\Link::XML_PATH_DEFAULT_DOWNLOADS_NUMBER, 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getConfigPriceWebsiteScope()
    {
        $scope = $this->scopeConfig->getValue(
            \Magento\Store\Model\Store::XML_PATH_PRICE_SCOPE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($scope == \Magento\Store\Model\Store::PRICE_SCOPE_WEBSITE) {
            return true;
        }

        return false;
    }
}
