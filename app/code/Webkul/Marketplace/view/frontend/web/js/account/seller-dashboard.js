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
    "jquery/ui"
], function ($, $t, alert) {
    'use strict';
    $.widget('mage.sellerDashboard', {
        options: {
            backUrl: '',
            opacityLow: '0.4',
            opacityHigh: '1',
            ajaxSuccessMessage: $t('Mail Send.'),
            ajaxErrorMessage: $t('There was error during fetching results.'),
            wrongCaptchaErrorMessage: $t('Wrong varification number.')
        },
        _create: function () {
            var self = this;

            $('body').append($(self.options.mpAskDataSelector));

            $(self.options.askQueSelector).on('click', function () {
                $(self.options.askFormInputSelector, self.options.askFormTextareaSelector).removeClass(self.options.mageErrorClass);
                $(self.options.pageWrapperSelector).css('opacity', self.options.opacityLow);
                $(self.options.mpModelPopupSelector).addClass(self.options.showClass);
                $(self.options.mpAskDataSelector).show();
            });

            $(self.options.wkCloseSelector).click(function(){
                $(self.options.pageWrapperSelector).css('opacity', self.options.opacityHigh);
                $(self.options.resetBtnSelector).trigger('click');
                $(self.options.mpAskDataSelector).hide();
                $(self.options.askFormValidationFailedSelector).each(function(){
                    $(this).removeClass(self.options.validationFailedSelector);
                });
                $(self.options.askFormValidationAdviceSelector).each(function(){
                    $(this).remove();
                });
            });

            $(self.options.askBtnSelector).click(function(){
                var askDataForm = $(self.options.askFormSelector);
                askDataForm.mage('validation', {});
                if(askDataForm.valid()!=false){
                    var thisthis = $(this);
                    if(thisthis.hasClass("clickask")){
                        if(parseInt(self.options.captchaEnableStatus)){
                            var total = parseInt($(self.options.mpCaptcha1Selector).text()) + parseInt($(self.options.mpCaptcha2Selector).text());
                            var wkMpCaptcha = $(self.options.mpCaptchaSelector).val();
                            if(total != wkMpCaptcha){
                                $(self.options.mpCaptcha1Selector).text(Math.floor((Math.random()*10)+1));
                                $(self.options.mpCaptcha2Selector).text(Math.floor((Math.random()*100)+1));
                                $(self.options.mpCaptchaSelector).val('');
                                $(self.options.mpCaptchaSelector).addClass(self.options.mageErrorClass);
                                $(this).addClass(self.options.mageErrorClass);
                                $(self.options.askFormErrorMailSelector)
                                    .text(self.options.wrongCaptchaErrorMessage)
                                    .slideDown('slow')
                                    .delay(2000)
                                    .slideUp('slow');
                                return false;
                            }
                        }
                        self.callAjaxFunction(thisthis);
                    }
                    return false;
                }
            });
        },

        callAjaxFunction: function (thisthis) {
            var self = this;
            thisthis.removeClass('clickask');
            $(self.options.mpAskDataSelector).addClass(self.options.mailProcssClass);
            $.ajax({
                url : self.options.ajaxMailSendUrl,
                data : $(self.options.askFormSelector).serialize(),
                type : 'post',
                dataType : 'json',
                success: function(d){
                    thisthis.addClass('clickask');
                    $(self.options.mpAskDataSelector).removeClass(self.options.mailProcssClass);
                    alert({
                        content: self.options.ajaxSuccessMessage
                    });
                    $(self.options.wkCloseSelector, self.options.resetBtnSelector).trigger('click');
                },
                error: function (response) {
                    alert({
                        content: self.options.ajaxErrorMessage
                    });
                }
            });
        }
    });
    return $.mage.sellerDashboard;
});
