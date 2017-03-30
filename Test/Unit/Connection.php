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
use Hoa\Socket;
use Hoa\Test;
use Hoa\Websocket;
use Mock\Hoa\Websocket\Connection as SUT;

/**
 * Class \Hoa\Websocket\Test\Unit\Connection.
 *
 * Test suite for the WebSocket connection class.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
class Connection extends Test\Unit\Suite
{
    public function case_opcodes()
    {
        $this
            ->then
                ->integer(SUT::OPCODE_CONTINUATION_FRAME)
                    ->isEqualTo(0x0)
                ->integer(SUT::OPCODE_TEXT_FRAME)
                    ->isEqualTo(0x1)
                ->integer(SUT::OPCODE_BINARY_FRAME)
                    ->isEqualTo(0x2)
                ->integer(SUT::OPCODE_CONNECTION_CLOSE)
                    ->isEqualTo(0x8)
                ->integer(SUT::OPCODE_PING)
                    ->isEqualTo(0x9)
                ->integer(SUT::OPCODE_PONG)
                    ->isEqualTo(0xa);
    }

    public function case_close_codes()
    {
        $this
            ->then
                ->integer(SUT::CLOSE_NORMAL)
                    ->isEqualTo(1000)
                ->integer(SUT::CLOSE_GOING_AWAY)
                    ->isEqualTo(1001)
                ->integer(SUT::CLOSE_PROTOCOL_ERROR)
                    ->isEqualTo(1002)
                ->integer(SUT::CLOSE_DATA_ERROR)
                    ->isEqualTo(1003)
                ->integer(SUT::CLOSE_STATUS_ERROR)
                    ->isEqualTo(1005)
                ->integer(SUT::CLOSE_ABNORMAL)
                    ->isEqualTo(1006)
                ->integer(SUT::CLOSE_MESSAGE_ERROR)
                    ->isEqualTo(1007)
                ->integer(SUT::CLOSE_POLICY_ERROR)
                    ->isEqualTo(1008)
                ->integer(SUT::CLOSE_MESSAGE_TOO_BIG)
                    ->isEqualTo(1009)
                ->integer(SUT::CLOSE_EXTENSION_MISSING)
                    ->isEqualTo(1010)
                ->integer(SUT::CLOSE_SERVER_ERROR)
                    ->isEqualTo(1011)
                ->integer(SUT::CLOSE_TLS)
                    ->isEqualTo(1015);
    }

    public function case_constructor()
    {
        $this
            ->given($socket = new Socket\Client('tcp://*:1234'))
            ->when($result = new SUT($socket))
            ->then
                ->object($result->getConnection())
                    ->isIdenticalTo($socket)
                ->string($socket->getNodeName())
                    ->isEqualTo(Websocket\Node::class)
                ->let($listener = $this->invoke($result)->getListener())
                ->object($listener)
                    ->isInstanceOf(Event\Listener::class)
                ->boolean($listener->listenerExists('open'))
                    ->isTrue()
                ->boolean($listener->listenerExists('message'))
                    ->isTrue()
                ->boolean($listener->listenerExists('binary-message'))
                    ->isTrue()
                ->boolean($listener->listenerExists('ping'))
                    ->isTrue()
                ->boolean($listener->listenerExists('close-before'))
                    ->isTrue()
                ->boolean($listener->listenerExists('close'))
                    ->isTrue()
                ->boolean($listener->listenerExists('error'))
                    ->isTrue();
    }

    public function case_run_do_handshake()
    {
        $self = $this;

        $this
            ->given(
                $socket     = new Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),

                $connection->on(
                    'open',
                    function (Event\Bucket $bucket) use (&$calledA, $self) {
                        $calledA = true;

                        $self
                            ->variable($bucket->getData())
                                ->isNull();

                        return;
                    }
                ),

                $this->calling($node)->getHandshake      = FAILED,
                $this->calling($connection)->doHandshake = function () use (&$calledB) {
                    $calledB = true;

                    return;
                }
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($calledA)
                    ->isTrue()
                ->boolean($calledB)
                    ->isTrue();
    }

    public function case_run_cannot_read_the_frame()
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation(new Websocket\Protocol\Rfc6455($socket)),

                $this->calling($socket)->read[1] =
                    chr(
                        (0x1 << 7)
                      | (0x1 << 6)
                      | (0x0 << 5)
                      | (0x0 << 4)
                      | Websocket\Connection::OPCODE_TEXT_FRAME
                    ),
                $this->calling($socket)->read[2] =
                    chr(
                        (0x1 << 7)
                      | 42
                    ),
                $this->calling($socket)->getCurrentNode = $node,
                $this->calling($node)->getHandshake     = SUCCEED,
                $this->calling($connection)->close      = function ($_code, $_reason) use (&$called, $self) {
                    $called = true;

                    $self
                        ->integer($_code)
                            ->isEqualTo(SUT::CLOSE_PROTOCOL_ERROR)
                        ->string($_reason)
                            ->isNotEmpty();

                    return;
                }
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($called)
                    ->isTrue();
    }

    public function case_run_client_messages_must_be_masked()
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Server('tcp://*:1234'),
                $connection = new \Mock\Hoa\Websocket\Server($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake  = SUCCEED,
                $this->calling($protocol)->readFrame = [
                    'fin'     => 0x1,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => SUT::OPCODE_TEXT_FRAME,
                    'mask'    => 0x0,
                    'length'  => 6,
                    'message' => 'foobar'
                ],
                $this->calling($connection)->close = function ($_code, $_reason) use (&$called, $self) {
                    $called = true;

                    $self
                        ->integer($_code)
                            ->isEqualTo(SUT::CLOSE_MESSAGE_ERROR)
                        ->string($_reason)
                            ->isEqualTo('All messages from the client must be masked.');

                    return;
                }
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($called)
                    ->isTrue();
    }

    public function case_run_ping_opcode()
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake  = SUCCEED,
                $this->calling($protocol)->readFrame = [
                    'fin'     => 0x1,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => SUT::OPCODE_PING,
                    'mask'    => 0x1,
                    'length'  => 6,
                    'message' => 'foobar'
                ],
                $this->calling($protocol)->writeFrame = function ($_message, $_opcode, $_mask) use (&$calledA, $self) {
                    $calledA = true;

                    $self
                        ->string($_message)
                            ->isEqualTo('foobar')
                        ->integer($_opcode)
                            ->isEqualTo(SUT::OPCODE_PONG)
                        ->boolean($_mask)
                            ->isTrue();

                    return;
                },

                $connection->on(
                    'ping',
                    function (Event\Bucket $bucket) use (&$calledB, $self) {
                        $calledB = true;

                        $self
                            ->array($bucket->getData())
                            ->isEqualTo([
                                'message' => 'foobar'
                            ]);

                        return;
                    }
                )
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($calledA)
                    ->isTrue()
                ->boolean($calledB)
                    ->isTrue();
    }

    public function case_run_ping_opcode_not_fin()
    {
        return $this->_case_run_ping_opcode_with_invalid_frame([
            'fin'     => 0x0,
            'rsv1'    => 0x0,
            'rsv2'    => 0x0,
            'rsv3'    => 0x0,
            'opcode'  => SUT::OPCODE_PING,
            'mask'    => 0x1,
            'length'  => 6,
            'message' => 'foobar'
        ]);
    }

    public function case_run_ping_opcode_with_length_too_big()
    {
        return $this->_case_run_ping_opcode_with_invalid_frame([
            'fin'     => 0x1,
            'rsv1'    => 0x0,
            'rsv2'    => 0x0,
            'rsv3'    => 0x0,
            'opcode'  => SUT::OPCODE_PING,
            'mask'    => 0x1,
            'length'  => 0x7e,
            'message' => 'foobar…'
        ]);
    }

    protected function _case_run_ping_opcode_with_invalid_frame(array $frame)
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake   = SUCCEED,
                $this->calling($protocol)->readFrame  = $frame,
                $this->calling($protocol)->writeFrame = function ($_message, $_opcode, $_mask) use (&$calledA) {
                    $calledA = true;

                    return;
                },
                $this->calling($connection)->close = function ($_code, $_reason) use (&$calledB, $self) {
                    $calledB = true;

                    $self
                        ->integer($_code)
                            ->isEqualTo(SUT::CLOSE_PROTOCOL_ERROR)
                        ->variable($_reason)
                            ->isNull();

                    return;
                },

                $connection->on(
                    'ping',
                    function (Event\Bucket $bucket) use (&$calledC) {
                        $calledC = true;

                        return;
                    }
                )
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->variable($result)
                    ->isNull()
                ->variable($calledA)
                    ->isNull()
                ->boolean($calledB)
                    ->isTrue()
                ->variable($calledC)
                    ->isNull();
    }

    public function case_run_pong_opcode()
    {
        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake  = SUCCEED,
                $this->calling($protocol)->readFrame = [
                    'fin'     => 0x1,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => SUT::OPCODE_PONG,
                    'mask'    => 0x1,
                    'length'  => 6,
                    'message' => 'foobar'
                ],
                $this->calling($protocol)->writeFrame = function ($_message, $_opcode, $_mask) use (&$called) {
                    $called = true;

                    return;
                }
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->variable($result)
                    ->isNull()
                ->variable($called)
                    ->isNull();
    }

    public function case_run_pong_opcode_not_fin()
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake  = SUCCEED,
                $this->calling($protocol)->readFrame = [
                    'fin'     => 0x0,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => SUT::OPCODE_PONG,
                    'mask'    => 0x1,
                    'length'  => 6,
                    'message' => 'foobar'
                ],
                $this->calling($protocol)->writeFrame = function ($_message, $_opcode, $_mask) use (&$calledA) {
                    $calledA = true;

                    return;
                },
                $this->calling($connection)->close = function ($_code, $_reason) use (&$calledB, $self) {
                    $calledB = true;

                    $self
                        ->integer($_code)
                            ->isEqualTo(SUT::CLOSE_PROTOCOL_ERROR)
                        ->variable($_reason)
                            ->isNull();

                    return;
                }
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->variable($result)
                    ->isNull()
                ->variable($calledA)
                    ->isNull()
                ->boolean($calledB)
                    ->isTrue();
    }

    public function case_run_text_frame_opcode_with_fragments()
    {
        return $this->_case_run_x_frame_opcode_with_fragments(SUT::OPCODE_TEXT_FRAME);
    }

    public function case_run_binary_frame_opcode_with_fragments()
    {
        return $this->_case_run_x_frame_opcode_with_fragments(SUT::OPCODE_BINARY_FRAME);
    }

    protected function _case_run_x_frame_opcode_with_fragments($opcode)
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake         = SUCCEED,
                $this->calling($node)->getNumberOfFragments = 42,
                $this->calling($protocol)->readFrame        = [
                    'fin'     => 0x1,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => $opcode,
                    'mask'    => 0x1,
                    'length'  => 6,
                    'message' => 'foobar'
                ],
                $this->calling($protocol)->writeFrame = function ($_message, $_opcode, $_mask) use (&$calledA) {
                    $calledA = true;

                    return;
                },
                $this->calling($connection)->close = function ($_code, $_reason) use (&$calledB, $self) {
                    $calledB = true;

                    $self
                        ->integer($_code)
                            ->isEqualTo(SUT::CLOSE_PROTOCOL_ERROR)
                        ->variable($_reason)
                            ->isNull();

                    return;
                }
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->variable($result)
                    ->isNull()
                ->variable($calledA)
                    ->isNull()
                ->boolean($calledB)
                    ->isTrue();
    }

    public function case_run_text_frame_opcode_with_invalid_message_encoding()
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake  = SUCCEED,
                $this->calling($protocol)->readFrame = [
                    'fin'     => 0x1,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => SUT::OPCODE_TEXT_FRAME,
                    'mask'    => 0x1,
                    'length'  => 6,
                    'message' => iconv('UTF-8', 'UTF-16', '😄')
                ],
                $this->calling($protocol)->writeFrame = function ($_message, $_opcode, $_mask) use (&$calledA) {
                    $calledA = true;

                    return;
                },
                $this->calling($connection)->close = function ($_code, $_reason) use (&$calledB, $self) {
                    $calledB = true;

                    $self
                        ->integer($_code)
                            ->isEqualTo(SUT::CLOSE_MESSAGE_ERROR)
                        ->variable($_reason)
                            ->isNull();

                    return;
                }
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->variable($result)
                    ->isNull()
                ->variable($calledA)
                    ->isNull()
                ->boolean($calledB)
                    ->isTrue();
    }

    public function case_run_text_frame_opcode()
    {
        return $this->_case_run_x_frame_opcode(SUT::OPCODE_TEXT_FRAME);
    }

    public function case_run_binary_frame_opcode()
    {
        return $this->_case_run_x_frame_opcode(SUT::OPCODE_BINARY_FRAME);
    }

    protected function _case_run_x_frame_opcode($opcode)
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake  = SUCCEED,
                $this->calling($protocol)->readFrame = [
                    'fin'     => 0x1,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => $opcode,
                    'mask'    => 0x1,
                    'length'  => 6,
                    'message' => 'foobar'
                ],
                $this->calling($protocol)->writeFrame = function ($_message, $_opcode, $_mask) use (&$calledA) {
                    $calledA = true;

                    return;
                },
                $this->calling($connection)->close = function ($_code, $_reason) use (&$calledB) {
                    $calledB = true;

                    return;
                },

                $connection->on(
                    SUT::OPCODE_TEXT_FRAME === $opcode ? 'message' : 'binary-message',
                    function (Event\Bucket $bucket) use (&$calledC, $self) {
                        $calledC = true;

                        $self
                            ->array($bucket->getData())
                                ->isEqualTo([
                                    'message' => 'foobar'
                                ]);

                        return;
                    }
                )
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->variable($result)
                    ->isNull()
                ->variable($calledA)
                    ->isNull()
                ->variable($calledB)
                    ->isNull()
                ->boolean($calledC)
                    ->isTrue();
    }

    public function case_run_text_frame_opcode_with_an_exception_from_the_listener()
    {
        return $this->_case_run_x_frame_opcode_with_an_exception_from_the_listener(SUT::OPCODE_TEXT_FRAME);
    }

    public function case_run_binary_frame_opcode_with_an_exception_from_the_listener()
    {
        return $this->_case_run_x_frame_opcode_with_an_exception_from_the_listener(SUT::OPCODE_BINARY_FRAME);
    }

    protected function _case_run_x_frame_opcode_with_an_exception_from_the_listener($opcode)
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake  = SUCCEED,
                $this->calling($protocol)->readFrame = [
                    'fin'     => 0x1,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => $opcode,
                    'mask'    => 0x1,
                    'length'  => 6,
                    'message' => 'foobar'
                ],
                $this->calling($protocol)->writeFrame = function ($_message, $_opcode, $_mask) use (&$calledA) {
                    $calledA = true;

                    return;
                },
                $this->calling($connection)->close = function ($_code, $_reason) use (&$calledB) {
                    $calledB = true;

                    return;
                },

                $connection->on(
                    SUT::OPCODE_TEXT_FRAME === $opcode ? 'message' : 'binary-message',
                    function (Event\Bucket $bucket) use (&$calledC, $self) {
                        $calledC = true;

                        $self
                            ->array($bucket->getData())
                                ->isEqualTo([
                                    'message' => 'foobar'
                                ]);

                        throw new \RuntimeException('bang');
                    }
                ),
                $connection->on(
                    'error',
                    function (Event\Bucket $bucket) use (&$calledD, $self) {
                        $calledD = true;

                        $self
                            ->let($data = $bucket->getData())
                            ->array($data)
                                ->hasSize(1)
                                ->hasKey('exception')
                            ->exception($data['exception'])
                                ->isInstanceOf(\RuntimeException::class)
                                ->hasMessage('bang');

                        return;
                    }
                )
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->variable($result)
                    ->isNull()
                ->variable($calledA)
                    ->isNull()
                ->variable($calledB)
                    ->isNull()
                ->boolean($calledC)
                    ->isTrue()
                ->boolean($calledD)
                    ->isTrue();
    }

    public function case_run_incomplete_text_frame_opcode()
    {
        return $this->_case_run_incomplete_x_frame_opcode(SUT::OPCODE_TEXT_FRAME);
    }

    public function case_run_incomplete_binary_frame_opcode()
    {
        return $this->_case_run_incomplete_x_frame_opcode(SUT::OPCODE_BINARY_FRAME);
    }

    protected function _case_run_incomplete_x_frame_opcode($opcode)
    {
        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake  = SUCCEED,
                $this->calling($protocol)->readFrame = [
                    'fin'     => 0x0,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => $opcode,
                    'mask'    => 0x1,
                    'length'  => 6,
                    'message' => 'foobar'
                ],
                $this->calling($protocol)->writeFrame = function ($_message, $_opcode, $_mask) use (&$calledA) {
                    $calledA = true;

                    return;
                },
                $this->calling($connection)->close = function ($_code, $_reason) use (&$calledB) {
                    $calledB = true;

                    return;
                },

                $connection->on(
                    'message',
                    function (Event\Bucket $bucket) use (&$calledC) {
                        $calledC = true;

                        return;
                    }
                )
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->variable($result)
                    ->isNull()
                ->variable($calledA)
                    ->isNull()
                ->variable($calledB)
                    ->isNull()
                ->variable($calledC)
                    ->isNull()
                ->boolean($node->isMessageComplete())
                    ->isFalse()
                ->integer($node->getNumberOfFragments())
                    ->isEqualTo(1)
                ->string($node->getFragmentedMessage())
                    ->isEqualTo('foobar')
                ->boolean($node->isBinary())
                    ->isEqualTo(SUT::OPCODE_BINARY_FRAME === $opcode);
    }

    public function case_run_continuation_frame_opcode_with_fragments()
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake         = SUCCEED,
                $this->calling($node)->getNumberOfFragments = 42,
                $this->calling($protocol)->readFrame        = [
                    'fin'     => 0x0,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => SUT::OPCODE_CONTINUATION_FRAME,
                    'mask'    => 0x1,
                    'length'  => 6,
                    'message' => 'foobar'
                ],
                $this->calling($connection)->close = function ($_code, $_reason) use (&$called) {
                    $called = true;

                    $self
                        ->integer($_code)
                            ->isEqualTo(SUT::CLOSE_PROTOCOL_ERROR)
                        ->variable($_reason)
                            ->isNull();

                    return;
                }
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->variable($result)
                    ->isNull()
                ->variable($called)
                    ->isNull();
    }

    public function case_run_text_frame_and_continuation_frame_opcodes()
    {
        return $this->_case_run_x_frame_and_continuation_frame_opcodes(SUT::OPCODE_TEXT_FRAME);
    }

    public function case_run_binary_frame_and_continuation_frame_opcodes()
    {
        return $this->_case_run_x_frame_and_continuation_frame_opcodes(SUT::OPCODE_BINARY_FRAME);
    }

    protected function _case_run_x_frame_and_continuation_frame_opcodes($opcode)
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake     = SUCCEED,
                $this->calling($protocol)->readFrame[1] = [
                    'fin'     => 0x0,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => $opcode,
                    'mask'    => 0x1,
                    'length'  => 3,
                    'message' => 'foo'
                ],
                $this->calling($protocol)->readFrame[2] = [
                    'fin'     => 0x0,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => SUT::OPCODE_CONTINUATION_FRAME,
                    'mask'    => 0x1,
                    'length'  => 3,
                    'message' => 'bar'
                ],
                $this->calling($protocol)->readFrame[3] = [
                    'fin'     => 0x1,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => SUT::OPCODE_CONTINUATION_FRAME,
                    'mask'    => 0x1,
                    'length'  => 3,
                    'message' => 'baz'
                ],
                $this->calling($socket)->getCurrentNode = $node,

                $connection->on(
                    SUT::OPCODE_TEXT_FRAME === $opcode ? 'message' : 'binary-message',
                    function (Event\Bucket $bucket) use (&$called, $self) {
                        $called = true;

                        $self
                            ->array($bucket->getData())
                            ->isEqualTo([
                                'message' => 'foobarbaz'
                            ]);

                        return;
                    }
                )
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->integer($node->getNumberOfFragments())
                    ->isEqualTo(1)
                ->string($node->getFragmentedMessage())
                    ->isEqualTo('foo')
                ->boolean($node->isMessageComplete())
                    ->isFalse()
                ->variable($called)
                    ->isNull()

            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->integer($node->getNumberOfFragments())
                    ->isEqualTo(2)
                ->string($node->getFragmentedMessage())
                    ->isEqualTo('foobar')
                ->boolean($node->isMessageComplete())
                    ->isFalse()
                ->variable($called)
                    ->isNull()

            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->integer($node->getNumberOfFragments())
                    ->isEqualTo(0)
                ->variable($node->getFragmentedMessage())
                    ->isNull()
                ->boolean($node->isMessageComplete())
                    ->isTrue()
                ->boolean($called)
                    ->isTrue();
    }

    public function case_run_text_frame_and_continuation_frame_opcodes_with_an_exception_from_the_listener()
    {
        return $this->_case_run_x_frame_and_continuation_frame_opcodes_with_an_exception_from_the_listener(SUT::OPCODE_TEXT_FRAME);
    }

    public function case_run_binary_frame_and_continuation_frame_opcodes_with_an_exception_from_the_listener()
    {
        return $this->_case_run_x_frame_and_continuation_frame_opcodes_with_an_exception_from_the_listener(SUT::OPCODE_BINARY_FRAME);
    }

    protected function _case_run_x_frame_and_continuation_frame_opcodes_with_an_exception_from_the_listener($opcode)
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake     = SUCCEED,
                $this->calling($protocol)->readFrame[1] = [
                    'fin'     => 0x0,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => $opcode,
                    'mask'    => 0x1,
                    'length'  => 3,
                    'message' => 'foo'
                ],
                $this->calling($protocol)->readFrame[2] = [
                    'fin'     => 0x0,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => SUT::OPCODE_CONTINUATION_FRAME,
                    'mask'    => 0x1,
                    'length'  => 3,
                    'message' => 'bar'
                ],
                $this->calling($protocol)->readFrame[3] = [
                    'fin'     => 0x1,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => SUT::OPCODE_CONTINUATION_FRAME,
                    'mask'    => 0x1,
                    'length'  => 3,
                    'message' => 'baz'
                ],
                $this->calling($socket)->getCurrentNode = $node,

                $connection->on(
                    SUT::OPCODE_TEXT_FRAME === $opcode ? 'message' : 'binary-message',
                    function (Event\Bucket $bucket) use (&$calledA, $self) {
                        $calledA = true;

                        $self
                            ->array($bucket->getData())
                            ->isEqualTo([
                                'message' => 'foobarbaz'
                            ]);

                        throw new \RuntimeException('bang');
                    }
                ),
                $connection->on(
                    'error',
                    function (Event\Bucket $bucket) use (&$calledB, $self) {
                        $calledB = true;

                        $self
                            ->let($data = $bucket->getData())
                            ->array($data)
                                ->hasSize(1)
                                ->hasKey('exception')
                            ->exception($data['exception'])
                                ->isInstanceOf(\RuntimeException::class)
                                ->hasMessage('bang');

                        return;
                    }
                )
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->integer($node->getNumberOfFragments())
                    ->isEqualTo(1)
                ->string($node->getFragmentedMessage())
                    ->isEqualTo('foo')
                ->boolean($node->isMessageComplete())
                    ->isFalse()
                ->variable($calledA)
                    ->isNull()
                ->variable($calledB)
                    ->isNull()

            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->integer($node->getNumberOfFragments())
                    ->isEqualTo(2)
                ->string($node->getFragmentedMessage())
                    ->isEqualTo('foobar')
                ->boolean($node->isMessageComplete())
                    ->isFalse()
                ->variable($calledA)
                    ->isNull()
                ->variable($calledB)
                    ->isNull()

            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->integer($node->getNumberOfFragments())
                    ->isEqualTo(0)
                ->variable($node->getFragmentedMessage())
                    ->isNull()
                ->boolean($node->isMessageComplete())
                    ->isTrue()
                ->boolean($calledA)
                    ->isTrue()
                ->boolean($calledB)
                    ->isTrue();
    }

    public function case_run_text_frame_and_continuation_frame_opcodes_with_invalid_message_encoding()
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $this->calling($node)->getHandshake     = SUCCEED,
                $this->calling($protocol)->readFrame[1] = [
                    'fin'     => 0x0,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => SUT::OPCODE_TEXT_FRAME,
                    'mask'    => 0x1,
                    'length'  => 3,
                    'message' => 'foo'
                ],
                $this->calling($protocol)->readFrame[2] = [
                    'fin'     => 0x1,
                    'rsv1'    => 0x0,
                    'rsv2'    => 0x0,
                    'rsv3'    => 0x0,
                    'opcode'  => SUT::OPCODE_CONTINUATION_FRAME,
                    'mask'    => 0x1,
                    'length'  => 6,
                    'message' => iconv('UTF-8', 'UTF-16', '😄')
                ],
                $this->calling($socket)->getCurrentNode = $node,

                $this->calling($connection)->close = function ($_code, $_reason) use (&$called, $self) {
                    $called = true;

                    $self
                        ->integer($_code)
                            ->isEqualTo(SUT::CLOSE_MESSAGE_ERROR)
                        ->variable($_reason)
                            ->isNull();

                    return;
                }
            )
            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->integer($node->getNumberOfFragments())
                    ->isEqualTo(1)
                ->string($node->getFragmentedMessage())
                    ->isEqualTo('foo')
                ->boolean($node->isMessageComplete())
                    ->isFalse()
                ->variable($called)
                    ->isNull()

            ->when($result = $this->invoke($connection)->_run($node))
            ->then
                ->integer($node->getNumberOfFragments())
                    ->isEqualTo(0)
                ->variable($node->getFragmentedMessage())
                    ->isNull()
                ->boolean($node->isMessageComplete())
                    ->isTrue()
                ->boolean($called)
                    ->isTrue();
    }

    public function case__send()
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $message = 'foobar',
                $opcode  = SUT::OPCODE_TEXT_FRAME,
                $end     = true,

                $this->calling($node)->getHandshake = true,
                $this->calling($protocol)->send     = function ($_message, $_opcode, $_end, $_mask) use (&$called, $self, $message, $opcode, $end) {
                    $called = true;

                    $self
                        ->string($_message)
                            ->isEqualTo($message)
                        ->integer($_opcode)
                            ->isEqualTo($opcode)
                        ->boolean($_end)
                            ->isEqualTo($end)
                        ->boolean($_mask)
                            ->isFalse();

                    return 42;
                },

                $closure = $this->invoke($connection)->_send($message, $node)
            )
            ->when($result = new \ReflectionFunction($closure))
            ->then
                ->integer($result->getNumberOfRequiredParameters())
                    ->isEqualTo(2)

            ->when($result = $closure($opcode, $end))
            ->then
                ->integer($result)
                    ->isEqualTo(42)
                ->boolean($called)
                    ->isTrue();
    }

    public function case__send_with_no_handshake()
    {
        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $message = 'foobar',
                $opcode  = SUT::OPCODE_TEXT_FRAME,
                $end     = true,

                $this->calling($protocol)->send = function ($_message, $_opcode, $_end, $_mask) use (&$called) {
                    $called = true;

                    return 42;
                },

                $closure = $this->invoke($connection)->_send($message, $node)
            )
            ->when($result = new \ReflectionFunction($closure))
            ->then
                ->integer($result->getNumberOfRequiredParameters())
                    ->isEqualTo(2)

            ->when($result = $closure($opcode, $end))
            ->then
                ->variable($result)
                    ->isNull()
                ->variable($called)
                    ->isNull();
    }

    public function case_close_with_no_protocol()
    {
        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),

                $this->calling($socket)->getCurrentNode = $node
            )
            ->when($result = $connection->close(SUT::CLOSE_DATA_ERROR, 'foo'))
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($socket->isDisconnected())
                    ->isTrue();
    }

    public function case_close()
    {
        $self = $this;

        $this
            ->given(
                $socket     = new \Mock\Hoa\Socket\Client('tcp://*:1234'),
                $connection = new SUT($socket),
                $protocol   = new \Mock\Hoa\Websocket\Protocol\Rfc6455($socket),
                $this->mockGenerator->orphanize('__construct'),
                $node = new \Mock\Hoa\Websocket\Node(),
                $node->setProtocolImplementation($protocol),

                $code   = SUT::CLOSE_DATA_ERROR,
                $reason = 'foo',

                $this->calling($protocol)->close = function ($_code, $_reason) use (&$called, $self, $code, $reason) {
                    $called = true;

                    $self
                        ->integer($_code)
                            ->isEqualTo($code)
                        ->string($_reason)
                            ->isEqualTo($reason);

                    return;
                },
                $this->calling($socket)->getCurrentNode = $node
            )
            ->when($result = $connection->close($code, $reason))
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($socket->isDisconnected())
                    ->isTrue()
                ->boolean($called)
                    ->isTrue();
    }
}
