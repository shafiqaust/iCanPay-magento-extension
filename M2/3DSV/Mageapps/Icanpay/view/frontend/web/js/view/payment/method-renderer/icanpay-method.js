define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/redirect-on-success',
        /*'Magento_Checkout/js/action/set-payment-information'*/
    ],
    function (Component, $,validator,b,url,placeOrderAction,redirectOnSuccessAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Mageapps_Icanpay/payment/icanpay-form'
            },

            getCode: function() {
                return 'mageapps_icanpay';
            },

            isActive: function() {
                return true;
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },
            afterPlaceOrder: function () {
                redirectOnSuccessAction.redirectUrl = url.build('icanpay/icanpay/redirect');
                this.redirectAfterPlaceOrder = true;
            },
            onEnter: function(data, evt){
                
                
                var charCode = (evt.which) ? evt.which : event.keyCode;
                if(charCode == 8) return true;
                if ((charCode >= 48 && charCode <= 57) || (charCode == 8 || charCode == 127 || charCode == 13))
                {

                    document.getElementById('mageapps_errorOnCVV').style.display = 'none';
                    document.getElementById('mageapps_errorOnCCN').style.display = 'none';
                  return true;  
                }
                else 
                {
                    var errorMsg = 'Please Enter Numbers Only!';
                    if(evt.target.id.toString() == 'mageapps_icanpay_cc_number')
                    {
                        document.getElementById('mageapps_errorOnCCN').innerHTML = errorMsg;
                        document.getElementById('mageapps_errorOnCCN').style.display = 'block';
                        document.getElementById('mageapps_errorOnCCN').style.color = '#e02b27';
                        document.getElementById('mageapps_errorOnCCN').style.fontSize = '1.2rem';
                        document.getElementById('mageapps_errorOnCCN').style.marginTop = '7px';
                        document.getElementById('mageapps_errorOnCVV').style.display = 'none';
                        
                    }
                    else if(evt.target.id.toString() == 'mageapps_icanpay_cc_cid')
                    {
                        document.getElementById('mageapps_errorOnCVV').innerHTML = errorMsg;
                        document.getElementById('mageapps_errorOnCVV').style.display = 'block';
                        document.getElementById('mageapps_errorOnCVV').style.color = '#e02b27';
                        document.getElementById('mageapps_errorOnCVV').style.fontSize = '1.2rem';
                        document.getElementById('mageapps_errorOnCVV').style.marginTop = '7px';
                        document.getElementById('mageapps_errorOnCCN').style.display = 'none';

                    }
                    else
                    {
                        document.getElementById('mageapps_errorOnCVV').style.display = 'none';
                        document.getElementById('mageapps_errorOnCCN').style.display = 'none';
                    }
                    
                    document.getElementById(evt.target.id).focus();
                    return false;
                }
                
            }

        });
    }
);
