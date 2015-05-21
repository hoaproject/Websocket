<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2015, Hoa community. All rights reserved.
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
 * Class \Hoa\Websocket\Protocol\Rfc6455.
 *
 * Protocol implementation: RFC6455.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Rfc6455 extends Generic
{
    /**
     * GUID.
     *
     * @const string
     */
    const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';



    /**
     * Do the handshake.
     *
     * @param   \Hoa\Http\Request  $request    Request.
     * @return  void
     * @throws  \Hoa\Websocket\Exception\BadProtocol
     */
    public function doHandshake(Http\Request $request)
    {
        if (!isset($request['sec-websocket-key'])) {
            throw new Websocket\Exception\BadProtocol(
                'Bad protocol implementation: it is not RFC6455.',
                0
            );
        }

        $key = $request['sec-websocket-key'];

        if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $key) ||
            16 !== strlen(base64_decode($key))) {
            throw new Websocket\Exception\BadProtocol(
                'Header Sec-WebSocket-Key: %s is illegal.',
                1,
                $key
            );
        }

        $response = base64_encode(sha1($key . static::GUID, true));

        /**
         * @TODO
         *   • Origin;
         *   • Sec-WebSocket-Protocol;
         *   • Sec-WebSocket-Extensions.
         */

        $this->_connection->writeAll(
            'HTTP/1.1 101 Switching Protocols' . "\r\n" .
            'Upgrade: websocket' . "\r\n" .
            'Connection: Upgrade' . "\r\n" .
            'Sec-WebSocket-Accept: ' . $response . "\r\n" .
            'Sec-WebSocket-Version: 13' . "\r\n\r\n"
        );
        $this->_connection->getCurrentNode()->setHandshake(SUCCEED);

        return;
    }

    /**
     * Read a frame.
     *
     * @return  array
     * @throws  \Hoa\Websocket\Exception\CloseError
     */
    public function readFrame()
    {
        $out  = [];
        $read = $this->_connection->read(1);

        if (empty($read)) {
            $out['opcode'] = Websocket\Connection::OPCODE_CONNECTION_CLOSE;

            return $out;
        }

        $handle        = ord($read);
        $out['fin']    = ($handle >> 7) & 0x1;
        $out['rsv1']   = ($handle >> 6) & 0x1;
        $out['rsv2']   = ($handle >> 5) & 0x1;
        $out['rsv3']   = ($handle >> 4) & 0x1;
        $out['opcode'] =  $handle       & 0xf;

        $handle        = ord($this->_connection->read(1));
        $out['mask']   = ($handle >> 7) & 0x1;
        $out['length'] =  $handle       & 0x7f;
        $length        = &$out['length'];

        if (0x0 !== $out['rsv1'] || 0x0 !== $out['rsv2'] || 0x0 !== $out['rsv3']) {
            $exception = new Websocket\Exception\CloseError(
                'Get rsv1: %s, rsv2: %s, rsv3: %s, they all must be equal to 0.',
                2,
                [$out['rsv1'], $out['rsv2'], $out['rsv3']]
            );
            $exception->setErrorCode(
                Websocket\Connection::CLOSE_PROTOCOL_ERROR
            );

            throw $exception;
        }

        if (0 === $length) {
            $out['message'] = '';

            return $out;
        } elseif (0x7e === $length) {
            $handle = unpack('nl', $this->_connection->read(2));
            $length = $handle['l'];
        } elseif (0x7f === $length) {
            $handle = unpack('N*l', $this->_connection->read(8));
            $length = $handle['l2'];

            if ($length > 0x7fffffffffffffff) {
                $exception = new Websocket\Exception\CloseError(
                    'Message is too long.',
                    3
                );
                $exception->setErrorCode(
                    Websocket\Connection::CLOSE_MESSAGE_TOO_BIG
                );

                throw $exception;
            }
        }

        if (0x0 === $out['mask']) {
            $out['message'] = $this->_connection->read($length);

            return $out;
        }

        $maskN = array_map('ord', str_split($this->_connection->read(4)));
        $maskC = 0;

        if (4 !== count($maskN)) {
            $exception = new Websocket\Exception\CloseError(
                'Mask is not well-formed (too short).',
                4
            );
            $exception->setErrorCode(
                Websocket\Connection::CLOSE_PROTOCOL_ERROR
            );

            throw $exception;
        }

        $buffer       = 0;
        $bufferLength = 3000;
        $message      = null;

        for ($i = 0; $i < $length; $i += $bufferLength) {
            $buffer = min($bufferLength, $length - $i);
            $handle = $this->_connection->read($buffer);

            for ($j = 0, $_length = strlen($handle); $j < $_length; ++$j) {
                $handle[$j] = chr(ord($handle[$j]) ^ $maskN[$maskC]);
                $maskC      = ($maskC + 1) % 4;
            }

            $message .= $handle;
        }

        $out['message'] = $message;

        return $out;
    }

    /**
     * Write a frame.
     *
     * @param   string  $message    Message.
     * @param   int     $opcode     Opcode.
     * @param   bool    $end        Whether it is the last frame of the message.
     * @param   bool    $mask       Whether the message will be masked or not.
     * @return  int
     */
    public function writeFrame(
        $message,
        $opcode = Websocket\Connection::OPCODE_TEXT_FRAME,
        $end    = true,
        $mask   = false
    ) {
        $fin    = true === $end ? 0x1 : 0x0;
        $rsv1   = 0x0;
        $rsv2   = 0x0;
        $rsv3   = 0x0;
        $mask   = true === $mask ? 0x1 : 0x0;
        $length = strlen($message);
        $out    = chr(
            ($fin  << 7)
          | ($rsv1 << 6)
          | ($rsv2 << 5)
          | ($rsv3 << 4)
          | $opcode
        );

        if (0xffff < $length) {
            $out .= chr(($mask << 7) | 0x7f) . pack('NN', 0, $length);
        } elseif (0x7d < $length) {
            $out .= chr(($mask << 7) | 0x7e) . pack('n', $length);
        } else {
            $out .= chr(($mask << 7) | $length);
        }

        if (0x0 === $mask) {
            $out .= $message;
        } else {
            $maskingKey = [];

            if (function_exists('openssl_random_pseudo_bytes')) {
                $maskingKey = array_map(
                    'ord',
                    str_split(
                        openssl_random_pseudo_bytes(4)
                    )
                );
            } else {
                for ($i = 0; $i < 4; ++$i) {
                    $maskingKey[] = mt_rand(1, 255);
                }
            }

            for ($i = 0, $max = strlen($message); $i < $max; ++$i) {
                $message[$i] = chr(ord($message[$i]) ^ $maskingKey[$i % 4]);
            }

            $out .= implode('', array_map('chr', $maskingKey)) .
                    $message;
        }

        return $this->_connection->writeAll($out);
    }

    /**
     * Send a message.
     *
     * @param   string  $message    Message.
     * @param   int     $opcode     Opcode.
     * @param   bool    $end        Whether it is the last frame of
     *                              the message.
     * @param   bool    $mask       Whether the message will be masked or not.
     * @return  void
     * @throws  \Hoa\Websocket\Exception\InvalidMessage
     */
    public function send(
        $message,
        $opcode = Websocket\Connection::OPCODE_TEXT_FRAME,
        $end    = true,
        $mask   = false
    ) {
        if ((Websocket\Connection::OPCODE_TEXT_FRAME         === $opcode ||
             Websocket\Connection::OPCODE_CONTINUATION_FRAME === $opcode) &&
            false === (bool) preg_match('//u', $message)) {
            throw new Websocket\Exception\InvalidMessage(
                'Message “%s” is not in UTF-8, cannot send it.',
                5,
                32 > strlen($message)
                    ? substr($message, 0, 32) . '…'
                    : $message
            );
        }

        $this->writeFrame($message, $opcode, $end, $mask);

        return;
    }

    /**
     * Close a connection.
     *
     * @param   int     $code      Code (please, see
     *                             \Hoa\Websocket\Connection::CLOSE_*
     *                             constants).
     * @param   string  $reason    Reason.
     * @param   bool    $mask      Whether the message will be masked or not.
     * @return  void
     */
    public function close(
        $code   = Websocket\Connection::CLOSE_NORMAL,
        $reason = null,
        $mask   = false
    ) {
        $this->writeFrame(
            pack('n', $code) . $reason,
            Websocket\Connection::OPCODE_CONNECTION_CLOSE,
            true,
            $mask
        );

        return;
    }
}
