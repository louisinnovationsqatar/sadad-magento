<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Controller\Webhook;

use LouisInnovations\Sadad\Model\WebhookProcessor;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;

/**
 * Webhook receiver for SADAD payment notifications.
 *
 * Route: POST sadad/webhook/index
 *        Also accessible via REST: POST /rest/V1/sadad/webhook
 *
 * SADAD sends JSON or form-encoded notifications to this endpoint
 * when payment events occur (success, failure, refund, etc.).
 */
class Index implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly WebhookProcessor $webhookProcessor,
        private readonly JsonFactory $jsonFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $jsonResult = $this->jsonFactory->create();

        // Accept both JSON body and form-encoded POST
        $rawBody = $this->request->getContent();
        $payload = [];

        if ($rawBody) {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $payload = $decoded;
            }
        }

        // Fall back to POST params if body was not JSON
        if (empty($payload)) {
            $payload = $this->request->getPostValue();
        }

        if (empty($payload)) {
            $this->logger->warning('[SADAD Webhook] Empty payload received.');
            return $jsonResult->setHttpResponseCode(400)->setData([
                'success' => false,
                'message' => 'Empty payload.',
            ]);
        }

        $result = $this->webhookProcessor->process($payload);

        $httpCode = $result['success'] ? 200 : 422;

        return $jsonResult->setHttpResponseCode($httpCode)->setData($result);
    }

    /**
     * {@inheritdoc} - Disable CSRF validation for webhook (external POST from SADAD).
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
