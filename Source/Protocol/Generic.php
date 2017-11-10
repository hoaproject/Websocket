<?php

declare(strict_types=1);

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Hoa\Websocket\Protocol;

use Hoa\Http;
use Hoa\Socket;
use Hoa\Websocket;

/**
 * Class \Hoa\Websocket\Protocol\Generic.
 *
 * An abstract protocol implementation.
 */
abstract class Generic
{
    /**
     * Connection.
     */
    protected $_connection = null;



    /**
     * Construct the protocol implementation.
     */
    public function __construct(Socket\Connection $connection)
    {
        $this->_connection = $connection;

        return;
    }

    /**
     * Do the handshake.
     */
    abstract public function doHandshake(Http\Request $request): void;

    /**
     * Read a frame.
     */
    abstract public function readFrame(): array;

    /**
     * Write a frame.
     */
    abstract public function writeFrame(
        string $message,
        int $opcode = Websocket\Connection::OPCODE_TEXT_FRAME,
        bool $end   = true,
        bool $mask  = false
    );

    /**
     * Send a message to a node (if not specified, current node).
     */
    abstract public function send(
        string $message,
        int $opcode = Websocket\Connection::OPCODE_TEXT_FRAME,
        bool $end   = true,
        bool $mask  = false
    ): void;

    /**
     * Close a specific node/connection.
     */
    abstract public function close(
        int $code      = Websocket\Connection::CLOSE_NORMAL,
        string $reason = null,
        bool $mask     = false
    ): void;

    /**
     * Get the socket connection.
     */
    protected function getConnection(): Socket\Connection
    {
        return $this->_connection;
    }
}
