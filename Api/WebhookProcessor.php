<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Api;

use LouisInnovations\Sadad\Model\WebhookProcessor as WebhookProcessorModel;
use Magento\Framework\App\RequestInterface;

/**
 * REST API implementation for the SADAD webhook endpoint.
 */
class WebhookProcessor implements WebhookInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly WebhookProcessorModel $webhookProcessor,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function receive(): string
    {
        $rawBody = $this->request->getContent();
        $payload = [];

        if ($rawBody) {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $payload = $decoded;
            }
        }

        if (empty($payload)) {
            $payload = $this->request->getPostValue();
        }

        $result = $this->webhookProcessor->process($payload);

        return json_encode($result);
    }
}
