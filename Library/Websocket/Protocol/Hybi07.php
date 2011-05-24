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
 * \Hoa\Websocket\Exception
 */
-> import('Websocket.Exception.~')

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
 * Class \Hoa\Websocket\Protocol\Hybi07.
 *
 * Protocol implementation: draft-ietf-hybi-thewebsocketprotocol-07.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2011 Ivan Enderlin.
 * @license    New BSD License
 */

class Hybi07 extends Generic {

    /**
     * Do the handshake.
     *
     * @access  public
     * @param   array  $headers    Headers.
     * @return  void
     * @throw   \Hoa\Websocket\Exception\BadProtocol
     */
    public function doHandshake ( Array $headers ) {

        if(!isset($headers['sec-websocket-key']))
            throw new \Hoa\Websocket\Exception\BadProtocol(
                'Bad protocol implementation: it is not Hybi07.', 0);

        $key      =  $headers['sec-websocket-key'];
        $origin   =  $headers['sec-websocket-origin'];
        $protocol = @$headers['sec-websocket-protocol'];
        $version  =  $headers['sec-websocket-version'];

        $uuid     = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
        $response = base64_encode(sha1($key . $uuid, true));

        $this->_server->writeAll(
            'HTTP/1.1 101 Switching Protocols' . "\r\n" .
            'Upgrade: websocket' . "\r\n" .
            'Connection: Upgrade' . "\r\n" .
            'Sec-WebSocket-Accept: ' . $response . "\r\n\r\n"
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

        $out           = array();
        $handle        = ord($this->_server->read(1));
        $out['fin']    = ($handle >> 7) & 0x1;
        $out['rsv1']   = ($handle >> 6) & 0x1;
        $out['rsv2']   = ($handle >> 5) & 0x1;
        $out['rsv3']   = ($handle >> 4) & 0x1;
        $out['opcode'] =  $handle       & 0xf;
        $handle        = ord($this->_server->read(1));
        $out['mask']   = ($handle >> 7) & 0x1;
        $out['length'] =  $handle       & 0x7f;

        if(0x0 !== $out['rsv1'] || 0x0 !== $out['rsv2'] || 0x0 !== $out['rsv3'])
            throw new \Hoa\Websocket\Exception(
                'frame-rsv1, frame-rsv2 and frame-rsv3 must equal to 0x0; ' .
                'given 0x%x, 0x%x and 0x%x.',
                1, array($out['rsv1'], $out['rsv2'], $out['rsv3']));

        if(0x7e === $out['length']) {

            $handle        = unpack('nl', $this->_server->read(2));
            $out['length'] = $handle['l'];
        }
        elseif(0x7f === $out['length']) {

            $handle        = unpack('N*l', $this->_server->read(8));
            $out['length'] = $handle['l2'];

            if($out['length'] > 0x7fffffffffffffff)
                throw new \Hoa\Websocket\Exception(
                    'Message is too long.', 2);
        }

        if(0x0 === $out['mask']) {

            $out['message'] = $this->_server->read($out['length']);

            return $out;
        }

        $maskN          = array_map(
            'ord',
            str_split($this->_server->read(4))
        );
        $maskC          = 0;
        $handle         = array_map(
            'ord',
            str_split($this->_server->read($out['length']))
        );

        foreach($handle as &$b) {

            $b     ^= $maskN[$maskC];
            $maskC  = ($maskC + 1) % 4;
        }

        $out['message'] = implode('', array_map('chr', $handle));

        return $out;
    }

    /**
     * Write a frame.
     *
     * @access  public
     * @param   string  $message    Message.
     * @param   bool    $end        Whether it is the last frame of the message.
     * @return  int
     * @throw   \Hoa\Websocket\Exception
     */
    public function writeFrame ( $message, $end = true ) {

        $fin    = true === $end ? 0x1 : 0x0;
        $rsv1   = 0x0;
        $rsv2   = 0x0;
        $rsv3   = 0x0;
        $opcode = true === $end
                      ? \Hoa\Websocket\Server::OPCODE_TEXT_FRAME
                      : \Hoa\Websocket\Server::OPCODE_CONTINUATION_FRAME;
        $mask   = 0x1;
        $length = strlen($message);
        $out    = chr(
            ($fin  << 7)
          | ($rsv1 << 6)
          | ($rsv2 << 5)
          | ($rsv3 << 4)
          | $opcode
        );

        if(0x7d >= $length)
            $out .= chr(($mask << 7) | $length);
        elseif(0x10000 >= $length)
            $out .= chr(($mask << 7) | 0x7e) . pack('n', $length);
        elseif(0x8000000000000000 >= $length)
            $out .= chr(($mask << 7) | 0x7f) . pack('N', $length);
        else
            throw new \Hoa\Websocket\Exception(
                'Message is too long.', 3);

        $maskN  = array(
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255)
        );
        $maskC  = 0;
        $handle = array_map('ord', str_split($message));

        foreach($handle as &$b) {

            $b     ^= $maskN[$maskC];
            $maskC  = ($maskC + 1) % 4;
        }

        $buffer = implode('', array_map('chr', $handle));
        $out   .= implode('', array_map('chr', $maskN)) .
                  $buffer;

        return $this->_server->writeAll($out);
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
