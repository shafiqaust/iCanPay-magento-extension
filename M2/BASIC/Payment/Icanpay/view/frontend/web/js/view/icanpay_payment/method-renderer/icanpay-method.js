define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Payment/js/model/credit-card-validation/validator',
        /*'Magento_Checkout/js/action/set-payment-information'*/
    ],
    function (Component, $) {
        'use strict';
        $(document).ready(function(){
          
            
        });
        
        return Component.extend({
            defaults: {
                template: 'Payment_Icanpay/icanpay_payment/icanpay-form'
            },
            initialize: function (data) {
                this._super();
                
            },
            
            getCode: function() {
                return 'payment_icanpay';
            },

            isActive: function() {
                return true;
            },
            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },
            onEnter: function(data, evt){
                
                
                var charCode = (evt.which) ? evt.which : event.keyCode;
                if(charCode == 8) return true;
                if ((charCode >= 48 && charCode <= 57) || (charCode == 8 || charCode == 127 || charCode == 13))
                {

                    document.getElementById('errorOnCVV').style.display = 'none';
                    document.getElementById('errorOnCCN').style.display = 'none';
                  return true;  
                }
                else 
                {
                    var errorMsg = 'Please Enter Numbers Only!';
                    if(evt.target.id.toString() == 'payment_icanpay_cc_number')
                    {
                        document.getElementById('errorOnCCN').innerHTML = errorMsg;
                        document.getElementById('errorOnCCN').style.display = 'block';
                        document.getElementById('errorOnCCN').style.color = '#e02b27';
                        document.getElementById('errorOnCCN').style.fontSize = '1.2rem';
                        document.getElementById('errorOnCCN').style.marginTop = '7px';
                        document.getElementById('errorOnCVV').style.display = 'none';
                        
                    }
                    else if(evt.target.id.toString() == 'payment_icanpay_cc_cid')
                    {
                        document.getElementById('errorOnCVV').innerHTML = errorMsg;
                        document.getElementById('errorOnCVV').style.display = 'block';
                        document.getElementById('errorOnCVV').style.color = '#e02b27';
                        document.getElementById('errorOnCVV').style.fontSize = '1.2rem';
                        document.getElementById('errorOnCVV').style.marginTop = '7px';
                        document.getElementById('errorOnCCN').style.display = 'none';

                    }
                    else
                    {
                        document.getElementById('errorOnCVV').style.display = 'none';
                        document.getElementById('errorOnCCN').style.display = 'none';
                    }
                    
                    
                    
                    document.getElementById(evt.target.id).focus();
                    return false;
                }
                
            }

        });
    }
);
