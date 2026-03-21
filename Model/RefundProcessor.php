<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Psr\Log\LoggerInterface;

/**
 * Handles full refunds for SADAD-paid orders.
 *
 * SADAD supports full refunds only; partial refunds are not available.
 * This processor creates a Magento credit memo and triggers the SDK refund.
 */
class RefundProcessor
{
    public function __construct(
        private readonly SadadPaymentMethod $paymentMethod,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly CreditmemoFactory $creditmemoFactory,
        private readonly CreditmemoService $creditmemoService,
        private readonly CreditmemoRepositoryInterface $creditmemoRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Process a full refund for a SADAD order.
     *
     * @param  int    $orderId    Magento order ID.
     * @param  string $comment    Optional comment to attach to the credit memo.
     * @return Creditmemo
     *
     * @throws LocalizedException
     */
    public function refundOrder(int $orderId, string $comment = ''): Creditmemo
    {
        /** @var Order $order */
        $order = $this->orderRepository->get($orderId);

        if (!$order->getId()) {
            throw new LocalizedException(__('Order ID %1 not found.', $orderId));
        }

        $payment = $order->getPayment();

        if ($payment->getMethod() !== SadadPaymentMethod::CODE) {
            throw new LocalizedException(__('Order was not paid with SADAD. Cannot refund via SADAD.'));
        }

        $transactionNumber = $payment->getAdditionalInformation('sadad_transaction_number');

        if (!$transactionNumber) {
            throw new LocalizedException(__('No SADAD transaction number on record. Cannot process refund.'));
        }

        // Verify the order is in a refundable state
        if (!in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE], true)) {
            throw new LocalizedException(
                __('Order cannot be refunded in its current state: %1', $order->getState())
            );
        }

        $storeId = (int) $order->getStoreId();

        $this->log('Starting refund for order ' . $order->getIncrementId() . ' txn=' . $transactionNumber);

        // Issue refund via SADAD SDK
        $refundResult = $this->paymentMethod->processRefund($transactionNumber, $storeId);

        if (empty($refundResult['success'])) {
            $error = $refundResult['error'] ?? 'Unknown error';
            $this->logError('SDK refund failed: ' . $error);
            throw new LocalizedException(__('SADAD refund failed: %1', $error));
        }

        $this->log('SDK refund successful for txn=' . $transactionNumber);

        // Create Magento credit memo
        $creditmemo = $this->createCreditMemo($order, $comment, $transactionNumber);

        $this->log('Credit memo created: ' . $creditmemo->getIncrementId());

        return $creditmemo;
    }

    /**
     * Create a Magento credit memo for the full order amount.
     *
     * @throws LocalizedException
     */
    private function createCreditMemo(
        Order $order,
        string $comment,
        string $transactionNumber
    ): Creditmemo {
        // Get the last invoice
        $invoicesCollection = $order->getInvoiceCollection();
        $invoice = $invoicesCollection->getFirstItem();

        if (!$invoice->getId()) {
            throw new LocalizedException(__('No invoice found for this order. Credit memo cannot be created.'));
        }

        $creditmemoData = [
            'qtys'           => [],
            'shipping_amount' => 0,
            'adjustment_positive' => 0,
            'adjustment_negative' => 0,
        ];

        $creditmemo = $this->creditmemoFactory->createByInvoice($invoice, $creditmemoData);
        $creditmemo->setPaymentRefundDisallowed(false);

        $refundComment = sprintf(
            'Refund processed via SADAD gateway. Transaction: %s.',
            $transactionNumber
        );

        if ($comment) {
            $refundComment .= ' ' . $comment;
        }

        $creditmemo->addComment($refundComment, false, false);

        $this->creditmemoService->refund($creditmemo, true);

        // Store refund details on the payment
        $payment = $order->getPayment();
        $payment->setAdditionalInformation('sadad_refunded', '1');
        $payment->setAdditionalInformation('sadad_refund_transaction', $transactionNumber . '-refund');

        $order->addCommentToStatusHistory(
            'SADAD full refund issued. Transaction: ' . $transactionNumber
        );
        $this->orderRepository->save($order);

        return $creditmemo;
    }

    private function log(string $message): void
    {
        $this->logger->info('[SADAD Refund] ' . $message);
    }

    private function logError(string $message): void
    {
        $this->logger->error('[SADAD Refund] ' . $message);
    }
}
