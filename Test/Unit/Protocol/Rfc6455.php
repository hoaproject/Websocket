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
use Mock\Hoa\Websocket\Protocol\Rfc6455 as SUT;

/**
 * Class \Hoa\Websocket\Test\Unit\Protocol\Rfc6455.
 *
 * Test suite for the RFC6455 protocol implementation.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
class Rfc6455 extends Test\Unit\Suite
{
    public function case_guid()
    {
        $this
            ->when($result = SUT::GUID)
            ->then
                ->string($result)
                    ->isEqualTo('258EAFA5-E914-47DA-95CA-C5AB0DC85B11');
    }

    public function case_extends_generic()
    {
        $this
            ->given($socket = new Socket\Server('tcp://*:1234'))
            ->when($result = new SUT($socket))
            ->then
                ->object($result)
                    ->isInstanceOf(Websocket\Protocol\Generic::class);
    }

    public function case_do_handshake_missing_sec_websocket_key_header()
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

    public function case_do_handshake_illegal_sec_websocket_key_header_invalid_encoding()
    {
        $this
            ->given(
                $request  = new Http\Request(),
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $request['sec-websocket-key'] = 'invalid'
            )
            ->exception(function () use ($protocol, $request) {
                $protocol->doHandshake($request);
            })
                ->isInstanceOf(Websocket\Exception\BadProtocol::class);
    }

    public function case_do_handshake_illegal_sec_websocket_key_header_invalid_length()
    {
        $this
            ->given(
                $request  = new Http\Request(),
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $request['sec-websocket-key'] = base64_encode('invalid')
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

                $request['sec-websocket-key'] = 'Y2FzZV9kb19oYW5kc2hhaA==',
                $challenge                    = base64_encode(sha1($request['sec-websocket-key'] . SUT::GUID, true)),

                $this->calling($socket)->getCurrentNode = $node,
                $this->calling($node)->setHandshake     = function ($handshake) use (&$calledA, $self) {
                    $calledA = true;

                    $self
                        ->boolean($handshake)
                            ->isTrue();

                    return;
                },
                $this->calling($socket)->writeAll = function ($_data) use (&$calledB, $self, $challenge) {
                    $calledB = true;

                    $self
                        ->string($_data)
                            ->isEqualTo(
                                'HTTP/1.1 101 Switching Protocols' . CRLF .
                                'Upgrade: websocket' . CRLF .
                                'Connection: Upgrade' . CRLF .
                                'Sec-WebSocket-Accept: ' . $challenge . CRLF .
                                'Sec-WebSocket-Version: 13' . CRLF . CRLF
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

    public function case_read_frame_no_opcode()
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
                        'opcode' => Websocket\Connection::OPCODE_CONNECTION_CLOSE
                    ]);
    }

    public function case_read_frame_rsv1_is_not_equal_to_0()
    {
        return $this->_case_read_frame_rsvX_is_not_equal_to_0(0x1, 0x0, 0x0);
    }

    public function case_read_frame_rsv2_is_not_equal_to_0()
    {
        return $this->_case_read_frame_rsvX_is_not_equal_to_0(0x0, 0x1, 0x0);
    }

    public function case_read_frame_rsv3_is_not_equal_to_0()
    {
        return $this->_case_read_frame_rsvX_is_not_equal_to_0(0x0, 0x0, 0x1);
    }

    protected function _case_read_frame_rsvX_is_not_equal_to_0($rsv1, $rsv2, $rsv3)
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $this->calling($socket)->read[1] =
                    chr(
                        (0x1   << 7)
                      | ($rsv1 << 6)
                      | ($rsv2 << 5)
                      | ($rsv3 << 4)
                      | Websocket\Connection::OPCODE_TEXT_FRAME
                    ),
                $this->calling($socket)->read[2] =
                    chr(
                        (0x1 << 7)
                      | 42
                    )
            )
            ->exception(function () use ($protocol) {
                $protocol->readFrame();
            })
                ->isInstanceOf(Websocket\Exception\CloseError::class)
            ->integer($this->exception->getErrorCode())
                ->isEqualTo(Websocket\Connection::CLOSE_PROTOCOL_ERROR);
    }

    public function case_read_frame_of_length_0()
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $fin    = 0x1,
                $rsv1   = 0x0,
                $rsv2   = 0x0,
                $rsv3   = 0x0,
                $opcode = Websocket\Connection::OPCODE_TEXT_FRAME,
                $mask   = 0x0,
                $length = 0x0,

                $this->calling($socket)->read[1] =
                    chr(
                        ($fin  << 7)
                      | ($rsv1 << 6)
                      | ($rsv2 << 5)
                      | ($rsv3 << 4)
                      | $opcode
                    ),
                $this->calling($socket)->read[2] =
                    chr(
                        ($mask << 7)
                      | $length
                    )
            )
            ->when($result = $protocol->readFrame())
            ->then
                ->array($result)
                    ->isEqualTo([
                        'fin'     => $fin,
                        'rsv1'    => $rsv1,
                        'rsv2'    => $rsv2,
                        'rsv3'    => $rsv3,
                        'opcode'  => $opcode,
                        'mask'    => $mask,
                        'length'  => $length,
                        'message' => ''
                    ]);
    }

    public function case_read_frame_of_small_length()
    {
        return $this->_case_read_frame_of_small_length(
            Websocket\Connection::OPCODE_TEXT_FRAME,
            0x1
        );
    }

    protected function _case_read_frame_of_small_length($opcode, $fin)
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $rsv1   = 0x0,
                $rsv2   = 0x0,
                $rsv3   = 0x0,
                $mask   = 0x0,
                $length = 0x7c,

                $this->calling($socket)->read[1] =
                    chr(
                        ($fin  << 7)
                      | ($rsv1 << 6)
                      | ($rsv2 << 5)
                      | ($rsv3 << 4)
                      | $opcode
                    ),
                $this->calling($socket)->read[2] =
                    chr(
                        ($mask << 7)
                      | $length
                    ),
                $this->calling($socket)->read[3] = function ($_length) use ($length) {
                    return str_repeat('a', $length);
                }
            )
            ->when($result = $protocol->readFrame())
            ->then
                ->array($result)
                    ->isEqualTo([
                        'fin'     => $fin,
                        'rsv1'    => $rsv1,
                        'rsv2'    => $rsv2,
                        'rsv3'    => $rsv3,
                        'opcode'  => $opcode,
                        'mask'    => $mask,
                        'length'  => $length,
                        'message' => str_repeat('a', $length)
                    ]);
    }

    public function case_read_frame_of_medium_length()
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $fin    = 0x1,
                $rsv1   = 0x0,
                $rsv2   = 0x0,
                $rsv3   = 0x0,
                $opcode = Websocket\Connection::OPCODE_TEXT_FRAME,
                $mask   = 0x0,
                $length = 0x7d,

                $this->calling($socket)->read[1] =
                    chr(
                        ($fin  << 7)
                      | ($rsv1 << 6)
                      | ($rsv2 << 5)
                      | ($rsv3 << 4)
                      | $opcode
                    ),
                $this->calling($socket)->read[2] =
                    chr(
                        ($mask << 7)
                      | 0x7e
                    ),
                $this->calling($socket)->read[3] = pack('n', $length),
                $this->calling($socket)->read[4] = function ($_length) use ($length) {
                    return str_repeat('a', $length);
                }
            )
            ->when($result = $protocol->readFrame())
            ->then
                ->array($result)
                    ->isEqualTo([
                        'fin'     => $fin,
                        'rsv1'    => $rsv1,
                        'rsv2'    => $rsv2,
                        'rsv3'    => $rsv3,
                        'opcode'  => $opcode,
                        'mask'    => $mask,
                        'length'  => $length,
                        'message' => str_repeat('a', $length)
                    ]);
    }

    public function case_read_frame_of_long_length()
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $fin    = 0x1,
                $rsv1   = 0x0,
                $rsv2   = 0x0,
                $rsv3   = 0x0,
                $opcode = Websocket\Connection::OPCODE_TEXT_FRAME,
                $mask   = 0x0,
                $length = 0xffff,

                $this->calling($socket)->read[1] =
                    chr(
                        ($fin  << 7)
                      | ($rsv1 << 6)
                      | ($rsv2 << 5)
                      | ($rsv3 << 4)
                      | $opcode
                    ),
                $this->calling($socket)->read[2] =
                    chr(
                        ($mask << 7)
                      | 0x7f
                    ),
                $this->calling($socket)->read[3] = pack('NN', 0, $length),
                $this->calling($socket)->read[4] = function ($_length) use ($length) {
                    return str_repeat('a', $length);
                }
            )
            ->when($result = $protocol->readFrame())
            ->then
                ->array($result)
                    ->isEqualTo([
                        'fin'     => $fin,
                        'rsv1'    => $rsv1,
                        'rsv2'    => $rsv2,
                        'rsv3'    => $rsv3,
                        'opcode'  => $opcode,
                        'mask'    => $mask,
                        'length'  => $length,
                        'message' => str_repeat('a', $length)
                    ]);
    }

    public function case_read_continuation_frame()
    {
        return $this->_case_read_frame_of_small_length(
            Websocket\Connection::OPCODE_CONTINUATION_FRAME,
            0x0
        );
    }

    public function case_read_masked_frame_of_length_0()
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $fin    = 0x1,
                $rsv1   = 0x0,
                $rsv2   = 0x0,
                $rsv3   = 0x0,
                $opcode = Websocket\Connection::OPCODE_TEXT_FRAME,
                $mask   = 0x1,
                $length = 0x0,

                $this->calling($socket)->read[1] =
                    chr(
                        ($fin  << 7)
                      | ($rsv1 << 6)
                      | ($rsv2 << 5)
                      | ($rsv3 << 4)
                      | $opcode
                    ),
                $this->calling($socket)->read[2] =
                    chr(
                        ($mask << 7)
                      | $length
                    ),
                $this->calling($socket)->read[3] = 'keyy'
            )
            ->when($result = $protocol->readFrame())
            ->then
                ->array($result)
                    ->isEqualTo([
                        'fin'     => $fin,
                        'rsv1'    => $rsv1,
                        'rsv2'    => $rsv2,
                        'rsv3'    => $rsv3,
                        'opcode'  => $opcode,
                        'mask'    => $mask,
                        'length'  => $length,
                        'message' => ''
                    ]);
    }

    public function case_read_masked_frame_of_small_length()
    {
        $self = $this;

        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $fin    = 0x1,
                $rsv1   = 0x0,
                $rsv2   = 0x0,
                $rsv3   = 0x0,
                $opcode = Websocket\Connection::OPCODE_TEXT_FRAME,
                $mask   = 0x1,
                $length = 0x7c,

                $maskingKey = [0x17, 0xea, 0xe5, 0xe8],

                $this->calling($socket)->read[1] =
                    chr(
                        ($fin  << 7)
                      | ($rsv1 << 6)
                      | ($rsv2 << 5)
                      | ($rsv3 << 4)
                      | $opcode
                    ),
                $this->calling($socket)->read[2] =
                    chr(
                        ($mask << 7)
                      | $length
                    ),
                $this->calling($socket)->read[3] = function ($_length) use ($self, $maskingKey) {
                    $self
                        ->integer($_length)
                            ->isEqualTo(4);

                    return implode('', array_map('chr', $maskingKey));
                },
                $this->calling($socket)->read[4] = $this->getMessage(
                    $length,
                    true,
                    $maskingKey
                )
            )
            ->when($result = $protocol->readFrame())
            ->then
                ->array($result)
                    ->isEqualTo([
                        'fin'     => $fin,
                        'rsv1'    => $rsv1,
                        'rsv2'    => $rsv2,
                        'rsv3'    => $rsv3,
                        'opcode'  => $opcode,
                        'mask'    => $mask,
                        'length'  => $length,
                        'message' => str_repeat('a', $length)
                    ]);
    }

    public function case_read_masked_frame_of_medium_length()
    {
        $self = $this;

        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $fin    = 0x1,
                $rsv1   = 0x0,
                $rsv2   = 0x0,
                $rsv3   = 0x0,
                $opcode = Websocket\Connection::OPCODE_TEXT_FRAME,
                $mask   = 0x1,
                $length = 0x7d,

                $maskingKey = [0x17, 0xea, 0xe5, 0xe8],

                $this->calling($socket)->read[1] =
                    chr(
                        ($fin  << 7)
                      | ($rsv1 << 6)
                      | ($rsv2 << 5)
                      | ($rsv3 << 4)
                      | $opcode
                    ),
                $this->calling($socket)->read[2] =
                    chr(
                        ($mask << 7)
                      | 0x7e
                    ),
                $this->calling($socket)->read[3] = pack('n', $length),
                $this->calling($socket)->read[4] = function ($_length) use ($self, $maskingKey) {
                    $self
                        ->integer($_length)
                            ->isEqualTo(4);

                    return implode('', array_map('chr', $maskingKey));
                },
                $this->calling($socket)->read[5] = $this->getMessage(
                    $length,
                    true,
                    $maskingKey
                )
            )
            ->when($result = $protocol->readFrame())
            ->then
                ->array($result)
                    ->isEqualTo([
                        'fin'     => $fin,
                        'rsv1'    => $rsv1,
                        'rsv2'    => $rsv2,
                        'rsv3'    => $rsv3,
                        'opcode'  => $opcode,
                        'mask'    => $mask,
                        'length'  => $length,
                        'message' => str_repeat('a', $length)
                    ]);
    }

    public function case_write_frame_of_length_0()
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),
                $message  = '',

                $this->calling($socket)->writeAll = function ($data) use (&$output) {
                    $output = $data;

                    return;
                }
            )
            ->when($result = $protocol->writeFrame($message))
            ->then
                ->variable($result)
                    ->isNull()
                ->string($output)
                    ->isEqualTo(
                        chr(
                            (0x1 << 7)
                          | (0x0 << 6)
                          | (0x0 << 5)
                          | (0x0 << 4)
                          | Websocket\Connection::OPCODE_TEXT_FRAME
                        ) .
                        chr(
                            (0x0 << 7)
                          | 0
                        ) .
                        $message
                    );
    }

    public function case_write_frame_of_small_length()
    {
        return $this->_case_write_frame_of_small_length(
            Websocket\Connection::OPCODE_TEXT_FRAME,
            true
        );
    }

    protected function _case_write_frame_of_small_length($opcode, $fin)
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),
                $message  = str_repeat('a', 0x7d),

                $this->calling($socket)->writeAll = function ($data) use (&$output) {
                    $output = $data;

                    return;
                }
            )
            ->when($result = $protocol->writeFrame($message, $opcode, $fin))
            ->then
                ->variable($result)
                    ->isNull()
                ->string($output)
                    ->isEqualTo(
                        chr(
                            ($fin << 7)
                          | (0x0  << 6)
                          | (0x0  << 5)
                          | (0x0  << 4)
                          | $opcode
                        ) .
                        chr(
                            (0x0 << 7)
                          | strlen($message)
                        ) .
                        $message
                    );
    }

    public function case_write_frame_of_medium_length()
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),
                $message  = str_repeat('a', 0x7e),

                $this->calling($socket)->writeAll = function ($data) use (&$output) {
                    $output = $data;

                    return;
                }
            )
            ->when($result = $protocol->writeFrame($message))
            ->then
                ->variable($result)
                    ->isNull()
                ->string($output)
                    ->isEqualTo(
                        chr(
                            (0x1 << 7)
                          | (0x0 << 6)
                          | (0x0 << 5)
                          | (0x0 << 4)
                          | Websocket\Connection::OPCODE_TEXT_FRAME
                        ) .
                        chr(
                            (0x0 << 7)
                          | 0x7e
                        ) .
                        pack('n', strlen($message)) .
                        $message
                    );
    }

    public function case_write_frame_of_long_length()
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),
                $message  = str_repeat('a', 0x10000),

                $this->calling($socket)->writeAll = function ($data) use (&$output) {
                    $output = $data;

                    return;
                }
            )
            ->when($result = $protocol->writeFrame($message))
            ->then
                ->variable($result)
                    ->isNull()
                ->string($output)
                    ->isEqualTo(
                        chr(
                            (0x1 << 7)
                          | (0x0 << 6)
                          | (0x0 << 5)
                          | (0x0 << 4)
                          | Websocket\Connection::OPCODE_TEXT_FRAME
                        ) .
                        chr(
                            (0x0 << 7)
                          | 0x7f
                        ) .
                        pack('NN', 0, strlen($message)) .
                        $message
                    );
    }

    public function case_write_continuation_frame()
    {
        return $this->_case_write_frame_of_small_length(
            Websocket\Connection::OPCODE_TEXT_FRAME,
            false
        );
    }

    public function case_write_masked_frame_of_length_0()
    {
        return $this->_case_write_masked_frame_of_small_length('');
    }

    public function case_write_masked_frame_of_small_length()
    {
        return $this->_case_write_masked_frame_of_small_length(str_repeat('a', 0x7d));
    }

    protected function _case_write_masked_frame_of_small_length($message)
    {
        $this
            ->given(
                $socket        = new Socket\Server('tcp://*:1234'),
                $protocol      = new SUT($socket),
                $message       = str_repeat('a', 0x7d),
                $maskingKey    = [0x17, 0xea, 0xe5, 0xe8],
                $maskedMessage = $this->getMessage(
                    strlen($message),
                    true,
                    $maskingKey
                ),

                $this->calling($socket)->writeAll = function ($data) use (&$output) {
                    $output = $data;

                    return;
                },
                $this->calling($protocol)->getMaskingKey = $maskingKey
            )
            ->when($result = $protocol->writeFrame($message, Websocket\Connection::OPCODE_TEXT_FRAME, true, true))
            ->then
                ->variable($result)
                    ->isNull()
                ->string($output)
                    ->isEqualTo(
                        chr(
                            (0x1 << 7)
                          | (0x0 << 6)
                          | (0x0 << 5)
                          | (0x0 << 4)
                          | Websocket\Connection::OPCODE_TEXT_FRAME
                        ) .
                        chr(
                            (0x1 << 7)
                          | strlen($maskedMessage)
                        ) .
                        chr($maskingKey[0]) .
                        chr($maskingKey[1]) .
                        chr($maskingKey[2]) .
                        chr($maskingKey[3]) .
                        $maskedMessage
                    );
    }

    public function case_write_masked_frame_of_medium_length()
    {
        $this
            ->given(
                $socket        = new Socket\Server('tcp://*:1234'),
                $protocol      = new SUT($socket),
                $message       = str_repeat('a', 0x7e),
                $maskingKey    = [0x17, 0xea, 0xe5, 0xe8],
                $maskedMessage = $this->getMessage(
                    strlen($message),
                    true,
                    $maskingKey
                ),

                $this->calling($socket)->writeAll = function ($data) use (&$output) {
                    $output = $data;

                    return;
                },
                $this->calling($protocol)->getMaskingKey = $maskingKey
            )
            ->when($result = $protocol->writeFrame($message, Websocket\Connection::OPCODE_TEXT_FRAME, true, true))
            ->then
                ->variable($result)
                    ->isNull()
                ->string($output)
                    ->isEqualTo(
                        chr(
                            (0x1 << 7)
                          | (0x0 << 6)
                          | (0x0 << 5)
                          | (0x0 << 4)
                          | Websocket\Connection::OPCODE_TEXT_FRAME
                        ) .
                        chr(
                            (0x1 << 7)
                          | 0x7e
                        ) .
                        pack('n', strlen($maskedMessage)) .
                        chr($maskingKey[0]) .
                        chr($maskingKey[1]) .
                        chr($maskingKey[2]) .
                        chr($maskingKey[3]) .
                        $maskedMessage
                    );
    }

    public function case_write_masked_frame_of_long_length()
    {
        $this
            ->given(
                $socket        = new Socket\Server('tcp://*:1234'),
                $protocol      = new SUT($socket),
                $message       = str_repeat('a', 0x10000),
                $maskingKey    = [0x17, 0xea, 0xe5, 0xe8],
                $maskedMessage = $this->getMessage(
                    strlen($message),
                    true,
                    $maskingKey
                ),

                $this->calling($socket)->writeAll = function ($data) use (&$output) {
                    $output = $data;

                    return;
                },
                $this->calling($protocol)->getMaskingKey = $maskingKey
            )
            ->when($result = $protocol->writeFrame($message, Websocket\Connection::OPCODE_TEXT_FRAME, true, true))
            ->then
                ->variable($result)
                    ->isNull()
                ->string($output)
                    ->isEqualTo(
                        chr(
                            (0x1 << 7)
                          | (0x0 << 6)
                          | (0x0 << 5)
                          | (0x0 << 4)
                          | Websocket\Connection::OPCODE_TEXT_FRAME
                        ) .
                        chr(
                            (0x1 << 7)
                          | 0x7f
                        ) .
                        pack('NN', 0, strlen($maskedMessage)) .
                        chr($maskingKey[0]) .
                        chr($maskingKey[1]) .
                        chr($maskingKey[2]) .
                        chr($maskingKey[3]) .
                        $maskedMessage
                    );
    }

    public function case_get_masking_key()
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket)
            )
            ->when($result = $protocol->getMaskingKey())
            ->then
                ->array($result)
                    ->hasSize(4);
    }

    public function case_send_text_frame()
    {
        return $this->_case_send(
            'foobar',
            Websocket\Connection::OPCODE_TEXT_FRAME,
            true,
            false
        );
    }

    public function case_send_masked_text_frame()
    {
        return $this->_case_send(
            'foobar',
            Websocket\Connection::OPCODE_TEXT_FRAME,
            true,
            true
        );
    }

    public function case_send_continuation_frame()
    {
        return $this->_case_send(
            'foobar',
            Websocket\Connection::OPCODE_CONTINUATION_FRAME,
            false,
            false
        );
    }

    public function case_send_masked_continuation_frame()
    {
        return $this->_case_send(
            'foobar',
            Websocket\Connection::OPCODE_CONTINUATION_FRAME,
            false,
            true
        );
    }

    public function case_send_empty_message()
    {
        return $this->_case_send(
            '',
            Websocket\Connection::OPCODE_TEXT_FRAME,
            true,
            false
        );
    }

    public function case_send_invalid_message_text_frame()
    {
        return $this->_case_send_invalid_message(
            Websocket\Connection::OPCODE_TEXT_FRAME
        );
    }

    public function case_send_invalid_message_continuation_frame()
    {
        return $this->_case_send_invalid_message(
            Websocket\Connection::OPCODE_CONTINUATION_FRAME
        );
    }

    protected function _case_send_invalid_message($opcode)
    {
        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),
                $message  = iconv('UTF-8', 'UTF-16', '😄')
            )
            ->exception(function () use ($protocol, $message) {
                $protocol->send($message);
            })
                ->isInstanceOf(Websocket\Exception\InvalidMessage::class);
    }

    public function case_send_invalid_message_binary_frame()
    {
        return $this->_case_send(
            iconv('UTF-8', 'UTF-16', '😄'),
            Websocket\Connection::OPCODE_BINARY_FRAME,
            true,
            false
        );
    }

    protected function _case_send($message, $opcode, $end, $mask)
    {
        $self = $this;

        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $this->calling($protocol)->writeFrame = function ($_message, $_opcode, $_end, $_mask) use (&$called, $self, $message, $opcode, $end, $mask) {
                    $called = true;

                    $self
                        ->string($_message)
                            ->isEqualTo($message)
                        ->integer($_opcode)
                            ->isEqualTo($opcode)
                        ->boolean($_end)
                            ->isEqualTo($end)
                        ->boolean($_mask)
                            ->isEqualTo($mask);

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

    /**
     * @dataProvider getCloseTypes
     */
    public function case_close_with_no_reason($closeType)
    {
        return $this->_case_close($closeType, null, false);
    }

    /**
     * @dataProvider getCloseTypes
     */
    public function case_close_with_a_reason($closeType)
    {
        return $this->_case_close($closeType, 'foobar', false);
    }

    /**
     * @dataProvider getCloseTypes
     */
    public function case_masked_close_with_no_reason($closeType)
    {
        return $this->_case_close($closeType, null, false);
    }

    /**
     * @dataProvider getCloseTypes
     */
    public function case_masked_close_with_a_reason($closeType)
    {
        return $this->_case_close($closeType, 'foobar', true);
    }

    protected function _case_close($code, $reason, $mask)
    {
        $self = $this;

        $this
            ->given(
                $socket   = new Socket\Server('tcp://*:1234'),
                $protocol = new SUT($socket),

                $this->calling($protocol)->writeFrame = function ($_message, $_opcode, $_end, $_mask) use (&$called, $self, $code, $reason, $mask) {
                    $called = true;

                    $self
                        ->string($_message)
                            ->isEqualTo(pack('n', $code) . $reason)
                        ->integer($_opcode)
                            ->isEqualTo(Websocket\Connection::OPCODE_CONNECTION_CLOSE)
                        ->boolean($_end)
                            ->isEqualTo(true)
                        ->boolean($_mask)
                            ->isEqualTo($mask);

                    return;
                }
            )
            ->when($result = $protocol->close($code, $reason, $mask))
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($called)
                    ->isTrue();
    }

    private function getMessage($length, $mask = false, array $maskingKey = [])
    {
        $message = str_repeat('a', $length);

        if (false === $mask) {
            return $message;
        }

        for ($i = 0; $i < $length; ++$i) {
            $message[$i] = chr(ord($message[$i]) ^ $maskingKey[$i % 4]);
        }

        return $message;
    }

    protected function getCloseTypes()
    {
        return [
            [Websocket\Connection::CLOSE_NORMAL],
            [Websocket\Connection::CLOSE_GOING_AWAY],
            [Websocket\Connection::CLOSE_PROTOCOL_ERROR],
            [Websocket\Connection::CLOSE_DATA_ERROR],
            [Websocket\Connection::CLOSE_STATUS_ERROR],
            [Websocket\Connection::CLOSE_ABNORMAL],
            [Websocket\Connection::CLOSE_MESSAGE_ERROR],
            [Websocket\Connection::CLOSE_POLICY_ERROR],
            [Websocket\Connection::CLOSE_MESSAGE_TOO_BIG],
            [Websocket\Connection::CLOSE_EXTENSION_MISSING],
            [Websocket\Connection::CLOSE_SERVER_ERROR],
            [Websocket\Connection::CLOSE_TLS]
        ];
    }
}
