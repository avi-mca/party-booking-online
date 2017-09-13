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
namespace Webkul\Marketplace\Observer;

use Magento\Framework\Event\ObserverInterface;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;

/**
 * Webkul Marketplace CustomerRegisterSuccessObserver Observer.
 */
class CustomerRegisterSuccessObserver implements ObserverInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface   $objectManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Store\Model\StoreManagerInterface  $storeManager
     * @param CollectionFactory                           $collectionFactory
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory
    ) 
    {
        $this->_objectManager = $objectManager;
        $this->_storeManager = $storeManager;
        $this->_collectionFactory = $collectionFactory;
        $this->_date = $date;
    }

    /**
     * customer register event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->moveDirToMediaDir();
        $data = $observer['account_controller'];
        try {
            $paramData = $data->getRequest()->getParams();
            if (!empty($paramData['is_seller']) && $paramData['is_seller'] == 1) {
                $customer = $observer->getCustomer();

                $profileurlcount = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Seller'
                )->getCollection();
                $profileurlcount->addFieldToFilter(
                    'shop_url', 
                    $paramData['profileurl']
                );
                if (!$profileurlcount->getSize()) {
                    $status = $this->_objectManager->get(
                        'Webkul\Marketplace\Helper\Data'
                    )->getIsPartnerApproval() ? 0 : 1;
                    $customerid = $customer->getId();
                    $model = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                    );
                    $model->setData('is_seller', $status);
                    $model->setData('shop_url', $paramData['profileurl']);
                    $model->setData('seller_id', $customerid);
                    $model->setCreatedAt($this->_date->gmtDate());
                    $model->setUpdatedAt($this->_date->gmtDate());
                    $model->save();

                    $helper = $this->_objectManager->get(
                        'Webkul\Marketplace\Helper\Data'
                    );
                    $adminStoremail = $helper->getAdminEmailId();
                    $adminEmail = $adminStoremail ? $adminStoremail : $helper->getDefaultTransEmailId();
                    $adminUsername = 'Admin';
                    $senderInfo = [
                        'name' => $customer->getFirstName().' '.$customer->getLastName(),
                        'email' => $customer->getEmail(),
                    ];
                    $receiverInfo = [
                        'name' => $adminUsername,
                        'email' => $adminEmail,
                    ];
                    $emailTemplateVariables['myvar1'] = $customer->getFirstName().' '.
                    $customer->getLastName();
                    $emailTemplateVariables['myvar2'] = $this->_storeManager->getStore()->getUrl(
                        'admin/customer/index/edit', array('id' => $customer->getId())
                    );
                    $emailTemplateVariables['myvar3'] = 'Admin';

                    $this->_objectManager->create(
                        'Webkul\Marketplace\Helper\Email'
                    )->sendNewSellerRequest(
                        $emailTemplateVariables, 
                        $senderInfo, 
                        $receiverInfo
                    );
                } else {
                    $this->messageManager->addError(
                        __('This Shop URL already Exists.')
                    );
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
    }

    private function moveDirToMediaDir($value = '')
    {
        try {
            /** @var \Magento\Framework\ObjectManagerInterface $objManager */
            $objManager = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \Magento\Framework\Module\Dir\Reader $reader */
            $reader = $objManager->get('Magento\Framework\Module\Dir\Reader');

            /** @var \Magento\Framework\Filesystem $filesystem */
            $filesystem = $objManager->get('Magento\Framework\Filesystem');

            $mediaAvatarFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('avatar');
            if (!file_exists($mediaAvatarFullPath)) {
                mkdir($mediaAvatarFullPath, 0777, true);
                $avatarBannerImage = $reader->getModuleDir(
                    '', 'Webkul_Marketplace'
                ).'/view/base/web/images/avatar/banner-image.png';
                copy($avatarBannerImage, $mediaAvatarFullPath.'/banner-image.png');
                $avatarNoImage = $reader->getModuleDir(
                    '', 'Webkul_Marketplace'
                ).'/view/base/web/images/avatar/noimage.png';
                copy($avatarNoImage, $mediaAvatarFullPath.'/noimage.png');
            }

            $mediaMarketplaceFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('marketplace');
            if (!file_exists($mediaMarketplaceFullPath)) {
                mkdir($mediaMarketplaceFullPath, 0777, true);
            }

            $mediaMarketplaceBannerFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('marketplace/banner');
            if (!file_exists($mediaMarketplaceBannerFullPath)) {
                mkdir($mediaMarketplaceBannerFullPath, 0777, true);
                $marketplaceBannerImage = $reader->getModuleDir(
                    '', 'Webkul_Marketplace'
                ).'/view/base/web/images/marketplace/banner/sell-page-banner.png';
                copy(
                    $marketplaceBannerImage, 
                    $mediaMarketplaceBannerFullPath.'/sell-page-banner.png'
                );
            }

            $mediaMarketplaceIconFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('marketplace/icon');
            if (!file_exists($mediaMarketplaceIconFullPath)) {
                mkdir($mediaMarketplaceIconFullPath, 0777, true);
                $icon1BannerImage = $reader->getModuleDir(
                    '', 'Webkul_Marketplace'
                ).'/view/base/web/images/marketplace/icon/icon-add-products.png';
                copy(
                    $icon1BannerImage, 
                    $mediaMarketplaceIconFullPath.'/icon-add-products.png'
                );

                $icon2BannerImage = $reader->getModuleDir(
                    '', 'Webkul_Marketplace'
                ).'/view/base/web/images/marketplace/icon/icon-collect-revenues.png';
                copy(
                    $icon2BannerImage, 
                    $mediaMarketplaceIconFullPath.'/icon-collect-revenues.png'
                );

                $icon3BannerImage = $reader->getModuleDir(
                    '', 'Webkul_Marketplace'
                ).'/view/base/web/images/marketplace/icon/icon-register-yourself.png';
                copy(
                    $icon3BannerImage, 
                    $mediaMarketplaceIconFullPath.'/icon-register-yourself.png'
                );

                $icon4BannerImage = $reader->getModuleDir(
                    '', 'Webkul_Marketplace'
                ).'/view/base/web/images/marketplace/icon/icon-start-selling.png';
                copy(
                    $icon4BannerImage, 
                    $mediaMarketplaceIconFullPath.'/icon-start-selling.png'
                );
            }

            $mediaPlaceholderFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('placeholder');
            if (!file_exists($mediaPlaceholderFullPath)) {
                mkdir($mediaPlaceholderFullPath, 0777, true);
                $placeholderImage = $reader->getModuleDir(
                    '', 'Webkul_Marketplace'
                ).'/view/base/web/images/placeholder/image.jpg';
                copy(
                    $placeholderImage, 
                    $mediaMarketplaceIconFullPath.'/image.jpg'
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
    }
}
