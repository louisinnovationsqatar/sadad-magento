<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for the SADAD environment selector.
 */
class Environment implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'test', 'label' => __('Test (Sandbox)')],
            ['value' => 'live', 'label' => __('Live (Production)')],
        ];
    }
}
