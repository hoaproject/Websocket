<?php

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
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
abstract class Generic
{
    /**
     * Connection.
     *
     * @var \Hoa\Socket\Connection
     */
    protected $_connection = null;



    /**
     * Construct the protocol implementation.
     *
     * @param   \Hoa\Socket\Connection  $connection    Connection.
     */
    public function __construct(Socket\Connection $connection)
    {
        $this->_connection = $connection;

        return;
    }

    /**
     * Do the handshake.
     *
     * @param   \Hoa\Http\Request  $request    Request.
     * @return  void
     * @throws  \Hoa\Websocket\Exception\BadProtocol
     */
    abstract public function doHandshake(Http\Request $request);

    /**
     * Read a frame.
     *
     * @return  array
     * @throws  \Hoa\Websocket\Exception
     */
    abstract public function readFrame();

    /**
     * Write a frame.
     *
     * @param   string  $message    Message.
     * @param   int     $opcode     Opcode.
     * @param   bool    $end        Whether it is the last frame of the message.
     * @param   bool    $mask       Whether the message will be masked or not.
     * @return  int
     * @throws  \Hoa\Websocket\Exception
     */
    abstract public function writeFrame(
        $message,
        $opcode = Websocket\Connection::OPCODE_TEXT_FRAME,
        $end    = true,
        $mask   = false
    );

    /**
     * Send a message to a node (if not specified, current node).
     *
     * @param   string  $message    Message.
     * @param   int     $opcode     Opcode.
     * @param   bool    $end        Whether it is the last frame of the message.
     * @param   bool    $mask       Whether the message will be masked or not.
     * @return  void
     */
    abstract public function send(
        $message,
        $opcode = Websocket\Connection::OPCODE_TEXT_FRAME,
        $end    = true,
        $mask   = false
    );

    /**
     * Close a specific node/connection.
     *
     * @param   int     $code      Code (please, see
     *                             \Hoa\Websocket\Connection::CLOSE_*
     *                             constants).
     * @param   string  $reason    Reason.
     * @param   bool    $mask      Whether the message will be masked or not.
     * @return  void
     */
    abstract public function close(
        $code   = Websocket\Connection::CLOSE_NORMAL,
        $reason = null,
        $mask   = false
    );

    /**
     * Get the socket connection.
     *
     * @return \Hoa\Socket\Connection
     */
    protected function getConnection()
    {
        return $this->_connection;
    }
}
