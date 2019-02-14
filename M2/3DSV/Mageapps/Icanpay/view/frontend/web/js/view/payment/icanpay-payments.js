define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
        /*'Magento_Checkout/js/view/payment/default'*/
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'mageapps_icanpay',
                component: 'Mageapps_Icanpay/js/view/payment/method-renderer/icanpay-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);