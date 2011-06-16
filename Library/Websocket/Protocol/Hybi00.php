<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2011, Ivan Enderlin. All rights reserved.
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

namespace {

from('Hoa')

/**
 * \Hoa\Websocket\Exception\BadProtocol
 */
-> import('Websocket.Exception.BadProtocol')

/**
 * \Hoa\Websocket\Protocol\Generic
 */
-> import('Websocket.Protocol.Generic');

}

namespace Hoa\Websocket\Protocol {

/**
 * Class \Hoa\Websocket\Protocol\Hybi00.
 *
 * Protocol implementation: draft-ietf-hybi-thewebsocketprotocol-00.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2011 Ivan Enderlin.
 * @license    New BSD License
 */

class Hybi00 extends Generic {

    /**
     * Do the handshake.
     *
     * @access  public
     * @param   array  $headers    Headers.
     * @return  void
     * @throw   \Hoa\Websocket\Exception\BadProtocol
     */
    public function doHandshake ( Array $headers ) {

        $key1      = $headers['sec-websocket-key1'];
        $key2      = $headers['sec-websocket-key2'];
        $key3      = $headers['__Body'];
        $location  = $headers['host'] . '/' . $headers['resource'];
        $keynumb1  = (float) preg_replace('#[^0-9]#', '', $key1);
        $keynumb2  = (float) preg_replace('#[^0-9]#', '', $key2);

        $spaces1   = substr_count($key1, ' ');
        $spaces2   = substr_count($key2, ' ');

        $part1     = pack('N', (int) ($keynumb1 / $spaces1));
        $part2     = pack('N', (int) ($keynumb2 / $spaces2));
        $challenge = $part1 . $part2 . $key3;
        $response  = md5($challenge, true);

        $this->_server->writeAll(
            'HTTP/1.1 101 WebSocket Protocol Handshake' . "\r\n" .
            'Upgrade: WebSocket' . "\r\n" .
            'Connection: Upgrade' . "\r\n" .
            'Sec-WebSocket-Origin: ' . $headers['origin'] . "\r\n" .
            'Sec-WebSocket-Location: ws://' . $location . "\r\n" .
            "\r\n" .
            $response . "\r\n"
        );

        $this->_server->getCurrentNode()->setHandshake(SUCCEED);

        return;
    }

    /**
     * Read a frame.
     *
     * @access  public
     * @return  array
     */
    public function readFrame ( ) {

        $buffer  = $this->_server->read(2048);
        $length  = strlen($buffer) - 2;

        if(empty($buffer))
            return array(
                'fin'     => 0x1,
                'rsv1'    => 0x0,
                'rsv2'    => 0x0,
                'rsv3'    => 0x0,
                'opcode'  => \Hoa\Websocket\Server::OPCODE_CONNECTION_CLOSE,
                'mask'    => 0x0,
                'length'  => 0,
                'message' => null
            );

        return array(
            'fin'     => 0x1,
            'rsv1'    => 0x0,
            'rsv2'    => 0x0,
            'rsv3'    => 0x0,
            'opcode'  => \Hoa\Websocket\Server::OPCODE_TEXT_FRAME,
            'mask'    => 0x0,
            'length'  => $length,
            'message' => substr($buffer, 1, $length)
        );
    }

    /**
     * Write a frame.
     *
     * @access  public
     * @param   string  $message    Message.
     * @param   bool    $end        Whether it is the last frame of the message.
     * @return  int
     */
    public function writeFrame ( $message, $end = true ) {

        return $this->_server->writeAll(
            chr(0) . $message . chr(255)
        );
    }

    /**
     * Send a message to a node (if not specified, current node).
     *
     * @access  public
     * @param   string               $message    Message.
     * @param   \Hoa\Websocket\Node  $node       Node.
     * @return  void
     */
    public function send ( $message, \Hoa\Websocket\Node $node = null ) {

        if(null === $node) {

            $this->writeFrame($message);

            return;
        }

        $old = $this->_server->_setStream($node->getSocket());
        $node->getProtocolImplementation()->writeFrame($message);
        $this->_server->_setStream($old);

        return;
    }
}

}
