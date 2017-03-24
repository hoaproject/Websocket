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

use Hoa\Event;
use Hoa\Http;
use Hoa\Test;
use Hoa\Websocket;
use Hoa\Websocket\Client as SUT;
use Mock\Hoa\Socket;

/**
 * Class \Hoa\Websocket\Test\Unit\Client.
 *
 * Test suite for the WebSocket client class.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
class Client extends Test\Unit\Suite
{
    public function case_is_a_connection()
    {
        $this
            ->given($this->mockGenerator->orphanize('__construct'))
            ->when($result = new \Mock\Hoa\Websocket\Client())
            ->then
                ->object($result)
                    ->isInstanceOf(Websocket\Connection::class);
    }

    public function case_constructor()
    {
        $this
            ->given(
                $socket   = new Socket\Client('tcp://*:1234'),
                $endPoint = '/foobar',
                $response = new Http\Response()
            )
            ->when($result = new SUT($socket, $endPoint, $response))
            ->then
                ->object($result->getConnection())
                    ->isIdenticalTo($socket)
                ->string($result->getEndPoint())
                    ->isEqualTo($endPoint)
                ->object($result->getResponse())
                    ->isIdenticalTo($response);
    }

    public function case_constructor_with_an_undefined_endpoint()
    {
        $this
            ->given(
                $socket   = new Socket\Client('tcp://*:1234'),
                $endPoint = null,
                $response = new Http\Response()
            )
            ->when($result = new SUT($socket, $endPoint, $response))
            ->then
                ->object($result->getConnection())
                    ->isIdenticalTo($socket)
                ->string($result->getEndPoint())
                    ->isEqualTo('/')
                ->object($result->getResponse())
                    ->isIdenticalTo($response);
    }

    public function case_constructor_with_a_socket_defined_endpoint()
    {
        $this
            ->given(
                $socket    = new Websocket\Socket('tcp://*:1234', false, '/foobar'),
                $hoaSocket = new Socket\Client('tcp://*:1234'),
                $endPoint  = null,
                $response  = new Http\Response(),

                $this->calling($hoaSocket)->getSocket = $socket
            )
            ->when($result = new SUT($hoaSocket, $endPoint, $response))
            ->then
                ->object($result->getConnection())
                    ->isIdenticalTo($hoaSocket)
                ->string($result->getEndPoint())
                    ->isEqualTo('/foobar')
                ->object($result->getResponse())
                    ->isIdenticalTo($response);
    }

    public function case_constructor_with_an_undefined_response()
    {
        $this
            ->given(
                $socket   = new Socket\Client('tcp://*:1234'),
                $endPoint = '/foobar'
            )
            ->when($result = new SUT($socket, $endPoint))
            ->then
                ->object($result->getConnection())
                    ->isIdenticalTo($socket)
                ->string($result->getEndPoint())
                    ->isEqualTo('/foobar')
                ->object($result->getResponse())
                    ->isInstanceOf(Http\Response::class);
    }

    public function case_connect()
    {
        $this
            ->given(
                $socket = new Socket\Client('tcp://*:1234'),
                $this->mockGenerator->makeVisible('doHandshake')->generate(SUT::class),
                $client = new \Mock\Hoa\Websocket\Client($socket),

                $this->calling($client)->doHandshake = function () use (&$called) {
                    $called = true;

                    return;
                }
            )
            ->when($result = $client->connect())
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($called)
                    ->isTrue();
    }

    public function case_receive_a_complete_message_and_is_not_disconnected()
    {
        $self = $this;

        $this
            ->given(
                $socket = new Socket\Client('tcp://*:1234'),
                $this->mockGenerator->orphanize('__construct'),
                $node   = new \Mock\Hoa\Websocket\Node(),
                $this->mockGenerator->makeVisible('_run')->generate(SUT::class),
                $client = new \Mock\Hoa\Websocket\Client($socket),

                $this->calling($node)->isMessageComplete = true,
                $this->calling($socket)->getCurrentNode  = $node,
                $this->calling($socket)->isDisconnected  = false,

                $this->calling($client)->_run = function (Websocket\Node $_node) use (&$called, $self, $node) {
                    $called = true;

                    $self
                        ->object($_node)
                            ->isIdenticalTo($node);

                    return;
                }
            )
            ->when($result = $client->receive())
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($called)
                    ->isTrue();
    }

    public function case_receive_an_incomplete_message_and_is_disconnected()
    {
        $self = $this;

        $this
            ->given(
                $socket = new Socket\Client('tcp://*:1234'),
                $this->mockGenerator->orphanize('__construct'),
                $node   = new \Mock\Hoa\Websocket\Node(),
                $this->mockGenerator->makeVisible('_run')->generate(SUT::class),
                $client = new \Mock\Hoa\Websocket\Client($socket),

                $this->calling($node)->isMessageComplete = false,
                $this->calling($socket)->getCurrentNode  = $node,
                $this->calling($socket)->isDisconnected  = true,

                $this->calling($client)->_run = function (Websocket\Node $_node) use (&$called, $self, $node) {
                    $called = true;

                    $self
                        ->object($_node)
                            ->isIdenticalTo($node);

                    return;
                }
            )
            ->when($result = $client->receive())
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($called)
                    ->isTrue();
    }

    public function case_do_handshake()
    {
        $self = $this;

        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $sock = new \Mock\Hoa\Websocket\Socket(),
                $this->mockGenerator->orphanize('__construct'),
                $node     = new \Mock\Hoa\Websocket\Node(),
                $socket   = new Socket\Client('tcp://*:1234'),
                $endPoint = '/foobar',
                $response = new Http\Response(),
                $client   = new \Mock\Hoa\Websocket\Client($socket, $endPoint, $response),
                $host     = 'example.org',
                $client->setHost($host),
                $challenge = 'Y2FzZV9kb19oYW5kc2hhaA==',

                $this->calling($sock)->isSecured   = false,
                $this->calling($socket)->getSocket = $sock,
                $this->calling($socket)->connect   = function () use (&$calledA) {
                    $calledA = true;

                    return true;
                },
                $this->calling($socket)->setStreamBlocking = function ($_block) use (&$calledB, $self) {
                    $calledB = true;

                    $self
                        ->boolean($_block)
                            ->isTrue();

                    return true;
                },
                $this->calling($socket)->writeAll = function ($_data) use (&$calledC, $self, $endPoint, $host, $challenge) {
                    $calledC = true;

                    $self
                        ->string($_data)
                            ->isEqualTo(
                                'GET ' . $endPoint . ' HTTP/1.1' . CRLF .
                                'Host: ' . $host . CRLF .
                                'User-Agent: Hoa' . CRLF .
                                'Upgrade: WebSocket' . CRLF .
                                'Connection: Upgrade' . CRLF .
                                'Pragma: no-cache' . CRLF .
                                'Cache-Control: no-cache' . CRLF .
                                'Sec-WebSocket-Key: ' . $challenge . CRLF .
                                'Sec-WebSocket-Version: 13' . CRLF . CRLF
                            );

                    return strlen($_data);
                },
                $this->calling($socket)->read = function ($_size) use (&$calledD, $self, $challenge) {
                    $calledD = true;

                    $self
                        ->integer($_size)
                            ->isEqualTo(2048);

                    return
                        'HTTP/1.1 101 Switching Protocols' . CRLF .
                        'Upgrade: websocket' . CRLF .
                        'Connection: Upgrade' . CRLF .
                        'Sec-WebSocket-Accept: ' . base64_encode(sha1($challenge . Websocket\Protocol\Rfc6455::GUID, true)) . CRLF .
                        'Sec-WebSocket-Version: 13' . CRLF . CRLF;
                },
                $this->calling($socket)->getCurrentNode  = $node,
                $this->calling($client)->getNewChallenge = $challenge,
                $client->on(
                    'open',
                    function (Event\Bucket $bucket) use (&$calledE, $self) {
                        $calledE = true;

                        $self
                            ->variable($bucket->getData())
                                ->isNull();

                        return;
                    }
                )
            )
            ->when($result = $this->invoke($client)->doHandshake())
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($calledA)
                    ->isTrue()
                ->boolean($calledB)
                    ->isTrue()
                ->boolean($calledC)
                    ->isTrue()
                ->boolean($calledD)
                    ->isTrue()
                ->boolean($calledE)
                    ->isTrue()
                ->boolean($node->getHandshake())
                    ->isTrue()
                ->object($node->getProtocolImplementation())
                    ->isInstanceOf(Websocket\Protocol\Rfc6455::class)
                ->float($response->getHttpVersion())
                    ->isEqualTo(1.1)
                ->array($response->getHeaders())
                    ->isEqualTo([
                        'status'                => $response::STATUS_SWITCHING_PROTOCOLS,
                        'upgrade'               => 'websocket',
                        'connection'            => 'Upgrade',
                        'sec-websocket-accept'  => base64_encode(sha1($challenge . Websocket\Protocol\Rfc6455::GUID, true)),
                        'sec-websocket-version' => '13'
                    ]);
    }

    public function case_do_handshake_with_a_secured_connection()
    {
        $self = $this;

        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $sock = new \Mock\Hoa\Websocket\Socket(),
                $this->mockGenerator->orphanize('__construct'),
                $node     = new \Mock\Hoa\Websocket\Node(),
                $socket   = new Socket\Client('tcp://*:1234'),
                $endPoint = '/foobar',
                $response = new Http\Response(),
                $client   = new \Mock\Hoa\Websocket\Client($socket, $endPoint, $response),
                $host     = 'example.org',
                $client->setHost($host),
                $challenge = 'Y2FzZV9kb19oYW5kc2hhaA==',

                $this->calling($sock)->isSecured   = true,
                $this->calling($socket)->getSocket = $sock,
                $this->calling($socket)->connect   = function () use (&$calledA) {
                    $calledA = true;

                    return true;
                },
                $this->calling($socket)->enableEncryption = function ($_enable, $_type, $_sessionStream) use (&$calledB, $self, $socket) {
                    $calledB = true;

                    $self
                        ->boolean($_enable)
                            ->isTrue()
                        ->integer($_type)
                            ->isEqualTo($socket::ENCRYPTION_TLS)
                        ->variable($_sessionStream)
                            ->isNull();

                    return true;
                },
                $this->calling($socket)->setStreamBlocking = function ($_block) use (&$calledC, $self) {
                    $calledC = true;

                    $self
                        ->boolean($_block)
                            ->isTrue();

                    return true;
                },
                $this->calling($socket)->writeAll = function ($_data) use (&$calledD, $self, $endPoint, $host, $challenge) {
                    $calledD = true;

                    $self
                        ->string($_data)
                            ->isEqualTo(
                                'GET ' . $endPoint . ' HTTP/1.1' . CRLF .
                                'Host: ' . $host . CRLF .
                                'User-Agent: Hoa' . CRLF .
                                'Upgrade: WebSocket' . CRLF .
                                'Connection: Upgrade' . CRLF .
                                'Pragma: no-cache' . CRLF .
                                'Cache-Control: no-cache' . CRLF .
                                'Sec-WebSocket-Key: ' . $challenge . CRLF .
                                'Sec-WebSocket-Version: 13' . CRLF . CRLF
                            );

                    return strlen($_data);
                },
                $this->calling($socket)->read = function ($_size) use (&$calledE, $self, $challenge) {
                    $calledE = true;

                    $self
                        ->integer($_size)
                            ->isEqualTo(2048);

                    return
                        'HTTP/1.1 101 Switching Protocols' . CRLF .
                        'Upgrade: websocket' . CRLF .
                        'Connection: Upgrade' . CRLF .
                        'Sec-WebSocket-Accept: ' . base64_encode(sha1($challenge . Websocket\Protocol\Rfc6455::GUID, true)) . CRLF .
                        'Sec-WebSocket-Version: 13' . CRLF . CRLF;
                },
                $this->calling($socket)->getCurrentNode  = $node,
                $this->calling($client)->getNewChallenge = $challenge,
                $client->on(
                    'open',
                    function (Event\Bucket $bucket) use (&$calledF, $self) {
                        $calledF = true;

                        $self
                            ->variable($bucket->getData())
                                ->isNull();

                        return;
                    }
                )
            )
            ->when($result = $this->invoke($client)->doHandshake())
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($calledA)
                    ->isTrue()
                ->boolean($calledB)
                    ->isTrue()
                ->boolean($calledC)
                    ->isTrue()
                ->boolean($calledD)
                    ->isTrue()
                ->boolean($calledE)
                    ->isTrue()
                ->boolean($calledF)
                    ->isTrue()
                ->boolean($node->getHandshake())
                    ->isTrue()
                ->object($node->getProtocolImplementation())
                    ->isInstanceOf(Websocket\Protocol\Rfc6455::class)
                ->float($response->getHttpVersion())
                    ->isEqualTo(1.1)
                ->array($response->getHeaders())
                    ->isEqualTo([
                        'status'                => $response::STATUS_SWITCHING_PROTOCOLS,
                        'upgrade'               => 'websocket',
                        'connection'            => 'Upgrade',
                        'sec-websocket-accept'  => base64_encode(sha1($challenge . Websocket\Protocol\Rfc6455::GUID, true)),
                        'sec-websocket-version' => '13'
                    ]);
    }

    public function case_do_handshake_with_no_host()
    {
        $self = $this;

        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $sock = new \Mock\Hoa\Websocket\Socket(),
                $this->mockGenerator->orphanize('__construct'),
                $node     = new \Mock\Hoa\Websocket\Node(),
                $socket   = new Socket\Client('tcp://*:1234'),
                $endPoint = '/foobar',
                $response = new Http\Response(),
                $client   = new SUT($socket, $endPoint, $response),

                $this->calling($sock)->isSecured   = false,
                $this->calling($socket)->getSocket = $sock,
                $this->calling($socket)->connect   = null,
                $this->calling($socket)->enableEncryption = null,
                $this->calling($socket)->setStreamBlocking = null
            )
            ->exception(function () use ($client) {
                $this->invoke($client)->doHandshake();
            })
                ->isInstanceOf(Websocket\Exception::class);
    }

    public function case_do_handshake_invalid_response_status()
    {
        return $this->_case_do_handshake_invalid_response(
            'HTTP/1.1 404 Not Found' . CRLF . CRLF
        );
    }

    public function case_do_handshake_invalid_response_upgrade_header()
    {
        return $this->_case_do_handshake_invalid_response(
            'HTTP/1.1 101 Switching Protocols' . CRLF .
            'Upgrade: foobar' . CRLF . CRLF
        );
    }

    public function case_do_handshake_invalid_response_connection_header()
    {
        return $this->_case_do_handshake_invalid_response(
            'HTTP/1.1 101 Switching Protocols' . CRLF .
            'Upgrade: websocket' . CRLF .
            'Connection: foobar' . CRLF . CRLF
        );
    }

    public function case_do_handshake_invalid_response_sec_websocket_accept_header()
    {
        return $this->_case_do_handshake_invalid_response(
            'HTTP/1.1 101 Switching Protocols' . CRLF .
            'Upgrade: websocket' . CRLF .
            'Connection: foobar' . CRLF .
            'Sec-WebSocket-Accept: ' . base64_encode(sha1('XXXXXXXXXXXXXXXXXXXXXX==' . Websocket\Protocol\Rfc6455::GUID, true)) . CRLF .
            'Sec-WebSocket-Version: 13' . CRLF . CRLF
        );
    }

    protected function _case_do_handshake_invalid_response($response)
    {
        $self = $this;

        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $sock = new \Mock\Hoa\Websocket\Socket(),
                $this->mockGenerator->orphanize('__construct'),
                $node     = new \Mock\Hoa\Websocket\Node(),
                $socket   = new Socket\Client('tcp://*:1234'),
                $endPoint = '/foobar',
                $client   = new \Mock\Hoa\Websocket\Client($socket, $endPoint),
                $host     = 'example.org',
                $client->setHost($host),
                $challenge = 'Y2FzZV9kb19oYW5kc2hhaA==',

                $this->calling($sock)->isSecured   = true,
                $this->calling($socket)->getSocket = $sock,
                $this->calling($socket)->connect   = true,
                $this->calling($socket)->enableEncryption = function ($_enable, $_type, $_sessionStream) use ($self, $socket) {
                    $self
                        ->boolean($_enable)
                            ->isTrue()
                        ->integer($_type)
                            ->isEqualTo($socket::ENCRYPTION_TLS)
                        ->variable($_sessionStream)
                            ->isNull();

                    return true;
                },
                $this->calling($socket)->setStreamBlocking = function ($_block) use ($self) {
                    $self
                        ->boolean($_block)
                            ->isTrue();

                    return true;
                },
                $this->calling($socket)->writeAll = function ($_data) use ($self, $endPoint, $host, $challenge) {
                    $self
                        ->string($_data)
                            ->isEqualTo(
                                'GET ' . $endPoint . ' HTTP/1.1' . CRLF .
                                'Host: ' . $host . CRLF .
                                'User-Agent: Hoa' . CRLF .
                                'Upgrade: WebSocket' . CRLF .
                                'Connection: Upgrade' . CRLF .
                                'Pragma: no-cache' . CRLF .
                                'Cache-Control: no-cache' . CRLF .
                                'Sec-WebSocket-Key: ' . $challenge . CRLF .
                                'Sec-WebSocket-Version: 13' . CRLF . CRLF
                            );

                    return strlen($_data);
                },
                $this->calling($socket)->read = function ($_size) use ($self, $challenge, $response) {
                    $self
                        ->integer($_size)
                            ->isEqualTo(2048);

                    return $response;
                },
                $this->calling($client)->getNewChallenge = $challenge
            )
            ->exception(function () use ($client) {
                $this->invoke($client)->doHandshake();
            })
                ->isInstanceOf(Websocket\Exception\BadProtocol::class);
    }

    public function case_get_new_challenge()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $client = new \Mock\Hoa\Websocket\Client()
            )
            ->when(function () use ($client) {
                for ($i = 0; $i < 1000; ++$i) {
                    $this
                        ->string($client->getNewChallenge())
                            ->matches('/^[A-Za-z0-9]{21}[AQgw]==$/');
                }
            });
    }

    public function case_close()
    {
        return $this->_case_close(true);
    }

    public function case_close_with_no_protocol_implementation()
    {
        return $this->_case_close(null);
    }

    protected function _case_close($protocolCalledValue)
    {
        $self = $this;

        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node   = new \Mock\Hoa\Websocket\Node(),
                $socket = new Socket\Client('tcp://*:1234'),
                $this->mockGenerator->orphanize('__construct'),
                $protocol = new \Mock\Hoa\Websocket\Protocol\Rfc6455(),
                $client   = new SUT($socket),

                $code   = Websocket\Connection::CLOSE_NORMAL,
                $reason = 'foobar',
                $mask   = true,

                $this->calling($node)->getProtocolImplementation = $protocolCalledValue ? $protocol : null,

                $this->calling($protocol)->close = function ($_code, $_reason, $_mask) use (&$calledA, $self, $code, $reason, $mask) {
                    $calledA = true;

                    $self
                        ->integer($_code)
                            ->isEqualTo($code)
                        ->string($reason)
                            ->isEqualTo($reason)
                        ->boolean($_mask)
                            ->isEqualTo($mask);

                    return;
                },

                $this->calling($socket)->getCurrentNode = $node,
                $this->calling($socket)->mute           = function () use (&$calledB) {
                    $calledB = true;

                    return;
                },
                $this->calling($socket)->setStreamTimeout = function ($_second, $_millisecond) use (&$calledC, $self) {
                    $calledC = true;

                    $self
                        ->integer($_second)
                            ->isEqualTo(0)
                        ->integer($_millisecond)
                            ->isEqualTo(30000);

                    return true;
                },
                $this->calling($socket)->read = function ($_size) use (&$calledD, $self) {
                    $calledD = true;

                    $self
                        ->integer($_size)
                            ->isEqualTo(1);

                    return 'x';
                }
            )
            ->when($result = $client->close($code, $reason))
            ->then
                ->variable($result)
                    ->isNull()
                ->variable($calledA)
                    ->isEqualTo($protocolCalledValue)
                ->boolean($calledB)
                    ->isTrue()
                ->boolean($calledC)
                    ->isTrue()
                ->boolean($calledD)
                    ->isTrue()
                ->boolean($socket->isDisconnected())
                    ->isTrue();
    }

    public function case_set_end_point()
    {
        $this
            ->given(
                $socket   = new Socket\Client('tcp://*:1234'),
                $endPoint = '/foobar',
                $client   = new SUT($socket, $endPoint)
            )
            ->when($result = $this->invoke($client)->setEndPoint('/bazqux'))
            ->then
                ->string($result)
                    ->isEqualTo($endPoint);
    }

    public function case_get_end_point()
    {
        $this
            ->given(
                $socket   = new Socket\Client('tcp://*:1234'),
                $endPoint = '/bazqux',
                $client   = new SUT($socket, '/foobar'),
                $this->invoke($client)->setEndPoint($endPoint)
            )
            ->when($result = $client->getEndPoint())
            ->then
                ->string($result)
                    ->isEqualTo($endPoint);
    }

    public function case_set_response()
    {
        $this
            ->given(
                $socket   = new Socket\Client('tcp://*:1234'),
                $endPoint = '/',
                $response = new Http\Response(),
                $client   = new SUT($socket, $endPoint, $response)
            )
            ->when($result = $client->setResponse(new Http\Response()))
            ->then
                ->object($result)
                    ->isIdenticalTo($response);
    }

    public function case_get_response()
    {
        $this
            ->given(
                $socket   = new Socket\Client('tcp://*:1234'),
                $endPoint = '/',
                $response = new Http\Response(),
                $client   = new SUT($socket, $endPoint, new Http\Response()),
                $this->invoke($client)->setResponse($response)
            )
            ->when($result = $client->getResponse())
            ->then
                ->object($result)
                    ->isIdenticalTo($response);
    }

    public function case_set_host()
    {
        $this
            ->given(
                $socket = new Socket\Client('tcp://*:1234'),
                $client = new SUT($socket)
            )
            ->when($result = $client->setHost('example.org'))
            ->then
                ->variable($result)
                    ->isNull();
    }

    public function case_get_host()
    {
        $this
            ->given(
                $socket = new Socket\Client('tcp://*:1234'),
                $client = new SUT($socket),
                $host   = 'example.org',
                $client->setHost($host)
            )
            ->when($result = $client->getHost())
            ->then
                ->string($result)
                    ->isEqualTo($host);
    }

    public function case_get_an_undefined_host()
    {
        unset($_SERVER);

        $this
            ->given(
                $socket = new Socket\Client('tcp://*:1234'),
                $client = new SUT($socket)
            )
            ->when($result = $client->getHost())
            ->then
                ->variable($result)
                    ->isNull();
    }

    public function case_get_a_global_defined_host()
    {
        $this
            ->given(
                $socket               = new Socket\Client('tcp://*:1234'),
                $host                 = 'example.org',
                $_SERVER['HTTP_HOST'] = $host,
                $client               = new SUT($socket)
            )
            ->when($result = $client->getHost())
            ->then
                ->string($result)
                    ->isEqualTo($host);
    }
}
