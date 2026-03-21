<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Renders the "Built by Louis Innovations" footer in admin config.
 */
class BuiltBy extends Field
{
    /**
     * {@inheritdoc}
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return '<div style="padding:10px 0; border-top:1px solid #e0e0e0; margin-top:10px; color:#666; font-size:12px;">'
            . '<strong>SADAD Payment Gateway</strong> &mdash; '
            . 'Built by <a href="https://www.louis-innovations.com" target="_blank" style="color:#007bdb;">Louis Innovations</a>'
            . ' | <a href="https://github.com/louis-innovations/sadad-magento" target="_blank" style="color:#007bdb;">GitHub</a>'
            . ' | <a href="mailto:info@louis-innovations.com" style="color:#007bdb;">Support</a>'
            . '</div>';
    }

    /**
     * {@inheritdoc}
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
