<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for the SADAD checkout mode selector.
 *
 * v1.1 - Standard redirect checkout
 * v2.1 - Enhanced redirect checkout with multi-product support
 * v2.2 - Embedded/hosted secure checkout page
 */
class CheckoutMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'v1.1',
                'label' => __('v1.1 - Standard Redirect Checkout'),
            ],
            [
                'value' => 'v2.1',
                'label' => __('v2.1 - Enhanced Redirect Checkout (multi-product)'),
            ],
            [
                'value' => 'v2.2',
                'label' => __('v2.2 - Embedded / Hosted Secure Checkout'),
            ],
        ];
    }
}
