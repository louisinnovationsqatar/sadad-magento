<?php
// Built by Louis Innovations (www.louis-innovations.com)

declare(strict_types=1);

namespace LouisInnovations\Sadad\Model\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Log handler that writes SADAD log entries to var/log/sadad.log.
 */
class Handler extends Base
{
    protected $loggerType = Logger::DEBUG;

    protected $fileName = '/var/log/sadad.log';
}
