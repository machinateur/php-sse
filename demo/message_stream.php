<?php
/*
 * MIT License
 *
 * Copyright (c) 2021-2022 machinateur
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace App;

if (!extension_loaded('json')) {
    throw new \RuntimeException('The "json" extension is missing!');
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Machinateur\SSE\Format\StreamFormat;
use Machinateur\SSE\Message\Comment;
use Machinateur\SSE\MessageStream;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

date_default_timezone_set('Europe/Berlin');

$waitTimeInSeconds = isset($_GET['wait']) ? (int)$_GET['wait'] : 1;

/**
 * A logger implementation that stores log messages internally.
 */
class SimpleMemoryLogger extends AbstractLogger
{
    /**
     * A list of all the allowed log levels.
     *
     * @var string[]
     */
    private $allowedLogLevels;

    /**
     * The action level threshold (minimum log level to log a message).
     *
     * @var string
     */
    private $actionLevel;

    /**
     * A list of all the log messages currently due for flush.
     *
     * @var string[]
     */
    private $logMessages = array();

    /**
     * The maximum size of the internal log message storage array. If set to less than or equal to `0`, no messages will
     *  be retained.
     *
     * *Keep in mind that the storage array is used to buffer log messages for the next yield!*
     *
     * @var int
     */
    private $maxBufferSize;

    /**
     * Create a new logger that stores log messages internally.
     *
     * @param string $actionLevel
     * @param int $maxBufferSize
     */
    public function __construct($actionLevel, $maxBufferSize = 10)
    {
        $ref = new \ReflectionClass(LogLevel::class);

        $this->allowedLogLevels = array_values($ref->getConstants());
        $this->actionLevel = $actionLevel;
        $this->maxBufferSize = $maxBufferSize;
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = array())
    {
        if (!\is_string($level) || !\in_array($level, $this->allowedLogLevels, true)) {
            throw new InvalidArgumentException(\sprintf('The log level must be a string and represent a valid '
                . 'log level! Valid log levels are: [%s], given: %s', \implode(', ', $this->allowedLogLevels), $level));
        }

        $message = \sprintf('[%s] %s: %s', $level, \date('Y-m-d H:i:s'), $message);

        if ($this->hasBuffer()) {
            $this->logMessages[] = $message;
        }

        if ($this->maxBufferSize > \count($this->logMessages)) {
            unset($this->logMessages[\key($this->logMessages)]);
        }
    }

    /**
     * @return string
     */
    public function getActionLevel()
    {
        return $this->actionLevel;
    }

    /**
     * @param string $actionLevel
     */
    public function setActionLevel($actionLevel)
    {
        $this->actionLevel = $actionLevel;
    }

    /**
     * @return string[]
     */
    public function flushLogMessages()
    {
        $logMessages = $this->logMessages;
        if ($this->hasLogMessages()) {
            $this->logMessages = array();
        }
        return $logMessages;
    }

    /**
     * @return bool
     */
    public function hasLogMessages()
    {
        return 0 === count($this->logMessages);
    }

    /**
     * @return bool
     */
    public function hasBuffer()
    {
        return 0 < $this->maxBufferSize;
    }
}

$logger = new SimpleMemoryLogger(LogLevel::DEBUG);

class LoggingMessageStream extends MessageStream
{
    /**
     * @param LoggerInterface $logger
     * @param resource|null $stream
     */
    public function __construct($logger, $stream = null)
    {
        parent::__construct($stream);

        $this->setLogger($logger);
    }

    /**
     * @inheritDoc
     */
    protected function printOutput(array $array)
    {
        parent::printOutput($array);

        if ($this->logger instanceof SimpleMemoryLogger) {
            $logger = $this->logger;

            if ($logger->hasBuffer() && $logger->hasLogMessages()) {
                foreach ($logger->flushLogMessages() as $logMessage) {
                    parent::printOutput([
                        StreamFormat::FIELD_COMMENT => $logger,
                    ]);
                }

                // Clear the debug log messages from calling `printOutput()`.
                $logger->flushLogMessages();
            }
        }
    }
}

foreach (MessageStream::getRecommendedHeaders() as $header) {
    \header($header);
}

$messageStream = new LoggingMessageStream($logger);
$messageStream->run(function () use ($waitTimeInSeconds, $logger) {
    $comment = new Comment();
    $comment->setComment('Welcome!');
    yield $comment;

    $content = \file_get_contents(\dirname(__DIR__) . '/demo/message_stream.json');

    if (false !== $content) {
        $contentData = \json_decode($content, true);

        foreach ($contentData as $data) {
            yield $data;

            \sleep($waitTimeInSeconds);
        }
    }
});
