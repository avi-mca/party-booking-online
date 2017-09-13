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
namespace Webkul\Marketplace\Model\Order\Pdf;

use Magento\Customer\Model\Session;

/**
 * Marketplace Order Shipment PDF model
 */
class Shipment extends \Magento\Sales\Model\Order\Pdf\Shipment
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $_string;

    /**
     * @param Session                                              $customerSession,
     * @param \Magento\Framework\ObjectManagerInterface            $objectManager,
     * @param \Magento\Payment\Helper\Data                         $paymentData
     * @param \Magento\Framework\Stdlib\StringUtils                $string
     * @param \Magento\Framework\App\Config\ScopeConfigInterface   $scopeConfig
     * @param \Magento\Framework\Filesystem                        $filesystem
     * @param Config                                               $pdfConfig
     * @param \Magento\Sales\Model\Order\Pdf\Total\Factory         $pdfTotalFactory
     * @param \Magento\Sales\Model\Order\Pdf\ItemsFactory          $pdfItemsFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Translate\Inline\StateInterface   $inlineTranslation
     * @param \Magento\Sales\Model\Order\Address\Renderer          $addressRenderer
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManager
     * @param \Magento\Framework\Locale\ResolverInterface          $localeResolver
     * @param array                                                $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Session $customerSession,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Sales\Model\Order\Pdf\Config $pdfConfig,
        \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory,
        \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->_objectManager = $objectManager;
        $this->_string = $string;
        parent::__construct(
            $paymentData,
            $string,
            $scopeConfig,
            $filesystem,
            $pdfConfig,
            $pdfTotalFactory,
            $pdfItemsFactory,
            $localeDate,
            $inlineTranslation,
            $addressRenderer,
            $storeManager,
            $localeResolver,
            $data
        );
    }

    /**
     * Retrieve customer session object.
     *
     * @return \Magento\Customer\Model\Session
     */
    public function getString()
    {
        return $this->_string;
    }

    /**
     * Retrieve customer session object.
     *
     * @return \Magento\Customer\Model\Session
     */
    public function getObjectManager()
    {
        return $this->_objectManager;
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
     * Insert Seller logo to seller pdf page.
     *
     * @param \Zend_Pdf_Page &$sellerPdfPage
     * @param null           $store
     */
    protected function insertLogo(&$sellerPdfPage, $store = null)
    {
        $sellerImage = '';
        $sellerImageFlag = 0;
        $sellerId = $this->_getSession()->getCustomerId();
        $collection = $this->_objectManager->create('Webkul\Marketplace\Model\Seller')
                    ->getCollection()
                    ->addFieldToFilter('seller_id', $sellerId);
        foreach ($collection as $row) {
            $sellerImage = $row->getLogoPic();
            if ($sellerImage) {
                $sellerImageFlag = 1;
            }
        }

        if ($sellerImage == '') {
            $sellerImage = $this->_scopeConfig
            ->getValue(
                'sales/identity/logo',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
            $sellerImageFlag = 0;
        }
        $this->y = $this->y ? $this->y : 815;
        if ($sellerImage) {
            if ($sellerImageFlag == 0) {
                $sellerImagePath = '/sales/store/logo/'.$sellerImage;
            } else {
                $sellerImagePath = '/avatar/'.$sellerImage;
            }
            if ($this->_mediaDirectory->isFile($sellerImagePath)) {
                $sellerImage = \Zend_Pdf_Image::imageWithPath(
                    $this->_mediaDirectory->getAbsolutePath($sellerImagePath)
                );
                $imageTop = 830; //top border of the page
                $imageWidthLimit = 270; //image width half of the page width
                $imageHeightLimit = 270;
                $imageWidth = $sellerImage->getPixelWidth();
                $imageHeight = $sellerImage->getPixelHeight();

                //preserving seller image aspect ratio
                $imageRatio = $imageWidth / $imageHeight;
                if ($imageRatio > 1 && $imageWidth > $imageWidthLimit) {
                    $imageWidth = $imageWidthLimit;
                    $imageHeight = $imageWidth / $imageRatio;
                } elseif ($imageRatio < 1 && $imageHeight > $imageHeightLimit) {
                    $imageHeight = $imageHeightLimit;
                    $imageWidth = $imageHeight * $imageRatio;
                } elseif ($imageRatio == 1 && $imageHeight > $imageHeightLimit) {
                    $imageHeight = $imageHeightLimit;
                    $imageWidth = $imageWidthLimit;
                }
                $y1Axis = $imageTop - $imageHeight;
                $y2Axis = $imageTop;
                $x1Axis = 25;
                $x2Axis = $x1Axis + $imageWidth;
                //seller image coordinates after transformation seller image are rounded by Zend
                $sellerPdfPage->drawImage($sellerImage, $x1Axis, $y1Axis, $x2Axis, $y2Axis);
                $this->y = $y1Axis - 10;
            }
        }
    }

    /**
     * Insert seller address address and other info to pdf page.
     *
     * @param \Zend_Pdf_Page &$sellerPdfPage
     * @param null           $store
     */
    protected function insertAddress(&$sellerPdfPage, $store = null)
    {
        $sellerPdfPage->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $font = $this->_setFontRegular($sellerPdfPage, 10);
        $sellerPdfPage->setLineWidth(0);
        $this->y = $this->y ? $this->y : 815;
        $imageTop = 815;

        $address = '';
        $sellerId = $this->_getSession()->getCustomerId();
        $collection = $this->_objectManager->create('Webkul\Marketplace\Model\Seller')
                    ->getCollection()
                    ->addFieldToFilter('seller_id', $sellerId);
        foreach ($collection as $row) {
            $address = $row->getOthersInfo();
        }

        if ($address == '') {
            $address = $this->_scopeConfig->getValue(
                'sales/identity/address',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        foreach (explode("\n", $address) as $value) {
            if ($value !== '') {
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach ($this->string->split($value, 45, true, true) as $_value) {
                    $sellerPdfPage->drawText(
                        trim(strip_tags($_value)),
                        $this->getAlignRight($_value, 130, 440, $font, 10),
                        $imageTop,
                        'UTF-8'
                    );
                    $imageTop -= 10;
                }
            }
        }
        $this->y = $this->y > $imageTop ? $imageTop : $this->y;
    }
}
