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

namespace Machinateur\SSE\Exception;

use Machinateur\SSE\MessageStream;

/**
 * All exceptions related to invalid arguments passed around or into {@see MessageStream}.
 */
class InvalidArgumentException extends \InvalidArgumentException
{
    /**
     * The exception thrown when no or an invalid stream resource was passed to the constructor of {@see MessageStream}.
     *
     * @param string $actualType
     * @return InvalidArgumentException
     */
    public static function causedByNoOrInvalidStreamResource($actualType)
    {
        return new self(\sprintf('The argument passed to "%s::__construct()" must be a stream resource, got "%s" instead!',
            MessageStream::class, $actualType));
    }

    /**
     * The exception thrown when the callback does not return an array not is a generator function itself (and thus
     *  returns a new instance of {@see \Generator}).
     *
     * @param string $actualType
     * @return InvalidArgumentException
     */
    public static function causedByNeitherArrayNorGeneratorReturnedFromCallback($actualType)
    {
        return new self(\sprintf('The callback passed to "%s::run()" must either return an array or itself be a '
            . 'generator function, but got "%s" instead!', MessageStream::class, $actualType));
    }

    /**
     * The exception thrown when the generator does not return a message object or an SSE representation as PHP `array`.
     *
     * @param string $actualType
     * @return InvalidArgumentException
     */
    public static function causedByNeitherArrayNorMessageReturnedFromGenerator($actualType)
    {
        return new self(\sprintf('The generator must yield a message object or a representation of the server-side '
            . 'event format as PHP array, but got "%s" instead!', $actualType));
    }
}
