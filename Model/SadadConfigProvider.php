<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Provides SADAD configuration to the frontend JS checkout component.
 */
class SadadConfigProvider implements ConfigProviderInterface
{
    private const METHOD_CODE = SadadPaymentMethod::CODE;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly UrlInterface $urlBuilder,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'payment' => [
                self::METHOD_CODE => [
                    'redirectUrl'  => $this->urlBuilder->getUrl('sadad/payment/redirect'),
                    'title'        => $this->getConfigValue('title'),
                    'checkoutMode' => $this->getConfigValue('checkout_mode'),
                    'environment'  => $this->getConfigValue('environment'),
                    'logoUrl'      => $this->getLogoUrl(),
                ],
            ],
        ];
    }

    private function isActive(): bool
    {
        return (bool) $this->getConfigValue('active');
    }

    private function getConfigValue(string $key): mixed
    {
        return $this->scopeConfig->getValue(
            'payment/' . self::METHOD_CODE . '/' . $key,
            ScopeInterface::SCOPE_STORE
        );
    }

    private function getLogoUrl(): string
    {
        return $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_STATIC])
            . 'LouisInnovations_Sadad/images/sadad-logo.svg';
    }
}
