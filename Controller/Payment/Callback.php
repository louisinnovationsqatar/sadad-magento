<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Controller\Payment;

use LouisInnovations\Sadad\Model\SadadPaymentMethod;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

/**
 * Handles the POST callback from the SADAD gateway after payment.
 *
 * Route: POST sadad/payment/callback
 *
 * SADAD posts the transaction result to this URL after the customer
 * completes (or cancels) payment. We parse the result and redirect
 * the customer to success or failure pages.
 */
class Callback implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly CheckoutSession $checkoutSession,
        private readonly SadadPaymentMethod $paymentMethod,
        private readonly OrderFactory $orderFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $postData = $this->request->getPostValue();

        $this->logger->info('[SADAD Callback] Received: ' . json_encode($postData));

        try {
            $config       = $this->paymentMethod->getSadadConfig();
            $checkoutMode = (string) ($this->paymentMethod->getConfigValue('checkout_mode') ?: 'v1.1');
            $client       = $this->paymentMethod->getSadadClient();
            $result       = $client->handleCallback($postData, $checkoutMode);
        } catch (LocalizedException $e) {
            $this->logger->error('[SADAD Callback] Config error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('Payment verification failed. Please contact support.'));
            return $this->redirectFactory->create()->setPath('checkout/cart');
        } catch (\Throwable $e) {
            $this->logger->error('[SADAD Callback] Unexpected error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('An error occurred processing your payment.'));
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        $this->logger->info(sprintf(
            '[SADAD Callback] Parsed: success=%s order=%s txn=%s code=%s msg=%s',
            $result->isSuccess ? 'true' : 'false',
            $result->orderNumber,
            $result->transactionNumber,
            $result->responseCode,
            $result->responseMessage
        ));

        if ($result->isSuccess) {
            return $this->handleSuccess($result);
        }

        return $this->handleFailure($result);
    }

    /**
     * Handle successful payment callback.
     */
    private function handleSuccess(\LouisInnovations\Sadad\Callback\CallbackResult $result): ResultInterface
    {
        $order = $this->loadOrder($result->orderNumber);

        if ($order && $order->getId()) {
            $payment = $order->getPayment();
            $payment->setTransactionId($result->transactionNumber);
            $payment->setAdditionalInformation('sadad_transaction_number', $result->transactionNumber);
            $payment->setAdditionalInformation('sadad_response_code', $result->responseCode);
            $payment->setAdditionalInformation('sadad_response_message', $result->responseMessage);
            $payment->setAdditionalInformation('sadad_status', $result->status);
            $payment->setAdditionalInformation('sadad_amount', $result->amount);
            $payment->setIsTransactionClosed(false);

            // Note: full order state update is handled by the webhook processor.
            // The callback merely records the transaction reference.
            $order->addCommentToStatusHistory(
                sprintf(
                    'SADAD callback received. Transaction: %s | Response: %s | Status: %s',
                    $result->transactionNumber,
                    $result->responseMessage,
                    $result->status
                )
            );

            try {
                $order->save();
            } catch (\Throwable $e) {
                $this->logger->error('[SADAD Callback] Order save failed: ' . $e->getMessage());
            }

            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
            $this->checkoutSession->setLastQuoteId($order->getQuoteId());
        }

        $this->messageManager->addSuccessMessage(__('Your payment was successful. Thank you for your order.'));
        return $this->redirectFactory->create()->setPath('checkout/onepage/success');
    }

    /**
     * Handle failed/cancelled payment callback.
     */
    private function handleFailure(\LouisInnovations\Sadad\Callback\CallbackResult $result): ResultInterface
    {
        $order = $this->loadOrder($result->orderNumber);

        if ($order && $order->getId()) {
            $order->addCommentToStatusHistory(
                sprintf(
                    'SADAD payment failed/cancelled. Code: %s | Message: %s',
                    $result->responseCode,
                    $result->responseMessage
                )
            );

            try {
                $order->save();
            } catch (\Throwable $e) {
                $this->logger->error('[SADAD Callback] Order save failed: ' . $e->getMessage());
            }
        }

        $this->messageManager->addErrorMessage(
            __('Payment was not completed. %1', $result->responseMessage)
        );
        return $this->redirectFactory->create()->setPath('checkout/cart');
    }

    /**
     * Load order by increment ID, stripping prefix if configured.
     */
    private function loadOrder(string $orderNumber): ?\Magento\Sales\Model\Order
    {
        $prefix = (string) ($this->paymentMethod->getConfigValue('order_prefix') ?: '');

        if ($prefix !== '' && str_starts_with($orderNumber, $prefix)) {
            $orderNumber = substr($orderNumber, strlen($prefix));
        }

        $order = $this->orderFactory->create();
        $order->loadByIncrementId($orderNumber);

        return $order->getId() ? $order : null;
    }

    /**
     * {@inheritdoc} - Disable CSRF validation for callback (SADAD POST is external).
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
