<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Model;

use LouisInnovations\Sadad\SadadClient;
use LouisInnovations\Sadad\SadadConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * SADAD Payment Method for Magento 2.
 *
 * Handles order placement, redirect construction, and full refunds
 * via the louis-innovations/sadad-php-sdk library.
 */
class SadadPaymentMethod extends AbstractMethod
{
    public const CODE = 'sadad';

    protected $_code = self::CODE;

    protected $_isOffline = false;

    protected $_canOrder = true;

    protected $_canAuthorize = false;

    protected $_canCapture = false;

    protected $_canCapturePartial = false;

    protected $_canRefund = true;

    protected $_canRefundInvoicePartial = false;

    protected $_canVoid = false;

    protected $_canUseInternal = true;

    protected $_canUseCheckout = true;

    protected $_canFetchTransactionInfo = false;

    protected $_isInitializeNeeded = true;

    private ScopeConfigInterface $scopeConfig;

    private UrlInterface $urlBuilder;

    private LoggerInterface $logger;

    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $methodLogger,
        UrlInterface $urlBuilder,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $methodLogger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->scopeConfig     = $scopeConfig;
        $this->urlBuilder      = $urlBuilder;
        $this->logger          = $logger;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Return config value by key.
     */
    public function getConfigValue(string $key, ?int $storeId = null): mixed
    {
        return $this->scopeConfig->getValue(
            'payment/' . self::CODE . '/' . $key,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Build a SadadConfig instance from Magento configuration.
     *
     * @throws LocalizedException
     */
    public function getSadadConfig(?int $storeId = null): SadadConfig
    {
        $merchantId = (string) $this->getConfigValue('merchant_id', $storeId);
        $secretKey  = (string) $this->getConfigValue('secret_key', $storeId);
        $website    = (string) $this->getConfigValue('website', $storeId);
        $environment = (string) ($this->getConfigValue('environment', $storeId) ?: 'test');
        $language    = (string) ($this->getConfigValue('language', $storeId) ?: 'eng');

        $callbackUrl = $this->urlBuilder->getUrl('sadad/payment/callback');
        $webhookUrl  = $this->urlBuilder->getUrl('rest/V1/sadad/webhook');

        try {
            return new SadadConfig(
                merchantId:  $merchantId,
                secretKey:   $secretKey,
                website:     $website,
                environment: $environment,
                language:    $language,
                callbackUrl: $callbackUrl,
                webhookUrl:  $webhookUrl,
            );
        } catch (\InvalidArgumentException $e) {
            throw new LocalizedException(__('SADAD configuration error: %1', $e->getMessage()));
        }
    }

    /**
     * Build a SadadClient instance.
     *
     * @throws LocalizedException
     */
    public function getSadadClient(?int $storeId = null): SadadClient
    {
        return new SadadClient($this->getSadadConfig($storeId));
    }

    /**
     * Build the checkout redirect URL and parameters for the given order.
     *
     * @param  Order $order
     * @return array{url: string, params: array<string, mixed>}
     *
     * @throws LocalizedException
     */
    public function buildCheckoutRedirect(Order $order): array
    {
        $storeId = (int) $order->getStoreId();
        $client  = $this->getSadadClient($storeId);
        $mode    = (string) ($this->getConfigValue('checkout_mode', $storeId) ?: 'v1.1');
        $prefix  = (string) ($this->getConfigValue('order_prefix', $storeId) ?: '');

        $billingAddress = $order->getBillingAddress();
        $mobile         = $billingAddress ? preg_replace('/\D/', '', (string) $billingAddress->getTelephone()) : '';
        $email          = $order->getCustomerEmail() ?? '';

        // Build items list from order items
        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = [
                'order_id' => $prefix . $order->getIncrementId() . '-' . (int) $item->getItemId(),
                'amount'   => round((float) $item->getRowTotal(), 2),
                'quantity' => (int) $item->getQtyOrdered(),
            ];
        }

        // Fallback: single item representing the full order total
        if (empty($items)) {
            $items[] = [
                'order_id' => $prefix . $order->getIncrementId(),
                'amount'   => round((float) $order->getGrandTotal(), 2),
                'quantity' => 1,
            ];
        }

        $orderData = [
            'order_id'     => $prefix . $order->getIncrementId(),
            'amount'       => round((float) $order->getGrandTotal(), 2),
            'mobile'       => $mobile,
            'email'        => $email,
            'items'        => $items,
            'callback_url' => $this->urlBuilder->getUrl('sadad/payment/callback'),
        ];

        $this->logDebug('buildCheckoutRedirect order_id=' . $orderData['order_id'] . ' mode=' . $mode);

        try {
            $result = $client->checkout($orderData, $mode);
        } catch (\Throwable $e) {
            $this->logError('checkout failed: ' . $e->getMessage());
            throw new LocalizedException(__('Could not initiate SADAD payment: %1', $e->getMessage()));
        }

        return [
            'url'    => $result->url,
            'params' => $result->params,
            'html'   => $result->toHtmlForm('sadad-checkout-form', false),
        ];
    }

    /**
     * Process a full refund via the SDK.
     *
     * @param  string $transactionNumber SADAD transaction number.
     * @param  int    $storeId
     * @return array<string, mixed>
     *
     * @throws LocalizedException
     */
    public function processRefund(string $transactionNumber, int $storeId): array
    {
        $client = $this->getSadadClient($storeId);

        $this->logDebug('processRefund txn=' . $transactionNumber);

        try {
            $result = $client->refund($transactionNumber);
        } catch (\LouisInnovations\Sadad\Exceptions\RefundException $e) {
            $this->logError('RefundException: ' . $e->getMessage() . ' code=' . $e->getCode());
            throw new LocalizedException(__('SADAD refund error: %1', $e->getMessage()));
        } catch (\Throwable $e) {
            $this->logError('refund unexpected error: ' . $e->getMessage());
            throw new LocalizedException(__('Refund could not be processed: %1', $e->getMessage()));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize($paymentAction, $stateObject)
    {
        $stateObject->setState(Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function refund(InfoInterface $payment, $amount)
    {
        /** @var Order $order */
        $order           = $payment->getOrder();
        $storeId         = (int) $order->getStoreId();
        $transactionNumber = $payment->getAdditionalInformation('sadad_transaction_number');

        if (!$transactionNumber) {
            throw new LocalizedException(__('SADAD transaction number not found. Cannot process refund.'));
        }

        $result = $this->processRefund($transactionNumber, $storeId);

        if (empty($result['success'])) {
            $error = $result['error'] ?? 'Unknown refund error';
            throw new LocalizedException(__('SADAD refund failed: %1', $error));
        }

        $payment->setTransactionId($transactionNumber . '-refund');
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
        $payment->setAdditionalInformation('sadad_refund_details', json_encode($result['refund_details'] ?? []));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->getConfigValue('merchant_id') || !$this->getConfigValue('secret_key')) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    private function logDebug(string $message): void
    {
        if ($this->getConfigValue('debug') || $this->getConfigValue('logging')) {
            $this->logger->debug('[SADAD] ' . $message);
        }
    }

    private function logError(string $message): void
    {
        $this->logger->error('[SADAD] ' . $message);
    }
}
