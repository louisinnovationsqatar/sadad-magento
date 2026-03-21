<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Controller\Payment;

use LouisInnovations\Sadad\Model\SadadPaymentMethod;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

/**
 * Redirects the customer to the SADAD payment gateway after order placement.
 *
 * Route: GET sadad/payment/redirect
 */
class Redirect implements HttpGetActionInterface, HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly CheckoutSession $checkoutSession,
        private readonly SadadPaymentMethod $paymentMethod,
        private readonly OrderFactory $orderFactory,
        private readonly PageFactory $pageFactory,
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
        $order = $this->checkoutSession->getLastRealOrder();

        if (!$order || !$order->getId()) {
            $this->messageManager->addErrorMessage(__('No order found. Please try again.'));
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        if ($order->getPayment()->getMethod() !== SadadPaymentMethod::CODE) {
            $this->messageManager->addErrorMessage(__('Payment method mismatch.'));
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        try {
            $redirect = $this->paymentMethod->buildCheckoutRedirect($order);
        } catch (LocalizedException $e) {
            $this->logger->error('[SADAD Redirect] ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->redirectFactory->create()->setPath('checkout/cart');
        } catch (\Throwable $e) {
            $this->logger->error('[SADAD Redirect] Unexpected error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('Could not redirect to payment gateway. Please try again.'));
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        // Render the auto-submit form page
        $page = $this->pageFactory->create();
        $page->addHandle('sadad_payment_redirect');

        // Pass the form data to the layout block via the registry/session
        $this->checkoutSession->setSadadRedirectData([
            'url'    => $redirect['url'],
            'params' => $redirect['params'],
            'html'   => $redirect['html'],
        ]);

        return $page;
    }
}
