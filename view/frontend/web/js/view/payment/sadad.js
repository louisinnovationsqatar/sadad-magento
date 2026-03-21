/**
 * SADAD Payment Gateway - payment renderer registration
 * Built by Louis Innovations (www.louis-innovations.com)
 */
define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'sadad',
        component: 'LouisInnovations_Sadad/js/view/payment/method-renderer/sadad'
    });

    return Component.extend({});
});
