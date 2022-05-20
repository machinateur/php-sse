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

/**
 * The base interface for a main entry point to the SSE loop.
 */
interface MessageStreamInterface
{
    /**
     * Run down the message stream.
     *
     * Any implementation must support passing a callback function with array result or a generator function in its
     *  stead. In the latter case, a {@see \Machinateur\SSE\Exception\TimeoutException} throw must shut down the
     *  stream (due to time-out). A proper client implementation will resume the connection after its `retry` period.
     *
     * @link https://www.php.net/manual/en/language.generators.syntax.php "Generator syntax"
     * @link https://www.php.net/manual/en/language.types.callable.php "Callbacks / Callables"
     *
     * @param callable $callback
     * @return void
     */
    public function run(callable $callback);
}
