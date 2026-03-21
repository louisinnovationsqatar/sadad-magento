<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for the SADAD gateway language selector.
 */
class Language implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'eng', 'label' => __('English')],
            ['value' => 'arb', 'label' => __('Arabic')],
        ];
    }
}
