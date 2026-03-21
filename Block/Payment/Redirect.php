<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Block\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Block for the redirect interstitial page shown while the auto-submit form is executing.
 */
class Redirect extends Template
{
    protected $_template = 'LouisInnovations_Sadad::payment/redirect.phtml';

    public function __construct(
        Context $context,
        private readonly CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Returns the redirect data (url, params, html) set during the controller action.
     *
     * @return array{url: string, params: array<string, mixed>, html: string}|null
     */
    public function getRedirectData(): ?array
    {
        $data = $this->checkoutSession->getSadadRedirectData();
        $this->checkoutSession->unsSadadRedirectData();
        return is_array($data) ? $data : null;
    }
}
