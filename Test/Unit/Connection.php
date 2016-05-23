<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2016, Hoa community. All rights reserved.
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

namespace Hoa\Webocket\Test\Unit;

use Hoa\Test;
use Hoa\Websocket\Connection as SUT;

/**
 * Class \Hoa\Websocket\Test\Unit\Connection.
 *
 * Test suite for the WebSocket connection class.
 *
 * @copyright  Copyright © 2007-2016 Hoa community
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
}
