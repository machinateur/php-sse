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

use Machinateur\SSE\Format\StreamFormat;

/**
 * All exceptions related to invalid formats of some kind. This type of exception is derived from {@see \LogicException}
 *  since it should also lead directly to a fix in your code.
 */
class InvalidFormatException extends \LogicException
{
    /**
     * The exception thrown when some available field is missing its push field method in {@see StreamFormat}. See
     *  also {@see StreamFormat::pushFields()}.
     *
     * @param string $field
     * @param string $pushFieldMethodName
     * @return InvalidFormatException
     */
    public static function causedByUndefinedPushFieldMethod($field, $pushFieldMethodName)
    {
        return new self(\sprintf('The field "%s", listed as available field, is missing its corresponding push '
            . 'field method "%s" in "%s"!', $field, $pushFieldMethodName, StreamFormat::class));
    }

    /**
     * The exception thrown when a stream format representation is empty (e.g. no `comment`, `id`, `retry`, `event`
     *  or `data` fields in the PHP `array`). See also {@see StreamFormat::getAvailableFields()}.
     *
     * @param string[] $availableFields
     * @return InvalidFormatException
     */
    public static function causedByEmptyStreamFormatRepresentation(array $availableFields = null)
    {
        if (null === $availableFields) {
            $availableFields = StreamFormat::getAvailableFields();
        }

        return new self(\sprintf('Empty stream format representation! A message must at least define one of the '
            . 'following fields: [%s]', implode(', ', $availableFields)));
    }
}
