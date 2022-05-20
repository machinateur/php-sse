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

namespace Machinateur\SSE\Message;

use Machinateur\SSE\Format\StreamFormat;

/**
 * Most basic implementation of {@see EventInterface}.
 */
class Event extends MessageAbstract implements EventInterface
{
    /**
     * @var string
     */
    private $comment = '';

    /**
     * @var string|null
     */
    private $id = null;

    /**
     * @var int
     */
    private $retry = 0;

    /**
     * @var string
     */
    private $event = '';

    /**
     * @var string|string[]|null
     */
    private $data = null;

    /**
     * @inheritDoc
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @inheritDoc
     */
    public function setRetry($retry)
    {
        $this->retry = $retry;
    }

    /**
     * @inheritDoc
     */
    public function setEvent($event)
    {
        $this->event = $event;
    }

    /**
     * @inheritDoc
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function getStreamFormat()
    {
        return [
            StreamFormat::FIELD_COMMENT => $this->comment,
            StreamFormat::FIELD_ID => $this->id,
            StreamFormat::FIELD_RETRY => $this->retry,
            StreamFormat::FIELD_EVENT => $this->event,
            StreamFormat::FIELD_DATA => $this->data,
        ];
    }
}
