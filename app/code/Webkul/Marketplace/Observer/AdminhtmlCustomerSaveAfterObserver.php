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
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Webkul\Marketplace\Model\Product as ProductStatus;

/**
 * Webkul Marketplace AdminhtmlCustomerSaveAfterObserver Observer.
 */
class AdminhtmlCustomerSaveAfterObserver implements ObserverInterface
{
    /**
     * File Uploader factory.
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_fileUploaderFactory;

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
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $_messageManager;

    protected $_mediaDirectory;

    /**
     * @param Filesystem                                       $filesystem,
     * @param \Magento\Framework\ObjectManagerInterface        $objectManager,
     * @param \Magento\Framework\Stdlib\DateTime\DateTime      $date,
     * @param \Magento\Framework\Message\ManagerInterface      $messageManager,
     * @param \Magento\Store\Model\StoreManagerInterface       $storeManager,
     * @param \Magento\Catalog\Api\ProductRepositoryInterface  $productRepository,
     * @param CollectionFactory                                $collectionFactory,
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory
     */
    public function __construct(
        Filesystem $filesystem,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        CollectionFactory $collectionFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory
    ) {
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_productRepository = $productRepository;
        $this->_objectManager = $objectManager;
        $this->_messageManager = $messageManager;
        $this->_collectionFactory = $collectionFactory;
        $this->_storeManager = $storeManager;
        $this->_date = $date;
    }

    /**
     * admin customer save after event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->moveDirToMediaDir();
        $customer = $observer->getCustomer();
        $customerid = $customer->getId();        
        $postData = $observer->getRequest()->getPostValue();
        if ($this->isSeller($customerid)) {
            list($data, $errors) = $this->validateprofiledata($observer);
            $productIds = isset($postData['sellerassignproid']) ? 
            $postData['sellerassignproid'] : '';
            $unassignproid = isset($postData['sellerunassignproid']) ? 
            $postData['sellerunassignproid'] : '';
            $sellerId = $customerid;
            if (isset($postData['is_seller_remove']) 
                && $postData['is_seller_remove'] == true) {
                $this->removePartner($sellerId);
                $this->_messageManager->addSuccess(
                    __('You removed the customer from seller.')
                );

                return $this;
            }
            if ($productIds != '' || $productIds != 0) {
                $this->assignProduct($sellerId, $productIds);
            }
            if ($unassignproid != '' || $unassignproid != 0) {
                $this->unassignProduct($sellerId, $unassignproid);
            }

            $collectionselect = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Saleperpartner'
            )->getCollection()
            ->addFieldToFilter(
                'seller_id', 
                $sellerId
            );
            if ($collectionselect->getSize() == 1) {
                foreach ($collectionselect as $verifyrow) {
                    $autoid = $verifyrow->getEntityId();
                }

                $collectionupdate = $this->_objectManager->get(
                    'Webkul\Marketplace\Model\Saleperpartner'
                )->load($autoid);
                if (!isset($postData['commission'])) {
                    $postData['commission'] = $collectionupdate->getCommissionRate();
                }
                $collectionupdate->setCommissionRate($postData['commission']);
                $collectionupdate->save();
            } else {
                if (!isset($postData['commission'])) {
                    $postData['commission'] = 0;
                }
                $collectioninsert = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Saleperpartner'
                );
                $collectioninsert->setSellerId($sellerId);
                $collectioninsert->setCommissionRate($postData['commission']);
                $collectioninsert->save();
            }
            if (empty($errors)) {
                $target = $this->_mediaDirectory->getAbsolutePath('avatar/');
                // upload logo file
                $postData['banner_pic'] = $this->uploadSellerProfileImage(
                    $target, 'banner_pic'
                );

                // upload logo file
                $postData['logo_pic'] = $this->uploadSellerProfileImage(
                    $target, 'logo_pic'
                );

                $autoId = '';
                $collection = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Seller'
                )
                ->getCollection()
                ->addFieldToFilter('seller_id', $sellerId);
                foreach ($collection as  $value) {
                    $autoId = $value->getId();                    
                    $postData['banner_pic'] = $postData['banner_pic'] ? 
                    $postData['banner_pic'] : $value->getBannerPic();
                    $postData['logo_pic'] = $postData['logo_pic'] ? 
                    $postData['logo_pic'] : $value->getLogoPic();
                }
                $value = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Seller'
                )->load($autoId);
                $value->addData($postData);
                $value->setIsSeller(1);
                $value->setUpdatedAt($this->_date->gmtDate());
                $value->save();
                if (isset($postData['company_description'])) {
                    $postData['company_description'] = str_replace(
                        'script', '', $postData['company_description']
                    );
                    $value->setCompanyDescription(
                        $postData['company_description']
                    );
                }

                if (isset($postData['return_policy'])) {
                    $postData['return_policy'] = str_replace(
                        'script', '', $postData['return_policy']
                    );
                    $value->setReturnPolicy($postData['return_policy']);
                }

                if (isset($postData['shipping_policy'])) {
                    $postData['shipping_policy'] = str_replace(
                        'script', '', $postData['shipping_policy']
                    );
                    $value->setShippingPolicy($postData['shipping_policy']);
                }

                if (isset($postData['meta_description'])) {
                    $value->setMetaDescription($postData['meta_description']);
                }

                if (array_key_exists('country_pic', $postData)) {
                    $value->setCountryPic($postData['country_pic']);
                }
                $value->save();
            }
        } else {
            if (isset($postData['is_seller_add'])) {
                $isSellerAdd = $postData['is_seller_add'];
                $profileurl = $postData['profileurl'];
            } else {
                $isSellerAdd = false;
            }
            if ($isSellerAdd == true) {
                if ($profileurl != '') {
                    $profileurlcount = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                    )->getCollection();
                    $profileurlcount->addFieldToFilter('shop_url', $profileurl);
                    $sellerProfileId = 0;
                    $sellerProfileUrl = '';
                    $collectionselect = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                    )->getCollection();
                    $collectionselect->addFieldToFilter('seller_id', $customerid);
                    foreach ($collectionselect as $coll) {
                        $sellerProfileId = $coll->getEntityId();
                        $sellerProfileUrl = $coll->getShopUrl();
                    }
                    if (
                        $profileurlcount->getSize() 
                        && ($profileurl != $sellerProfileUrl)
                    ) {
                        $this->_messageManager->addError(
                            __('This Shop URL already Exists.')
                        );
                    } else {
                        $collection = $this->_objectManager->get(
                            'Webkul\Marketplace\Model\Seller'
                        )->load($sellerProfileId);
                        $collection->setIsSeller(1);
                        $collection->setShopUrl($profileurl);
                        $collection->setSellerId($customerid);
                        $collection->setCreatedAt($this->_date->gmtDate());
                        $collection->setUpdatedAt($this->_date->gmtDate());
                        $collection->save();

                        $helper = $this->_objectManager->get(
                            'Webkul\Marketplace\Helper\Data'
                        );
                        $adminStoreEmail = $helper->getAdminEmailId();
                        $adminEmail = $adminStoreEmail ? $adminStoreEmail : 
                        $helper->getDefaultTransEmailId();
                        $adminUsername = 'Admin';

                        $seller = $this->_objectManager->get(
                            'Magento\Customer\Model\Customer'
                        )->load($customerid);

                        $emailTempVariables['myvar1'] = $seller->getName();
                        $emailTempVariables['myvar2'] = $this->_storeManager
                        ->getStore()->getUrl(
                            'customer/account/login'
                        );
                        $senderInfo = [
                            'name' => $adminUsername,
                            'email' => $adminEmail,
                        ];
                        $receiverInfo = [
                            'name' => $seller->getName(),
                            'email' => $seller->getEmail(),
                        ];
                        $this->_objectManager->create(
                            'Webkul\Marketplace\Helper\Email'
                        )->sendSellerApproveMail(
                            $emailTempVariables, $senderInfo, $receiverInfo
                        );
                        $this->_messageManager->addSuccess(
                            __('You created the customer as seller.')
                        );
                    }
                } else {
                    $this->_messageManager->addError(
                        __('Enter Shop Name of Customer.')
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Upload Seller Profile Image.
     *
     * @param string $target
     * @param string $fileName
     * @return string
     */
    protected function uploadSellerProfileImage ($target, $fileName)
    {
        try {
            /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
            $uploader = $this->_fileUploaderFactory->create(
                ['fileId' => $fileName]
            );
            $uploader->setAllowedExtensions(
                ['jpg', 'jpeg', 'gif', 'png']
            );
            $uploader->setAllowRenameFiles(true);
            $resul = $uploader->save($target);
            if ($resul['file']) {
                return $resul['file'];
            }
        } catch(\Exception $e) {
            return '';
        }
    }

    public function isSeller($customerid)
    {
        $sellerStatus = 0;
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )
        ->getCollection()
        ->addFieldToFilter('seller_id', $customerid);
        foreach ($model as $value) {
            $sellerStatus = $value->getIsSeller();
        }

        return $sellerStatus;
    }

    private function validateprofiledata($observer)
    {
        $errors = [];
        $data = [];
        $paramData = $observer->getRequest()->getParams();
        foreach ($paramData as $code => $value) {
            switch ($code) :
                case 'twitter_id':
                    if (
                        trim($value) != '' && 
                        preg_match('/[\'^£$%&*()}{@#~?><>, |=_+¬-]/', $value)
                    ) {
                        $errors[] = __(
                            'Twitterid cannot contain space and special charecters'
                        );
                    } else {
                        $data[$code] = $value;
                    }
                    break;
                case 'facebook_id':
                    if (
                        trim($value) != '' &&  
                        preg_match('/[\'^£$%&*()}{@#~?><>, |=_+¬-]/', $value)
                    ) {
                        $errors[] = __(
                            'Facebookid cannot contain space and special charecters'
                        );
                    } else {
                        $data[$code] = $value;
                    }
                    break;
            endswitch;
        }

        return [$data, $errors];
    }

    private function removePartner($sellerId)
    {
        $collectionselectdelete = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )->getCollection();
        $collectionselectdelete->addFieldToFilter(
            'seller_id', 
            $sellerId
        );
        foreach ($collectionselectdelete as $delete) {
            $autoid = $delete->getEntityId();
        }
        $collectiondelete = $this->_objectManager->get(
            'Webkul\Marketplace\Model\Seller'
        )->load($autoid);
        $collectiondelete->delete();
        //Set Produt status disabled
        $sellerProduct = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )
        ->getCollection()
        ->addFieldToFilter(
            'seller_id', 
            $sellerId
        );

        foreach ($sellerProduct as $productInfo) {
            $allStores = $this->_storeManager->getStores();
            foreach ($allStores as $_eachStoreId => $val) {
                $product = $this->_productRepository->getById(
                    $productInfo->getMageproductId()
                );
                $product->setStatus(
                    ProductStatus::STATUS_DISABLED
                );
                $this->_productRepository->save($product);
            }

            $productInfo->setStatus(0);
            $productInfo->save();
        }

        $helper = $this->_objectManager->get(
            'Webkul\Marketplace\Helper\Data'
        );
        $adminStoreEmail = $helper->getAdminEmailId();
        $adminEmail = $adminStoreEmail ? 
        $adminStoreEmail : $helper->getDefaultTransEmailId();
        $adminUsername = 'Admin';

        $seller = $this->_objectManager->get(
            'Magento\Customer\Model\Customer'
        )->load($sellerId);

        $emailTempVariables['myvar1'] = $seller->getName();
        $emailTempVariables['myvar2'] = $this->_storeManager->getStore()
        ->getUrl(
            'customer/account/login'
        );
        $senderInfo = [
            'name' => $adminUsername,
            'email' => $adminEmail,
        ];
        $receiverInfo = [
            'name' => $seller->getName(),
            'email' => $seller->getEmail(),
        ];
        $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Email'
        )->sendSellerDisapproveMail(
            $emailTempVariables, 
            $senderInfo, 
            $receiverInfo
        );
    }

    public function assignProduct($sellerId, $productIds)
    {
        $productids = explode(',', $productIds);

        $productCollection = $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        )->getCollection()
        ->addFieldToFilter(
            'entity_id', 
            ['in' => $productids]
        );
        $successMessage = '';
        foreach ($productCollection as $product) {
            $proid = $product->getID();
            $userid = '';
            $collection = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Product'
            )->getCollection()
            ->addFieldToFilter(
                'mageproduct_id', 
                $proid
            );
            $flag = 1;
            foreach ($collection as $coll) {
                $flag = 0;
                if ($sellerId != $coll['seller_id']) {
                    $this->_messageManager->addError(
                        __('The product with id %1 is already assigned to other seller.', $proid)
                    );
                }
            }
            if ($flag) {
                $collection1 = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Product'
                );
                $collection1->setMageproductId($proid);
                $collection1->setSellerId($sellerId);
                $collection1->setStatus($product->getStatus());
                $collection1->setAdminassign(1);
                $collection1->setStoreId(
                    [$this->_storeManager->getStore()->getId()]
                );
                $collection1->setCreatedAt($this->_date->gmtDate());
                $collection1->setUpdatedAt($this->_date->gmtDate());
                $collection1->save();
                $successMessage = __(
                    'Products has been successfully assigned to seller.'
                );
            }
        }
        if ($successMessage) {
            $this->_messageManager->addSuccess($successMessage);
        }
    }

    public function unassignProduct($sellerId, $productIds)
    {
        $productids = explode(',', $productIds);
        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )->getCollection()
        ->addFieldToFilter(
            'mageproduct_id', 
            ['in' => $productids]
        )
        ->addFieldToFilter(
            'seller_id', 
            $sellerId
        );
        foreach ($collection as $coll) {
            $coll->delete();
        }
        $this->_messageManager->addSuccess(
            __('Products has been successfully unassigned to seller.')
        );
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
                    $mediaPlaceholderFullPath.'/image.jpg'
                );
            }
        } catch (\Exception $e) {
            $this->_messageManager->addError($e->getMessage());
        }
    }
}
