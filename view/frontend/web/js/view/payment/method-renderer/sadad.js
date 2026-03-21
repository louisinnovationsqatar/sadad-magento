/**
 * SADAD Payment Gateway - Magento 2 Knockout.js checkout component
 * Built by Louis Innovations (www.louis-innovations.com)
 */
define([
    'ko',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Customer/js/model/customer',
    'mage/url',
    'jquery'
], function (
    ko,
    Component,
    fullScreenLoader,
    additionalValidators,
    customer,
    url,
    $
) {
    'use strict';

    return Component.extend({

        defaults: {
            template: 'LouisInnovations_Sadad/payment/sadad',
            redirectAfterPlaceOrder: false
        },

        /**
         * Observables
         */
        isLoading: ko.observable(false),

        /**
         * Initialise the component.
         */
        initialize: function () {
            this._super();
            return this;
        },

        /**
         * Return the payment method code.
         */
        getCode: function () {
            return 'sadad';
        },

        /**
         * Check if the method is active (enabled in admin config).
         */
        isActive: function () {
            return true;
        },

        /**
         * Return the payment method title from config.
         */
        getTitle: function () {
            var config = window.checkoutConfig.payment.sadad || {};
            return config.title || 'SADAD Payment Gateway';
        },

        /**
         * Return the SADAD logo URL.
         */
        getLogoUrl: function () {
            var config = window.checkoutConfig.payment.sadad || {};
            return config.logoUrl || '';
        },

        /**
         * Return the checkout mode (v1.1 / v2.1 / v2.2).
         */
        getCheckoutMode: function () {
            var config = window.checkoutConfig.payment.sadad || {};
            return config.checkoutMode || 'v1.1';
        },

        /**
         * Return whether we are running in test/sandbox mode.
         */
        isTestMode: function () {
            var config = window.checkoutConfig.payment.sadad || {};
            return config.environment === 'test';
        },

        /**
         * Return the redirect URL used after order placement.
         */
        getRedirectUrl: function () {
            var config = window.checkoutConfig.payment.sadad || {};
            return config.redirectUrl || url.build('sadad/payment/redirect');
        },

        /**
         * Called after Magento has placed the order. Redirect to SADAD gateway.
         */
        afterPlaceOrder: function () {
            var self = this;
            self.isLoading(true);
            fullScreenLoader.startLoader();

            // Short delay so Magento can complete session writes before redirect
            setTimeout(function () {
                window.location.replace(self.getRedirectUrl());
            }, 300);
        },

        /**
         * Validate and place the order.
         */
        placeOrder: function (data, event) {
            var self = this;

            if (event) {
                event.preventDefault();
            }

            if (!additionalValidators.validate()) {
                return false;
            }

            self.isLoading(true);

            this._super(data, event);

            return true;
        },

        /**
         * Return additional payment data to send with the order.
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {}
            };
        }
    });
});
