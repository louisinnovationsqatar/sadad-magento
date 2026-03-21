<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Admin system config field block that renders the Callback URL as a read-only input.
 */
class CallbackUrl extends Field
{
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $url = $this->getBaseUrl() . 'sadad/payment/callback';

        return sprintf(
            '<input type="text" readonly="readonly" class="input-text" style="width:100%%;background:#f8f8f8;" value="%s" onclick="this.select();" />
            <p class="note"><span>%s</span></p>',
            htmlspecialchars($url, ENT_QUOTES),
            __('This URL is automatically configured as the CALLBACK_URL for all SADAD transactions.')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    private function getBaseUrl(): string
    {
        try {
            return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        } catch (\Throwable $e) {
            return '/';
        }
    }
}
