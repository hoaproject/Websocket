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
use Hoa\Websocket;

/**
 * Class \Hoa\Websocket\Protocol\Hybi00.
 *
 * Protocol implementation: draft-ietf-hybi-thewebsocketprotocol-00.
 */
class Hybi00 extends Generic
{
    /**
     * Do the handshake.
     */
    public function doHandshake(Http\Request $request): void
    {
        $key1      = $request['sec-websocket-key1'] ?? '';
        $key2      = $request['sec-websocket-key2'] ?? '';
        $key3      = $request->getBody();
        $location  = $request['host'] . $request->getUrl();
        $keynumb1  = (float) preg_replace('#[^0-9]#', '', $key1);
        $keynumb2  = (float) preg_replace('#[^0-9]#', '', $key2);

        $spaces1   = substr_count($key1, ' ');
        $spaces2   = substr_count($key2, ' ');

        if (0 === $spaces1 || 0 === $spaces2) {
            throw new Websocket\Exception\BadProtocol(
                'Header Sec-WebSocket-Key1: %s or ' .
                'Sec-WebSocket-Key2: %s is illegal.',
                0,
                [$key1, $key2]
            );
        }

        $part1     = pack('N', (int) ($keynumb1 / $spaces1));
        $part2     = pack('N', (int) ($keynumb2 / $spaces2));
        $challenge = $part1 . $part2 . $key3;
        $response  = md5($challenge, true);

        $connection = $this->getConnection();
        $connection->writeAll(
            'HTTP/1.1 101 WebSocket Protocol Handshake' . "\r\n" .
            'Upgrade: WebSocket' . "\r\n" .
            'Connection: Upgrade' . "\r\n" .
            'Sec-WebSocket-Origin: ' . $request['origin'] . "\r\n" .
            'Sec-WebSocket-Location: ws://' . $location . "\r\n" .
            "\r\n" .
            $response . "\r\n"
        );
        $connection->getCurrentNode()->setHandshake(SUCCEED);
    }

    /**
     * Read a frame.
     */
    public function readFrame(): array
    {
        $buffer  = $this->getConnection()->read(2048);
        $length  = strlen($buffer) - 2;

        if (empty($buffer)) {
            return [
                'fin'     => 0x1,
                'rsv1'    => 0x0,
                'rsv2'    => 0x0,
                'rsv3'    => 0x0,
                'opcode'  => Websocket\Connection::OPCODE_CONNECTION_CLOSE,
                'mask'    => 0x0,
                'length'  => 0,
                'message' => null
            ];
        }

        return [
            'fin'     => 0x1,
            'rsv1'    => 0x0,
            'rsv2'    => 0x0,
            'rsv3'    => 0x0,
            'opcode'  => Websocket\Connection::OPCODE_TEXT_FRAME,
            'mask'    => 0x0,
            'length'  => $length,
            'message' => substr($buffer, 1, $length)
        ];
    }

    /**
     * Write a frame.
     */
    public function writeFrame(
        string $message,
        int $opcode = -1,
        bool $end   = true,
        bool $mask  = false
    ) {
        return $this->getConnection()->writeAll(
            chr(0) . $message . chr(255)
        );
    }

    /**
     * Send a message to a node (if not specified, current node).
     */
    public function send(
        string $message,
        int $opcode = -1,
        bool $end   = true,
        bool $mask  = false
    ): void {
        $this->writeFrame($message);
    }

    /**
     * Close a specific node/connection.
     */
    public function close(
        int $code      = Websocket\Connection::CLOSE_NORMAL,
        string $reason = null,
        bool $mask     = false
    ): void {
    }
}
