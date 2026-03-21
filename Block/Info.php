<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Block;

use Magento\Payment\Block\Info as BaseInfo;

/**
 * Payment info block displayed in order detail / invoice views.
 */
class Info extends BaseInfo
{
    protected $_template = 'LouisInnovations_Sadad::payment/info.phtml';

    /**
     * {@inheritdoc}
     */
    public function toPdf(): string
    {
        $this->setTemplate('LouisInnovations_Sadad::payment/info/pdf.phtml');
        return $this->toHtml();
    }

    /**
     * Get the SADAD transaction number stored on the payment.
     */
    public function getTransactionNumber(): string
    {
        return (string) ($this->getInfo()->getAdditionalInformation('sadad_transaction_number') ?? '');
    }

    /**
     * Get the SADAD response message.
     */
    public function getResponseMessage(): string
    {
        return (string) ($this->getInfo()->getAdditionalInformation('sadad_response_message') ?? '');
    }

    /**
     * Get the SADAD invoice number (if any).
     */
    public function getInvoiceNumber(): string
    {
        return (string) ($this->getInfo()->getAdditionalInformation('sadad_invoice_number') ?? '');
    }

    /**
     * Returns true when the payment was made in test mode.
     */
    public function isTestMode(): bool
    {
        return (bool) ($this->getInfo()->getAdditionalInformation('sadad_test_mode') ?? false);
    }
}
