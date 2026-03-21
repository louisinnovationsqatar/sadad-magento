<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Model;

use LouisInnovations\Sadad\Webhook\WebhookResult;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Processes incoming SADAD webhook notifications.
 *
 * Validates the webhook payload via the SDK and updates the Magento
 * order status accordingly. Idempotent: safe to call multiple times
 * for the same transaction.
 */
class WebhookProcessor
{
    public function __construct(
        private readonly SadadPaymentMethod $paymentMethod,
        private readonly OrderFactory $orderFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderSender $orderSender,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Process a SADAD webhook payload.
     *
     * @param  array<string, mixed> $payload  Raw webhook POST data.
     * @param  int|null             $storeId
     * @return array{success: bool, message: string}
     */
    public function process(array $payload, ?int $storeId = null): array
    {
        $this->log('Webhook received: ' . json_encode($payload));

        try {
            $client = $this->paymentMethod->getSadadClient($storeId);
            $result = $client->handleWebhook($payload);
        } catch (\Throwable $e) {
            $this->logError('Webhook SDK error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Webhook processing error: ' . $e->getMessage()];
        }

        $this->log(sprintf(
            'Webhook parsed: success=%s txn=%s order=%s amount=%s testMode=%s',
            $result->isSuccess ? 'true' : 'false',
            $result->transactionNumber,
            $result->orderNumber,
            $result->amount,
            $result->isTestMode ? 'true' : 'false'
        ));

        if (!$result->isSuccess) {
            $this->log('Webhook indicates failed/pending payment for order ' . $result->orderNumber);
            $this->handleFailedPayment($result);
            return ['success' => true, 'message' => 'Webhook acknowledged (payment not successful).'];
        }

        try {
            $this->handleSuccessfulPayment($result);
        } catch (\Throwable $e) {
            $this->logError('Order update failed for ' . $result->orderNumber . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'Order update failed: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Payment processed successfully.'];
    }

    /**
     * Handle a successful payment notification.
     *
     * @throws LocalizedException
     */
    private function handleSuccessfulPayment(WebhookResult $result): void
    {
        $order = $this->loadOrderByIncrementId($result->orderNumber);

        if (!$order->getId()) {
            throw new LocalizedException(__('Order not found: %1', $result->orderNumber));
        }

        // Guard: do not process an already-paid order twice
        if ($order->getState() === Order::STATE_PROCESSING
            || $order->getState() === Order::STATE_COMPLETE
        ) {
            $this->log('Order ' . $result->orderNumber . ' already processed, skipping.');
            return;
        }

        $payment = $order->getPayment();
        $payment->setTransactionId($result->transactionNumber);
        $payment->setAdditionalInformation('sadad_transaction_number', $result->transactionNumber);
        $payment->setAdditionalInformation('sadad_amount', $result->amount);
        $payment->setAdditionalInformation('sadad_message', $result->message);
        $payment->setAdditionalInformation('sadad_merchant_id', $result->merchantId);
        $payment->setAdditionalInformation('sadad_test_mode', $result->isTestMode ? '1' : '0');

        if ($result->invoiceNumber) {
            $payment->setAdditionalInformation('sadad_invoice_number', $result->invoiceNumber);
        }

        $payment->setIsTransactionClosed(false);
        $payment->registerCaptureNotification($result->amount, true);

        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus($this->getOrderStatus($order->getStoreId()));
        $order->addCommentToStatusHistory(
            sprintf(
                'SADAD payment confirmed via webhook. Transaction: %s | Amount: %s QAR%s',
                $result->transactionNumber,
                number_format($result->amount, 2),
                $result->isTestMode ? ' [TEST MODE]' : ''
            )
        );

        $this->orderRepository->save($order);

        // Send order confirmation email if not already sent
        if (!$order->getEmailSent()) {
            try {
                $this->orderSender->send($order);
            } catch (\Throwable $e) {
                $this->logError('Order email send failed: ' . $e->getMessage());
            }
        }

        $this->log('Order ' . $result->orderNumber . ' marked as processing.');
    }

    /**
     * Handle a failed/cancelled payment notification.
     */
    private function handleFailedPayment(WebhookResult $result): void
    {
        $order = $this->loadOrderByIncrementId($result->orderNumber);

        if (!$order->getId()) {
            $this->logError('Order not found for failed payment: ' . $result->orderNumber);
            return;
        }

        if ($order->getState() === Order::STATE_PENDING_PAYMENT
            || $order->getState() === Order::STATE_NEW
        ) {
            $order->registerCancellation(
                'SADAD payment failed/declined. Message: ' . $result->message
            );
            $this->orderRepository->save($order);
            $this->log('Order ' . $result->orderNumber . ' cancelled due to failed payment.');
        }
    }

    /**
     * Load an order by increment ID, stripping any configured prefix.
     */
    private function loadOrderByIncrementId(string $orderNumber): Order
    {
        // Strip the configured prefix if present
        $prefix = (string) $this->scopeConfig->getValue(
            'payment/' . SadadPaymentMethod::CODE . '/order_prefix',
            ScopeInterface::SCOPE_STORE
        );

        if ($prefix !== '' && str_starts_with($orderNumber, $prefix)) {
            $orderNumber = substr($orderNumber, strlen($prefix));
        }

        /** @var Order $order */
        $order = $this->orderFactory->create();
        $order->loadByIncrementId($orderNumber);

        return $order;
    }

    private function getOrderStatus(?int $storeId = null): string
    {
        return (string) ($this->scopeConfig->getValue(
            'payment/' . SadadPaymentMethod::CODE . '/order_status',
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'processing');
    }

    private function log(string $message): void
    {
        $loggingEnabled = (bool) $this->scopeConfig->getValue(
            'payment/' . SadadPaymentMethod::CODE . '/logging',
            ScopeInterface::SCOPE_STORE
        );

        if ($loggingEnabled) {
            $this->logger->info('[SADAD Webhook] ' . $message);
        }
    }

    private function logError(string $message): void
    {
        $this->logger->error('[SADAD Webhook] ' . $message);
    }
}
