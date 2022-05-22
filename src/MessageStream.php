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

namespace Machinateur\SSE;

use Machinateur\SSE\Exception\InvalidArgumentException;
use Machinateur\SSE\Exception\TimeoutException;
use Machinateur\SSE\Format\StreamFormat;
use Machinateur\SSE\Message\MessageInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * A very basic, but sufficient, implementation of {@see MessageStreamInterface}.
 */
class MessageStream implements MessageStreamInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var resource
     */
    protected $stream;

    /**
     * @var bool
     */
    protected $streamIsInternal = false;

    /**
     * Construct a new {@see MessageStream} object. If no external stream resource for the output is provided, the
     *  default one (`php://output`) will be used. That one would be marked as internal and closed when the destructor
     *  is called. To configure a logger, refer to {@see MessageStream::setLogger()}.
     *
     * @link https://www.php.net/manual/en/wrappers.php.php#wrappers.php.output
     *
     * @param resource|null $stream
     */
    public function __construct($stream = null)
    {
        if (null === $stream) {
            $stream = \fopen('php://output', 'w');

            $this->streamIsInternal = true;
        }

        if (!\is_resource($stream)) {
            throw InvalidArgumentException::causedByNoOrInvalidStreamResource($this->getActualType($stream));
        }

        $this->stream = $stream;

        $this->setLogger(new NullLogger());
    }

    public function __destruct()
    {
        if ($this->streamIsInternal) {
            \fclose($this->stream);
        }
    }

    /**
     * Return the recommended HTTP headers for this message stream.
     *
     * @return string[]
     */
    public static function getRecommendedHeaders()
    {
        return [
            'Content-Type: text/event-stream',
            'Cache-Control: no-cache',
            'Connection: keep-alive',
            // See https://www.nginx.com/resources/wiki/start/topics/examples/x-accel/#x-accel-buffering.
            'X-Accel-Buffering: no',
        ];
    }

    /**
     * @inheritDoc
     */
    public function run(callable $callback)
    {
        $arrayOrGenerator = $callback();

        if (\is_array($arrayOrGenerator)) {
            $generator = $this->toGenerator($arrayOrGenerator);
        } elseif ($arrayOrGenerator instanceof \Generator) {
            /** @var \Generator $generator */
            $generator = $arrayOrGenerator;
        } else {
            throw InvalidArgumentException::causedByNeitherArrayNorGeneratorReturnedFromCallback(
                $this->getActualType($arrayOrGenerator));
        }

        while ($generator->valid()) {
            try {
                // This will execute to first yield of generator...
                $messageOrArray = $generator->current();

                if (\is_array($messageOrArray)) {
                    $array = $messageOrArray;
                } elseif ($messageOrArray instanceof MessageInterface) {
                    $array = $messageOrArray->getStreamFormat();
                } else {
                    throw InvalidArgumentException::causedByNeitherArrayNorMessageReturnedFromGenerator(
                        $this->getActualType($messageOrArray));
                }

                $this->printOutput($array);
                $this->checkConnection();

                // ... while this will take over after first yield.
                $generator->next();
            } catch (TimeoutException $exception) {
                $this->logger->notice(sprintf('Shutdown signal received. Cause: %s', $exception->getMessage()));

                break;
            }
        }
    }

    /**
     * Write the output to the output buffer and flush it.
     *
     * @param array $array
     * @return void
     */
    protected function printOutput(array $array)
    {
        $lines = StreamFormat::formatLines($array);

        foreach ($lines as $line) {
            $line = $line . StreamFormat::getLineSeparator();

            $falseOnError = \fwrite($this->stream, $line);

            if (false === $falseOnError) {
                throw TimeoutException::causedByOutputError();
            }
        }

        $this->logger->debug('New output received. Output: ' . print_r($lines, true));

        if (\ob_get_level() > 0) {
            \ob_flush();
        }

        \flush();
    }

    /**
     * Check if the connection is still open, otherwise throw a {@see TimeoutException} to trigger shutdown.
     *
     * @return void
     */
    protected function checkConnection()
    {
        if (\connection_aborted()) {
            throw TimeoutException::causedByConnectionError();
        }
    }

    /**
     * Create a generator from the passed array.
     *
     * @param array $array
     * @return \Generator
     */
    protected final function toGenerator(array $array)
    {
        foreach ($array as $value) {
            yield $value;
        }
    }

    /**
     * Determine the actual type of the passed value, if it's an object, the objects class.
     *
     * @param mixed $value
     * @return string
     */
    protected final function getActualType($value)
    {
        return \is_object($value) ? \get_class($value) : \gettype($value);
    }
}
