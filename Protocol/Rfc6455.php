<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2013, Ivan Enderlin. All rights reserved.
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
 * \Hoa\Websocket\Exception
 */
-> import('Websocket.Exception.~')

/**
 * \Hoa\Websocket\Exception\BadProtocol
 */
-> import('Websocket.Exception.BadProtocol')

/**
 * \Hoa\Websocket\Exception\InvalidMessage
 */
-> import('Websocket.Exception.InvalidMessage')

/**
 * \Hoa\Websocket\Protocol\Generic
 */
-> import('Websocket.Protocol.Generic');

}

namespace Hoa\Websocket\Protocol {

/**
 * Class \Hoa\Websocket\Protocol\Rfc6455.
 *
 * Protocol implementation: RFC6455.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2013 Ivan Enderlin.
 * @license    New BSD License
 */

class Rfc6455 extends Generic {

    /**
     * GUID.
     *
     * @const string
     */
    const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';



    /**
     * Do the handshake.
     *
     * @access  public
     * @param   \Hoa\Http\Request  $request    Request.
     * @return  void
     * @throw   \Hoa\Websocket\Exception\BadProtocol
     */
    public function doHandshake ( \Hoa\Http\Request $request ) {

        if(!isset($request['sec-websocket-key']))
            throw new \Hoa\Websocket\Exception\BadProtocol(
                'Bad protocol implementation: it is not RFC6455.', 0);

        $key      = $request['sec-websocket-key'];
        $response = base64_encode(sha1($key . static::GUID, true));

        /**
         * @TODO
         *   • Origin;
         *   • Sec-WebSocket-Protocol;
         *   • Sec-WebSocket-Extensions.
         */

        $this->_server->writeAll(
            'HTTP/1.1 101 Switching Protocols' . "\r\n" .
            'Upgrade: websocket' . "\r\n" .
            'Connection: Upgrade' . "\r\n" .
            'Sec-WebSocket-Accept: ' . $response . "\r\n" .
            'Sec-WebSocket-Version: 13' . "\r\n\r\n"
        );
        $this->_server->getCurrentNode()->setHandshake(SUCCEED);

        return;
    }

    /**
     * Read a frame.
     *
     * @access  public
     * @return  array
     * @throw   \Hoa\Websocket\Exception
     */
    public function readFrame ( ) {

        $out  = array();
        $read = $this->_server->read(1);

        if(empty($read)) {

            $out['opcode'] = \Hoa\Websocket\Server::OPCODE_CONNECTION_CLOSE;

            return $out;
        }

        $handle        = ord($read);
        $out['fin']    = ($handle >> 7) & 0x1;
        $out['rsv1']   = ($handle >> 6) & 0x1;
        $out['rsv2']   = ($handle >> 5) & 0x1;
        $out['rsv3']   = ($handle >> 4) & 0x1;
        $out['opcode'] =  $handle       & 0xf;

        $handle        = ord($this->_server->read(1));
        $out['mask']   = ($handle >> 7) & 0x1;
        $out['length'] =  $handle       & 0x7f;
        $length        = &$out['length'];

        if(0x0 !== $out['rsv1'] || 0x0 !== $out['rsv2'] || 0x0 !== $out['rsv3']) {

            $this->_server->close(\Hoa\Websocket\Server::CLOSE_PROTOCOL_ERROR);

            return false;
        }

        if(0 === $length) {

            $out['message'] = '';

            return $out;
        }
        elseif(0x7e === $length) {

            $handle = unpack('nl', $this->_server->read(2));
            $length = $handle['l'];
        }
        elseif(0x7f === $length) {

            $handle = unpack('N*l', $this->_server->read(8));
            $length = $handle['l2'];

            if($length > 0x7fffffffffffffff)
                throw new \Hoa\Websocket\Exception(
                    'Message is too long.', 1);
        }

        if(0x0 === $out['mask']) {

            $out['message'] = $this->_server->read($length);

            return $out;
        }

        $maskN = array_map('ord', str_split($this->_server->read(4)));
        $maskC = 0;

        $buffer       = 0;
        $bufferLength = 3000;
        $message      = null;

        for($i = 0; $i < $length; $i += $bufferLength) {

            $buffer = min($bufferLength, $length - $i);
            $handle = $this->_server->read($buffer);

            for($j = 0, $_length = strlen($handle); $j < $_length; ++$j) {

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
     * @access  public
     * @param   string  $message    Message.
     * @param   int     $opcode     Opcode.
     * @param   bool    $end        Whether it is the last frame of the message.
     * @return  int
     */
    public function writeFrame ( $message,
                                 $opcode = \Hoa\Websocket\Server::OPCODE_TEXT_FRAME,
                                 $end    = true ) {

        $fin    = true === $end ? 0x1 : 0x0;
        $rsv1   = 0x0;
        $rsv2   = 0x0;
        $rsv3   = 0x0;
        $mask   = 0x1;
        $length = strlen($message);
        $out    = chr(
            ($fin  << 7)
          | ($rsv1 << 6)
          | ($rsv2 << 5)
          | ($rsv3 << 4)
          | $opcode
        );

        if(0xffff < $length)
            $out .= chr(0x7f) . pack('NN', 0, $length);
        elseif(0x7d < $length)
            $out .= chr(0x7e) . pack('n', $length);
        else
            $out .= chr($length);

        $out .= $message;

        return $this->_server->writeAll($out);
    }

    /**
     * Send a message.
     *
     * @access  public
     * @param   string  $message    Message.
     * @param   int     $opcode     Opcode.
     * @param   bool    $end        Whether it is the last frame of
     *                              the message.
     * @return  void
     * @throw   \Hoa\Websocket\Exception\InvalidMessage
     */
    public function send ( $message,
                           $opcode = \Hoa\Websocket\Server::OPCODE_TEXT_FRAME,
                           $end    = true ) {

        if(   (\Hoa\Websocket\Server::OPCODE_TEXT_FRAME         === $opcode
           ||  \Hoa\Websocket\Server::OPCODE_CONTINUATION_FRAME === $opcode)
           && false === (bool) preg_match('//u', $message))
            throw new \Hoa\Websocket\Exception\InvalidMessage(
                'Message “%s” is not in UTF-8, cannot send it.',
                2, 32 > strlen($message) ? substr($message, 0, 32) . '…' : $message);

        $this->writeFrame($message, $opcode, $end);

        return;
    }

    /**
     * Close a connection.
     *
     * @access  public
     * @param   int     $code      Code (please, see
     *                             \Hoa\Websocket\Server::CLOSE_*
     *                             constants).
     * @param   string  $reason    Reason.
     * @return  void
     */
    public function close ( $code   = \Hoa\Websocket\Server::CLOSE_NORMAL,
                            $reason = null ) {

        $this->writeFrame(
            pack('n', $code) . $reason,
            \Hoa\Websocket\Server::OPCODE_CONNECTION_CLOSE
        );

        return;
    }
}

}
