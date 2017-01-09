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

namespace Hoa\Websocket\Test\Unit\Protocol;

use Hoa\Test;
use Hoa\Websocket;
use Mock\Hoa\Http;
use Mock\Hoa\Socket;
use Mock\Hoa\Websocket\Protocol\Hybi00 as SUT;

/**
 * Class \Hoa\Websocket\Test\Unit\Protocol\Hybi00.
 *
 * Test suite for the Hybi00 protocol implementation.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
class Hybi00 extends Test\Unit\Suite
{
    public function case_extends_generic()
    {
        $this
            ->given($socket = new Socket\Server('tcp://*:1234'))
            ->when($result = new SUT($socket))
            ->then
                ->object($result)
                    ->isInstanceOf(Websocket\Protocol\Generic::class);
    }

    public function case_do_handshake_illegal_sec_websocket_key_header()
    {
        $this
            ->given(
                $request  = new Http\Request(),
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket)
            )
            ->exception(function () use ($protocol, $request) {
                $protocol->doHandshake($request);
            })
                ->isInstanceOf(Websocket\Exception\BadProtocol::class);
    }

    public function case_do_handshake()
    {
        $self = $this;

        $this
            ->given(
                $request = new Http\Request(),
                $this->mockGenerator->orphanize('__construct'),
                $node     = new \Mock\Hoa\Websocket\Node(),
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $request['sec-websocket-key1'] = '1a23b c45d e f',
                $request['sec-websocket-key2'] = '6g78h i90j kl',
                $request['host']               = 'example.org',
                $request['origin']             = 'elpmaxe.org',
                $request->setUrl('/foobar'),
                $request->setBody('hello'),
                $challenge = md5(
                    pack('N', (int) (12345 / 3)) .
                    pack('N', (int) (67890 / 2)) .
                    $request->getBody(),
                    true
                ),

                $this->calling($socket)->getCurrentNode = $node,
                $this->calling($node)->setHandshake     = function ($handshake) use (&$calledA, $self) {
                    $calledA = true;

                    $self
                        ->boolean($handshake)
                            ->isTrue();

                    return;
                },
                $this->calling($socket)->writeAll = function ($data) use (&$calledB, $self, $challenge) {
                    $calledB = true;

                    $self
                        ->string($data)
                            ->isEqualTo(
                                'HTTP/1.1 101 WebSocket Protocol Handshake' . CRLF .
                                'Upgrade: WebSocket' . CRLF .
                                'Connection: Upgrade' . CRLF .
                                'Sec-WebSocket-Origin: elpmaxe.org' . CRLF .
                                'Sec-WebSocket-Location: ws://example.org/foobar' . CRLF . CRLF .
                                $challenge . CRLF
                            );

                    return;
                }
            )
            ->when($result = $protocol->doHandshake($request))
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($calledA)
                    ->isTrue()
                ->boolean($calledB)
                    ->isTrue();
    }

    public function case_read_empty_frame()
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $this->calling($socket)->read = null
            )
            ->when($result = $protocol->readFrame())
            ->then
                ->array($result)
                    ->isEqualTo([
                        'fin'     => 0x1,
                        'rsv1'    => 0x0,
                        'rsv2'    => 0x0,
                        'rsv3'    => 0x0,
                        'opcode'  => Websocket\Connection::OPCODE_CONNECTION_CLOSE,
                        'mask'    => 0x0,
                        'length'  => 0,
                        'message' => null
                    ]);
    }

    public function case_read_frame()
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $message = 'foobar',

                $this->calling($socket)->read = '!' . $message . '?'
            )
            ->when($result = $protocol->readFrame())
            ->then
                ->array($result)
                    ->isEqualTo([
                        'fin'     => 0x1,
                        'rsv1'    => 0x0,
                        'rsv2'    => 0x0,
                        'rsv3'    => 0x0,
                        'opcode'  => Websocket\Connection::OPCODE_TEXT_FRAME,
                        'mask'    => 0x0,
                        'length'  => strlen($message),
                        'message' => $message
                    ]);
    }

    public function case_write_frame()
    {
        $self = $this;

        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $message = 'foobar',
                $opcode  = Websocket\Connection::OPCODE_CONNECTION_CLOSE,
                $end     = true,
                $mask    = false,

                $this->calling($socket)->writeAll = function ($_data) use (&$called, $self, $message) {
                    $called = true;

                    $self
                        ->string($_data)
                            ->isEqualTo(chr(0) . $message . chr(255));

                    return strlen($message) + 2;
                }
            )
            ->when($result = $protocol->writeFrame($message, $opcode, $end, $mask))
            ->then
                ->integer($result)
                    ->isEqualTo(strlen($message) + 2)
                ->boolean($called)
                    ->isTrue();
    }

    public function case_send()
    {
        $self = $this;

        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $message = 'foobar',
                $opcode  = Websocket\Connection::OPCODE_CONNECTION_CLOSE,
                $end     = false,
                $mask    = true,

                $this->calling($protocol)->writeFrame = function ($_message, $_opcode, $_end, $_mask) use (&$called, $self, $message) {
                    $called = true;

                    $self
                        ->string($_message)
                            ->isEqualTo($message)
                        ->integer($_opcode)
                            ->isEqualTo(-1)
                        ->boolean($_end)
                            ->isTrue()
                        ->boolean($_mask)
                            ->isFalse();

                    return;
                }
            )
            ->when($result = $protocol->send($message, $opcode, $end, $mask))
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($called)
                    ->isTrue();
    }

    public function case_close()
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $this->calling($socket)->write = function () use (&$called) {
                    $called = true;
                }
            )
            ->when($result = $protocol->close())
            ->then
                ->variable($result)
                    ->isNull()
                ->variable($called)
                    ->isNull();
    }
}
