/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2016 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
var config = {
    map: {
        '*': {
            colorpicker: 'Webkul_Marketplace/js/colorpicker',
            verifySellerShop: 'Webkul_Marketplace/js/account/verify-seller-shop',
            editSellerProfile: 'Webkul_Marketplace/js/account/edit-seller-profile',
            sellerDashboard: 'Webkul_Marketplace/js/account/seller-dashboard',
            sellerAddProduct: 'Webkul_Marketplace/js/product/seller-add-product',
            sellerEditProduct: 'Webkul_Marketplace/js/product/seller-edit-product',
            sellerCreateConfigurable: 'Webkul_Marketplace/js/product/attribute/create',
            sellerProductList: 'Webkul_Marketplace/js/product/seller-product-list',
            sellerOrderHistory: 'Webkul_Marketplace/js/order/history',
            colorPickerFunction: 'Webkul_Marketplace/js/color-picker-function'
        }
    },
    paths: {
        "colorpicker": 'js/colorpicker'
    },
    "shim": {
        "colorpicker" : ["jquery"]
    }
};
