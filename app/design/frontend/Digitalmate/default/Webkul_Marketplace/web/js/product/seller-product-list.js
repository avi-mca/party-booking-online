/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2016 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
 /*jshint jquery:true*/
define([
    "jquery",
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    "jquery/ui",
    'mage/calendar'
], function ($, $t, alert) {
    'use strict';
    $.widget('mage.sellerProductList', {
        _create: function () {
            var self = this;
            $("#special-from-date").calendar({'dateFormat':'mm/dd/yy'});
            $("#special-to-date").calendar({'dateFormat':'mm/dd/yy'});

            $('body').delegate('.mp-edit','click',function(){
                var dicision=confirm($t(" Are you sure you want to edit this product ? "));
                if(dicision === true){         
                    var $url=$(this).attr('data-url');
                    window.location = $url;
                }
            });
            $('#mass-delete-butn').click(function(e){
                var flag =0;
                $('.mpcheckbox').each(function(){
                    if (this.checked === true){
                        flag =1;
                    }
                });
                if (flag === 0){
                    alert({content : $t(' No Checkbox is checked ')});
                    return false;
                }
                else{
                    var dicisionapp=confirm($t(" Are you sure you want to delete these product ? "));
                    if(dicisionapp === true){
                        $('#form-customer-product-new').submit();
                    }else{
                        return false;
                    }
                }
            });

            $('#mpselecctall').click(function(event) {
                if(this.checked) {
                    $('.mpcheckbox').each(function() {
                        this.checked = true;      
                    });
                }else{
                    $('.mpcheckbox').each(function() {
                        this.checked = false;           
                    });         
                }
            });

            $('.mp-delete').click(function(){
                var dicisionapp=confirm($t(" Are you sure you want to delete this product ? "));
                if(dicisionapp === true){
                    var $url=$(this).attr('data-url');
                    window.location = $url;
                }
            });
        }
    });
    return $.mage.sellerProductList;
});
