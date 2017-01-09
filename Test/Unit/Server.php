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

namespace Hoa\Websocket\Test\Unit;

use Hoa\Http;
use Hoa\Test;
use Hoa\Websocket;
use Hoa\Websocket\Server as SUT;
use Mock\Hoa\Socket;

/**
 * Class \Hoa\Websocket\Test\Unit\Server.
 *
 * Test suite for the WebSocket server class.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
class Server extends Test\Unit\Suite
{
    public function case_is_a_connection()
    {
        $this
            ->given($this->mockGenerator->orphanize('__construct'))
            ->when($result = new \Mock\Hoa\Websocket\Server())
            ->then
                ->object($result)
                    ->isInstanceOf(Websocket\Connection::class);
    }

    public function case_constructor()
    {
        $this
            ->given(
                $socket  = new Socket\Server('tcp://*:1234'),
                $request = new Http\Request()
            )
            ->when($result = new SUT($socket, $request))
            ->then
                ->object($result->getConnection())
                    ->isIdenticalTo($socket)
                ->object($result->getRequest())
                    ->isIdenticalTo($request);
    }

    public function case_constructor_with_an_undefined_request()
    {
        $this
            ->given($socket = new Socket\Server('tcp://*:1234'))
            ->when($result = new SUT($socket))
            ->then
                ->object($result->getConnection())
                    ->isIdenticalTo($socket)
                ->object($result->getRequest())
                    ->isInstanceOf(Http\Request::class);
    }

    public function case_do_handshake_rfc6455()
    {
        $self = $this;

        $this
            ->given(
                $socket  = new Socket\Server('tcp://*:1234'),
                $request = new Http\Request(),
                $this->mockGenerator->orphanize('__construct'),
                $node    = new \Mock\Hoa\Websocket\Node(),
                $server  = new SUT($socket, $request),

                $secWebSocketKey = 'Y2FzZV9kb19oYW5kc2hhaA==',

                $this->calling($socket)->read[1] =
                    'GET /foobar HTTP/1.1' . CRLF .
                    'Sec-WebSocket-Key: ' . $secWebSocketKey . CRLF . CRLF,
                $this->calling($socket)->writeAll = function ($_data) use (&$called, $self, $secWebSocketKey) {
                    $called = true;

                    $self
                        ->let($challenge = base64_encode(sha1($secWebSocketKey . Websocket\Protocol\Rfc6455::GUID, true)))
                        ->string($_data)
                            ->isEqualTo(
                                'HTTP/1.1 101 Switching Protocols' . CRLF .
                                'Upgrade: websocket' . CRLF .
                                'Connection: Upgrade' . CRLF .
                                'Sec-WebSocket-Accept: ' . $challenge . CRLF .
                                'Sec-WebSocket-Version: 13' . CRLF . CRLF
                            );

                    return strlen($_data);
                },
                $this->calling($socket)->getCurrentNode = $node
            )
            ->when($result = $this->invoke($server)->doHandshake())
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($called)
                    ->isTrue()
                ->object($node->getProtocolImplementation())
                    ->isInstanceOf(Websocket\Protocol\Rfc6455::class)
                ->string($request->getMethod())
                    ->isEqualTo($request::METHOD_GET)
                ->string($request->getUrl())
                    ->isEqualTo('/foobar')
                ->array($request->getHeaders())
                    ->isEqualTo([
                        'sec-websocket-key' => $secWebSocketKey
                    ]);
    }

    public function case_do_handshake_hybi00()
    {
        $self = $this;

        $this
            ->given(
                $socket  = new Socket\Server('tcp://*:1234'),
                $request = new Http\Request(),
                $this->mockGenerator->orphanize('__construct'),
                $node    = new \Mock\Hoa\Websocket\Node(),
                $server  = new SUT($socket, $request),

                $this->calling($socket)->read[1] =
                    'GET /foobar HTTP/1.1' . CRLF .
                    'Host: example.org' . CRLF .
                    'Origin: elpmaxe.org' . CRLF .
                    'Sec-WebSocket-Key1: 1a23b c45d e f' . CRLF .
                    'Sec-WebSocket-Key2: 6g78h i90j kl' . CRLF .
                    'Host: example.org' . CRLF . CRLF .
                    'hello' . CRLF,
                $this->calling($socket)->writeAll = function ($_data) use (&$called, $self) {
                    $called = true;

                    $self
                        ->let($challenge = md5(pack('N', (int) (12345 / 3)) . pack('N', (int) (67890 / 2)) . 'hello', true))
                        ->string($_data)
                            ->isEqualTo(
                                'HTTP/1.1 101 WebSocket Protocol Handshake' . CRLF .
                                'Upgrade: WebSocket' . CRLF .
                                'Connection: Upgrade' . CRLF .
                                'Sec-WebSocket-Origin: elpmaxe.org' . CRLF .
                                'Sec-WebSocket-Location: ws://example.org/foobar' . CRLF . CRLF .
                                $challenge . CRLF
                            );

                    return strlen($_data);
                },
                $this->calling($socket)->getCurrentNode = $node
            )
            ->when($result = $this->invoke($server)->doHandshake())
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($called)
                    ->isTrue()
                ->object($node->getProtocolImplementation())
                    ->isInstanceOf(Websocket\Protocol\Hybi00::class)
                ->string($request->getMethod())
                    ->isEqualTo($request::METHOD_GET)
                ->string($request->getUrl())
                    ->isEqualTo('/foobar')
                ->array($request->getHeaders())
                    ->isEqualTo([
                        'host'               => 'example.org',
                        'origin'             => 'elpmaxe.org',
                        'sec-websocket-key1' => '1a23b c45d e f',
                        'sec-websocket-key2' => '6g78h i90j kl'
                    ]);
    }

    public function case_do_handshake_undefined_protocol()
    {
        $this
            ->given(
                $socket  = new Socket\Server('tcp://*:1234'),
                $request = new Http\Request(),
                $this->mockGenerator->orphanize('__construct'),
                $node    = new \Mock\Hoa\Websocket\Node(),
                $server  = new SUT($socket, $request),

                $this->calling($socket)->read[1] =
                    'GET /foobar HTTP/1.1' . CRLF . CRLF,
                $this->calling($socket)->writeAll = function ($_data) use (&$called) {
                    $called = true;

                    return 0;
                },
                $this->calling($socket)->getCurrentNode = $node
            )
            ->exception(function () use ($server) {
                $this->invoke($server)->doHandshake();
            })
                ->isInstanceOf(Websocket\Exception\BadProtocol::class);
    }

    public function case_set_request()
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $requestA = new Http\Request(),
                $requestB = new Http\Request(),
                $server   = new SUT($socket, $requestA)
            )
            ->when($result = $server->setRequest($requestB))
            ->then
                ->object($result)
                    ->isIdenticalTo($requestA);
    }

    public function case_get_request()
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $requestA = new Http\Request(),
                $requestB = new Http\Request(),
                $server   = new SUT($socket, $requestA),
                $server->setRequest($requestB)
            )
            ->when($result = $server->getRequest())
            ->then
                ->object($result)
                    ->isIdenticalTo($requestB)
                ->boolean($socket->isDisconnected())
                    ->isTrue();
    }
}
