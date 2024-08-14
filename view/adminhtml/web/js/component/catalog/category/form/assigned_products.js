/**
 * DISCLAIMER
 *
 * This is a derivative work from Smile's ElasticsuiteVirtualCategory module.
 * Original credits: Aurelien FOUCRET <aurelien.foucret@smile.fr> 2020 Smile
 *
 * @category  Ep
 * @package   CategoryDragSort
 * @author    Enzo PERROTTA <enzo.perrotta@gmail.com>
 * @copyright 2024 Enzo PERROTTA
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'Magento_Ui/js/form/components/html',
    'underscore',
    'Ep_CategoryDragSort/js/MutationObserver'
], function (Component, _) {
    'use strict';

    return Component.extend({
        defaults: {
            formField: "in_category_products",
            visualMerchandiserFormField: "vm_category_products",
            links: {
                addedProducts: '${ $.provider }:${ $.dataScope }.added_products',
                deletedProducts: '${ $.provider }:${ $.dataScope }.deleted_products'
            }
        },
        initialize: function () {
            this._super();
            this.initAssignedProductsListener();
        },

        initObservable: function () {
            this._super();
            this.addedProducts   = {};
            this.deletedProducts = {};
            this.observe('addedProducts');
            this.observe('deletedProducts');

            return this;
        },

        initAssignedProductsListener: function () {
            var observer = new MutationObserver(function () {
                // Change listened field when visual merchandiser is enabled
                var selectedProductsField = document.getElementById(this.formField)
                    ? document.getElementById(this.formField)
                    : document.getElementById(this.visualMerchandiserFormField);
                if (selectedProductsField) {
                    observer.disconnect();
                    observer = new MutationObserver(this.onProductIdsUpdated.bind(this));
                    observerConfig = {attributes: true, attributeFilter: ['value']};
                    observer.observe(selectedProductsField, observerConfig);

                    // Initialize initialProductIds in visual merchandising mode
                    if (!document.getElementById(this.formField)) {
                        let selectedProductsFieldVal = selectedProductsField.value ? selectedProductsField.value : '{}';
                        this.initialProductIds = Object.keys(JSON.parse(selectedProductsFieldVal));
                    }
                }
            }.bind(this));

            var observerConfig = {childList: true, subtree: true};
            observer.observe(document, observerConfig);
        },

        onProductIdsUpdated: function (mutations) {
            while (mutations.length > 0) {
                var currentMutation = mutations.shift();
                var productIds = Object.keys(JSON.parse(currentMutation.target.value));
                this.updateProductIds(productIds);
            }
        },

        updateProductIds: function (productIds) {
            if (this.initialProductIds === undefined) {
                this.initialProductIds = productIds;
            } else {
                this.addedProducts(_.difference(productIds, this.initialProductIds));
                this.deletedProducts(_.difference(this.initialProductIds, productIds));
            }
        }
    })
});
