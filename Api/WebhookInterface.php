<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Api;

/**
 * REST API interface for receiving SADAD webhook notifications.
 *
 * Endpoint: POST /rest/V1/sadad/webhook
 */
interface WebhookInterface
{
    /**
     * Receive and process a SADAD webhook notification.
     *
     * @return string JSON response with success/message.
     */
    public function receive(): string;
}
