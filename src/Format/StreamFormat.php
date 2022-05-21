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

namespace Machinateur\SSE\Format;

use Machinateur\SSE\Exception\InvalidFormatException;

/**
 * All static formatting helper methods for SSE message stream format representation of a message (as PHP `array`).
 *
 * @link https://html.spec.whatwg.org/multipage/server-sent-events.html#parsing-an-event-stream "Parsing an event stream"
 */
final class StreamFormat
{
    const FIELD_COMMENT = 'comment',
        FIELD_ID = 'id',
        FIELD_RETRY = 'retry',
        FIELD_EVENT = 'event',
        FIELD_DATA = 'data';

    /**
     * Push {@see StreamFormat::FIELD_COMMENT} line to the given storage array from fields if it exists and possesses a
     *  valid value.
     *
     * @param array $lines
     * @param array $fields
     * @return void
     */
    private static function pushFieldComment(array &$lines, array $fields)
    {
        if (isset($fields[self::FIELD_COMMENT])) {
            $comment = $fields[self::FIELD_COMMENT];

            if (\is_string($comment) && '' !== $comment) {
                $lines[] = \sprintf(': %s', $comment);
            }
        }
    }

    /**
     * Push {@see StreamFormat::FIELD_ID} line to the given storage array from fields if it exists and possesses a valid
     *  value.
     *
     * @param array $lines
     * @param array $fields
     * @return void
     */
    private static function pushFieldId(array &$lines, array $fields)
    {
        if (isset($fields[self::FIELD_ID])) {
            $id = $fields[self::FIELD_ID];

            if (\is_string($id) && '' !== $id) {
                $lines[] = \sprintf('id: %s', $id);
            }
        }
    }

    /**
     * Push {@see StreamFormat::FIELD_RETRY} line to the given storage array from fields if it exists and possesses a
     *  valid value.
     *
     * @param array $lines
     * @param array $fields
     * @return void
     */
    private static function pushFieldRetry(array &$lines, array $fields)
    {
        if (isset($fields[self::FIELD_RETRY])) {
            $retry = (int)$fields[self::FIELD_RETRY];

            if ($retry > 0) {
                $lines[] = \sprintf('retry: %d', $retry);
            }
        }
    }

    /**
     * Push {@see StreamFormat::FIELD_EVENT} line to the given storage array from fields if it exists and possesses a
     *  valid value.
     *
     * @param array $lines
     * @param array $fields
     * @return void
     */
    private static function pushFieldEvent(array &$lines, array $fields)
    {
        if (isset($fields[self::FIELD_EVENT])) {
            $event = $fields[self::FIELD_EVENT];

            if (\is_string($event) && '' !== $event) {
                $lines[] = \sprintf('event: %s', $event);
            }
        }
    }

    /**
     * Push {@see StreamFormat::FIELD_DATA} line to the given storage array from fields if it exists and possesses a
     *  valid value.
     *
     * @param array $lines
     * @param array $fields
     * @return void
     */
    private static function pushFieldData(array &$lines, array $fields)
    {
        if (isset($fields[self::FIELD_DATA])) {
            $data = $fields[self::FIELD_DATA];

            if (\is_string($data)) {
                $dataArray = \explode(self::getLineSeparator(), $fields[self::FIELD_DATA]);
            } else {
                $dataArray = $data;
            }

            unset($data);

            if (\is_array($dataArray) && 0 !== \count($dataArray)) {
                foreach ($dataArray as $data) {
                    $lines[] = \sprintf('data: %s', $data);
                }
            }
        }
    }

    /**
     * Push each available fields line to the given storage array from fields if it exists and possesses a valid value.
     *  See also {@see StreamFormat::getAvailableFields()}.
     *
     * @param array $lines
     * @param array $fields
     * @param string[]|null $availableFields
     * @return void
     */
    private static function pushFields(array &$lines, array $fields, array $availableFields = null)
    {
        if (null === $availableFields) {
            $availableFields = self::getAvailableFields();
        }

        foreach ($availableFields as $availableField) {
            $pushFieldMethodName = 'pushField' . ucfirst($availableField);

            if (\method_exists(self::class, $pushFieldMethodName)) {
                self::$pushFieldMethodName($lines, $fields);
            } else {
                throw InvalidFormatException::causedByUndefinedPushFieldMethod($availableField, $pushFieldMethodName);
            }
        }
    }

    /**
     * Format SSE message stream format representation of a message (as PHP `array`) to valid SSE message lines. See
     *  also {@see \Machinateur\SSE\Message\MessageInterface::getStreamFormat()} and {@see StreamFormat::pushFields()}.
     *  Refer to {@link https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events/Using_server-sent_events#event_stream_format "Event stream format"}.
     *
     * For string output (concatenation of this method result using `\n`), turn to {@see StreamFormat::format()}.
     *
     * @param array $fields
     * @return string[]
     */
    public static function formatLines(array $fields)
    {
        // Push all content from fields.
        $lines = array();
        self::pushFields($lines, $fields);

        if (0 === count($lines)) {
            throw InvalidFormatException::causedByEmptyStreamFormatRepresentation();
        }

        // Properly end the content using two "\n".
        $lines[] = '';
        $lines[] = '';

        return $lines;
    }

    /**
     * Create valid SSE string output from single SSE message lines (as PHP `array`).
     *
     * For line output, turn to {@see StreamFormat::formatLines()}.
     *
     * @param array $lines
     * @return string
     */
    public static function format(array $lines)
    {
        return \implode(self::getLineSeparator(), self::formatLines($lines));
    }

    /**
     * A list of available fields for the SSE message format representation.
     *
     * @return string[]
     */
    public static function getAvailableFields()
    {
        return [
            self::FIELD_COMMENT,
            self::FIELD_ID,
            self::FIELD_RETRY,
            self::FIELD_EVENT,
            self::FIELD_DATA,
        ];
    }

    /**
     * @return string
     */
    public static function getLineSeparator()
    {
        return \PHP_EOL;
    }
}
