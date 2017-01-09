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

namespace Hoa\Websocket\Test\Integration\Protocol;

use Hoa\Event;
use Hoa\Socket;
use Hoa\Test;
use Hoa\Websocket;

/**
 * Class \Hoa\Websocket\Test\Integration\Protocol\Rfc6455.
 *
 * Test suite of the RFC6455 protocol.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
class Rfc6455 extends Test\Integration\Suite
{
    public function case_server()
    {
        $numberOfTestsToCompute = $this->getNumberOfTestsToCompute();

        $server = new Websocket\Server(new Socket\Server('ws://127.0.0.1:1234'));
        $server->on(
            'ping',
            function (Event\Bucket $bucket) use (&$numberOfTestsToCompute) {
                $bucket->getSource()->close();

                if ('skip' === substr($bucket->getData()['message'], 0, 4)) {
                    return;
                }

                if (0 >= --$numberOfTestsToCompute) {
                    throw new StopServerException('Boom!');
                }
            }
        );
        $server->on(
            'message',
            function (Event\Bucket $bucket) use (&$numberOfTestsToCompute) {
                $source  = $bucket->getSource();
                $message = $bucket->getData()['message'];
                $source->send($message);

                if ('skip' === substr($message, 0, 4)) {
                    return;
                }

                $source->close();

                if (0 >= --$numberOfTestsToCompute) {
                    throw new StopServerException('Boom!');
                }
            }
        );
        $server->on(
            'binary-message',
            function (Event\Bucket $bucket) use (&$numberOfTestsToCompute) {
                $source  = $bucket->getSource();
                $message = $bucket->getData()['message'];
                $source->send(
                    $message,
                    null,
                    Websocket\Server::OPCODE_BINARY_FRAME
                );

                if ('skip' === substr($message, 0, 4)) {
                    return;
                }

                $source->close();

                if (0 >= --$numberOfTestsToCompute) {
                    throw new StopServerException('Boom!');
                }
            }
        );
        $server->on(
            'error',
            function (Event\Bucket $bucket) {
                throw $bucket->getData()['exception'];
            }
        );

        try {
            $server->run();
        } catch (StopServerException $e) {
            $this->boolean(true)->isTrue();
        }
    }

    public function case_send_text_message_with_an_empty_payload()
    {
        return $this->_case_send_text_message_with_payload_of_size_x(0);
    }

    public function case_send_text_message_with_payload_of_size_125()
    {
        return $this->_case_send_text_message_with_payload_of_size_x(125);
    }

    public function case_send_text_message_with_payload_of_size_126()
    {
        return $this->_case_send_text_message_with_payload_of_size_x(126);
    }

    public function case_send_text_message_with_payload_of_size_127()
    {
        return $this->_case_send_text_message_with_payload_of_size_x(127);
    }

    public function case_send_text_message_with_payload_of_size_128()
    {
        return $this->_case_send_text_message_with_payload_of_size_x(128);
    }

    public function case_send_text_message_with_payload_of_size_65535()
    {
        return $this->_case_send_text_message_with_payload_of_size_x(65535);
    }

    public function case_send_text_message_with_payload_of_size_65536()
    {
        return $this->_case_send_text_message_with_payload_of_size_x(65536);
    }

    protected function _case_send_text_message_with_payload_of_size_x($size)
    {
        return $this->_case_client(
            $payload = str_repeat('a', $size),
            Websocket\Connection::OPCODE_TEXT_FRAME,
            true,
            $payload,
            Websocket\Connection::CLOSE_NORMAL
        );
    }

    public function case_send_binary_message_with_an_empty_payload()
    {
        return $this->_case_send_binary_message_with_payload_of_size_x(0);
    }

    public function case_send_binary_message_with_payload_of_size_125()
    {
        return $this->_case_send_binary_message_with_payload_of_size_x(125);
    }

    public function case_send_binary_message_with_payload_of_size_126()
    {
        return $this->_case_send_binary_message_with_payload_of_size_x(126);
    }

    public function case_send_binary_message_with_payload_of_size_127()
    {
        return $this->_case_send_binary_message_with_payload_of_size_x(127);
    }

    public function case_send_binary_message_with_payload_of_size_128()
    {
        return $this->_case_send_binary_message_with_payload_of_size_x(128);
    }

    public function case_send_binary_message_with_payload_of_size_65535()
    {
        return $this->_case_send_binary_message_with_payload_of_size_x(65535);
    }

    public function case_send_binary_message_with_payload_of_size_65536()
    {
        return $this->_case_send_binary_message_with_payload_of_size_x(65536);
    }

    protected function _case_send_binary_message_with_payload_of_size_x($size)
    {
        return $this->_case_client(
            $payload = str_repeat('a', $size),
            Websocket\Connection::OPCODE_BINARY_FRAME,
            true,
            $payload,
            Websocket\Connection::CLOSE_NORMAL
        );
    }

    public function case_send_ping_with_an_empty_payload()
    {
        return $this->_case_send_ping_with_payload_of_size_x(0);
    }

    public function case_send_ping_with_payload_of_size_64()
    {
        return $this->_case_send_ping_with_payload_of_size_x(64);
    }

    protected function _case_send_ping_with_payload_of_size_x($size)
    {
        return $this->_case_client(
            str_repeat('a', $size),
            Websocket\Connection::OPCODE_PING,
            true
        );
    }

    public function case_send_ping_with_binary_non_UTF8_payload()
    {
        return $this->_case_client(
            "\x00\xff\xfe\xfd\xfc\xfb\x00\xff",
            Websocket\Connection::OPCODE_PING,
            true
        );
    }

    public function case_send_ping_with_binary_payload_of_size_125()
    {
        return $this->_case_client(
            str_repeat("\xfe", 125),
            Websocket\Connection::OPCODE_PING,
            true
        );
    }

    public function case_send_ping_with_binary_payload_of_size_126()
    {
        return $this->_case_client(
            str_repeat("\xfe", 126),
            Websocket\Connection::OPCODE_PING,
            true
        );
    }

    public function case_send_unsolicited_pong_with_an_empty_payload()
    {
        return
            $this->_case_client(
                '',
                Websocket\Connection::OPCODE_PONG,
                true
            )
            ->send('close');
    }

    public function case_send_unsolicited_pong_with_payload_of_size_42()
    {
        return
            $this->_case_client(
                str_repeat("\xfe", 42),
                Websocket\Connection::OPCODE_PONG,
                true
            )
            ->send('close');
    }

    public function case_send_fragmented_message()
    {
        $this
            ->given(
                $client = $this->getClient(
                    Websocket\Connection::OPCODE_TEXT_FRAME,
                    'foobar',
                    Websocket\Connection::CLOSE_NORMAL,
                    $onMessageCalled,
                    $onCloseCalled
                )
            )
            ->when(
                $client->send('foo', null, Websocket\Connection::OPCODE_TEXT_FRAME, false),
                $client->send('bar', null, Websocket\Connection::OPCODE_CONTINUATION_FRAME, true),
                $client->receive(),
                $client->receive()
            )
            ->then
                ->boolean($onMessageCalled)
                    ->isTrue()
                ->boolean($onCloseCalled)
                    ->isTrue();
    }

    public function case_send_empty_text_frame_with_an_empty_payload()
    {
        return
            $this->_case_client(
                '',
                Websocket\Connection::OPCODE_TEXT_FRAME,
                true,
                '',
                Websocket\Connection::CLOSE_NORMAL
            );
    }

    public function case_send_fragmented_text_frame_with_an_empty_payload()
    {
        $this
            ->given(
                $client = $this->getClient(
                    Websocket\Connection::OPCODE_TEXT_FRAME,
                    '',
                    Websocket\Connection::CLOSE_NORMAL,
                    $onMessageCalled,
                    $onCloseCalled
                )
            )
            ->when(
                $client->send('', null, Websocket\Connection::OPCODE_TEXT_FRAME, false),
                $client->send('', null, Websocket\Connection::OPCODE_CONTINUATION_FRAME, false),
                $client->send('', null, Websocket\Connection::OPCODE_CONTINUATION_FRAME, true),
                $client->receive(),
                $client->receive()
            )
            ->then
                ->boolean($onMessageCalled)
                    ->isTrue()
                ->boolean($onCloseCalled)
                    ->isTrue();
    }

    public function case_send_text_frame_with_a_valid_UTF_8_payload()
    {
        return $this->_case_send_text_frame_with_a_valid_UTF_8_payload(
            'Hello-µ@ßöäüàá-UTF-8!!'
        );
    }

    public function case_send_text_frame_with_a_full_UTF_8_payload()
    {
        return $this->_case_send_text_frame_with_a_valid_UTF_8_payload(
            'κόσμε'
        );
    }

    protected function _case_send_text_frame_with_a_valid_UTF_8_payload($payload)
    {
        return
            $this->_case_client(
                $payload,
                Websocket\Connection::OPCODE_TEXT_FRAME,
                true,
                $payload,
                Websocket\Connection::CLOSE_NORMAL
            );
    }

    public function case_send_fragmented_text_frame_with_a_valid_UTF_8_payload()
    {
        $this
            ->given(
                $payload = 'Hello-µ@ßöäüàá-UTF-8!!',
                $client  = $this->getClient(
                    Websocket\Connection::OPCODE_TEXT_FRAME,
                    $payload,
                    Websocket\Connection::CLOSE_NORMAL,
                    $onMessageCalled,
                    $onCloseCalled
                )
            )
            ->when(
                $client->send('Hello-µ@ßöä', null, Websocket\Connection::OPCODE_TEXT_FRAME, false),
                $client->send('üàá-UTF-8!!', null, Websocket\Connection::OPCODE_CONTINUATION_FRAME, true),
                $client->receive(),
                $client->receive()
            )
            ->then
                ->boolean($onMessageCalled)
                    ->isTrue()
                ->boolean($onCloseCalled)
                    ->isTrue();
    }

    public function case_send_fragmented_text_frame_of_1_bytes_with_a_valid_UTF_8_payload()
    {
        return $this->_case_send_fragmented_text_frame_of_1_bytes_with_a_valid_UTF_8_payload(
            'Hello-µ@ßöäüàá-UTF-8!!'
        );
    }

    public function case_send_fragmented_text_frame_of_1_bytes_with_a_full_UTF_8_payload()
    {
        return $this->_case_send_fragmented_text_frame_of_1_bytes_with_a_valid_UTF_8_payload(
            'κόσμε'
        );
    }

    protected function _case_send_fragmented_text_frame_of_1_bytes_with_a_valid_UTF_8_payload($payload)
    {
        $this
            ->given(
                $client = $this->getClient(
                    Websocket\Connection::OPCODE_TEXT_FRAME,
                    $payload,
                    Websocket\Connection::CLOSE_NORMAL,
                    $onMessageCalled,
                    $onCloseCalled
                )
            )
            ->when(function () use ($client, $payload) {
                $bytes     = str_split($payload);
                $firstByte = array_shift($bytes);
                $lastByte  = array_pop($bytes);
                $client->send($firstByte, null, Websocket\Connection::OPCODE_TEXT_FRAME, false);

                foreach ($bytes as $byte) {
                    $client->send($byte, null, Websocket\Connection::OPCODE_CONTINUATION_FRAME, false);
                }

                $client->send($lastByte, null, Websocket\Connection::OPCODE_CONTINUATION_FRAME, true);

                $client->receive();
                $client->receive();

                return;
            })
            ->then
                ->boolean($onMessageCalled)
                    ->isTrue()
                ->boolean($onCloseCalled)
                    ->isTrue();
    }

    public function case_send_a_message_and_close()
    {
        $this
            ->given(
                $payload = 'foobar',
                $client  = $this->getClient(
                    Websocket\Connection::OPCODE_TEXT_FRAME,
                    $payload,
                    Websocket\Connection::CLOSE_NORMAL,
                    $onMessageCalled,
                    $onCloseCalled
                )
            )
            ->when(
                $client->send($payload, null, Websocket\Connection::OPCODE_TEXT_FRAME),
                $client->receive(),
                $client->close()
            )
            ->then
                ->boolean($onMessageCalled)
                    ->isTrue()
                ->variable($onCloseCalled)
                    ->isNull();
    }

    protected function _case_client(
        $message,
        $opcode,
        $end,
        $expectedMessage   = null,
        $expectedCloseCode = null
    ) {
        $self = $this;

        $this
            ->given(
                $client = $this->getClient(
                    $opcode,
                    $expectedMessage,
                    $expectedCloseCode,
                    $calledA,
                    $calledB
                )
            )
            ->when(
                $client->send($message, null, $opcode, $end),
                null !== $expectedMessage && $client->receive(),  // the message
                null !== $expectedCloseCode && $client->receive() // the closing code
            )
            ->then
                ->variable($calledA)
                    ->isEqualTo(null !== $expectedMessage ? true : null)
                ->variable($calledB)
                    ->isEqualTo(null !== $expectedCloseCode ? true : null);

        return $client;
    }

    protected function getClient(
        $opcode,
        $expectedMessage   = null,
        $expectedCloseCode = null,
        &$calledA          = null,
        &$calledB          = null
    ) {
        $self = $this;

        $client = new Websocket\Client(new Socket\Client('ws://127.0.0.1:1234'));
        $client->setHost('hoa.websocket.test');
        $client->on(
            Websocket\Connection::OPCODE_BINARY_FRAME === $opcode
                ? 'binary-message'
                : (Websocket\Connection::OPCODE_PING === $opcode
                    ? 'ping'
                    : 'message'),
            function (Event\Bucket $bucket) use (&$calledA, $self, $expectedMessage) {
                $calledA = true;

                $self
                    ->string($bucket->getData()['message'])
                        ->isEqualTo($expectedMessage);

                return;
            }
        );
        $client->on(
            'close',
            function (Event\Bucket $bucket) use (&$calledB, $self, $expectedCloseCode) {
                $calledB = true;

                $self
                    ->integer($bucket->getData()['code'])
                        ->isEqualTo($expectedCloseCode);

                return;
            }
        );
        $client->on(
            'error',
            function (Event\Bucket $bucket) use ($self) {
                $self
                    ->boolean(true)
                        ->isFalse();

                return;
            }
        );

        usleep(10000);
        $client->connect();

        return $client;
    }

    protected function getNumberOfTestsToCompute()
    {
        $numberOfTestsToCompute = 0;
        $object                 = new \ReflectionObject($this);
        $testCasePrefix         = $this->getMethodPrefix();

        foreach ($object->getMethods() as $method) {
            if (0 !== preg_match($testCasePrefix, $method->getName())) {
                ++$numberOfTestsToCompute;
            }
        }

        $skipped = 1;

        return $numberOfTestsToCompute - 1 - $skipped;
    }
}

/**
 * Class \Hoa\Websocket\Test\Integration\Protocol\StopServerException.
 *
 * An exception that is thrown to stop the server and exits gently.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
class StopServerException extends \RuntimeException
{
}
