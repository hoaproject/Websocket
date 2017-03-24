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

use Hoa\Socket as HoaSocket;
use Hoa\Test;
use Mock\Hoa\Websocket\Node as SUT;

/**
 * Class \Hoa\Websocket\Test\Unit\Node.
 *
 * Test suite for the node class.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
class Node extends Test\Unit\Suite
{
    public function case_is_a_node()
    {
        $this
            ->given($this->mockGenerator->orphanize('__construct'))
            ->when($result = new SUT())
            ->then
                ->object($result)
                    ->isInstanceOf(HoaSocket\Node::class);
    }

    public function case_set_protocol_implementation()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $protocolA = new \Mock\Hoa\Websocket\Protocol\Generic(),
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT()
            )
            ->when($result = $node->setProtocolImplementation($protocolA))
            ->then
                ->variable($result)
                    ->isNull()

            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $protocolB = new \Mock\Hoa\Websocket\Protocol\Generic()
            )
            ->when($result = $node->setProtocolImplementation($protocolB))
            ->then
                ->object($result)
                    ->isIdenticalTo($protocolA);
    }

    public function case_get_protocol_implementation()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $protocol = new \Mock\Hoa\Websocket\Protocol\Generic(),
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT(),
                $node->setProtocolImplementation($protocol)
            )
            ->when($result = $node->getProtocolImplementation())
            ->then
                ->object($result)
                    ->isIdenticalTo($protocol);
    }

    public function case_set_handshake()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT()
            )
            ->when($result = $node->setHandshake(true))
            ->then
                ->boolean($result)
                    ->isFalse()

            ->when($result = $node->setHandshake(false))
            ->then
                ->boolean($result)
                    ->isTrue();
    }

    public function case_get_handshake()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT(),
                $node->setHandshake(true)
            )
            ->when($result = $node->getHandshake())
            ->then
                ->boolean($result)
                    ->isTrue();
    }

    public function case_append_message_fragment()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT()
            )
            ->when($result = $node->appendMessageFragment('foo'))
            ->then
                ->string($result)
                    ->isEqualTo('foo');
    }

    public function case_append_many_message_fragments()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT(),
                $fragmentA = 'foo',
                $fragmentB = 'bar',
                $fragmentC = 'baz'
            )
            ->when($result = $node->appendMessageFragment($fragmentA))
            ->then
                ->string($result)
                    ->isEqualTo($fragmentA)

            ->when($result = $node->appendMessageFragment($fragmentB))
            ->then
                ->string($result)
                    ->isEqualTo($fragmentA . $fragmentB)

            ->when($result = $node->appendMessageFragment($fragmentC))
            ->then
                ->string($result)
                    ->isEqualTo($fragmentA . $fragmentB . $fragmentC);
    }

    public function case_get_fragmented_message()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT(),
                $fragmentA = 'foo',
                $fragmentB = 'bar',
                $fragmentC = 'baz',
                $node->appendMessageFragment($fragmentA),
                $node->appendMessageFragment($fragmentB),
                $node->appendMessageFragment($fragmentC)
            )
            ->when($result = $node->getFragmentedMessage())
            ->then
                ->string($result)
                    ->isEqualTo($fragmentA . $fragmentB . $fragmentC);
    }

    public function case_get_number_of_fragments_for_an_message()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT()
            )
            ->when($result = $node->getNumberOfFragments())
            ->then
                ->integer($result)
                    ->isEqualTo(0);
    }

    public function case_get_number_of_fragments()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT(),
                $fragmentA = 'foo',
                $fragmentB = 'bar',
                $fragmentC = 'baz',
                $node->appendMessageFragment($fragmentA),
                $node->appendMessageFragment($fragmentB),
                $node->appendMessageFragment($fragmentC)
            )
            ->when($result = $node->getNumberOfFragments())
            ->then
                ->integer($result)
                    ->isEqualTo(3);
    }

    public function case_set_complete()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT()
            )
            ->when($result = $node->setComplete(false))
            ->then
                ->boolean($result)
                    ->isTrue()

            ->when($result = $node->setComplete(true))
            ->then
                ->boolean($result)
                    ->isFalse();
    }

    public function case_is_message_complete()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT(),
                $node->setComplete(true)
            )
            ->when($result = $node->isMessageComplete())
            ->then
                ->boolean($result)
                    ->isTrue();
    }

    public function case_is_message_not_complete()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT(),
                $node->setComplete(false)
            )
            ->when($result = $node->isMessageComplete())
            ->then
                ->boolean($result)
                    ->isFalse();
    }

    public function case_message_is_complete_by_default()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT()
            )
            ->when($result = $node->isMessageComplete())
            ->then
                ->boolean($result)
                    ->isTrue();
    }

    public function case_set_binary()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT()
            )
            ->when($result = $node->setBinary(true))
            ->then
                ->boolean($result)
                    ->isFalse()

            ->when($result = $node->setBinary(false))
            ->then
                ->boolean($result)
                    ->isTrue();
    }

    public function case_is_binary()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT(),
                $node->setBinary(true)
            )
            ->when($result = $node->isBinary())
            ->then
                ->boolean($result)
                    ->isTrue();
    }

    public function case_is_not_binary()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT(),
                $node->setBinary(false)
            )
            ->when($result = $node->isBinary())
            ->then
                ->boolean($result)
                    ->isFalse();
    }

    public function case_not_binary_by_default()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT()
            )
            ->when($result = $node->isBinary())
            ->then
                ->boolean($result)
                    ->isFalse();
    }

    public function case_clear_fragmentation()
    {
        $this
            ->given(
                $this->mockGenerator->orphanize('__construct'),
                $protocol = new \Mock\Hoa\Websocket\Protocol\Generic(),
                $this->mockGenerator->orphanize('__construct'),
                $node = new SUT(),
                $node->setProtocolImplementation($protocol),
                $node->appendMessageFragment('foo'),
                $node->appendMessageFragment('bar'),
                $node->setBinary(true),
                $node->setComplete(false)
            )
            ->when($result = $node->clearFragmentation())
            ->then
                ->variable($result)
                    ->isNull()
                ->object($node->getProtocolImplementation())
                    ->isIdenticalTo($protocol)
                ->variable($node->getFragmentedMessage())
                    ->isNull()
                ->boolean($node->isBinary())
                    ->isFalse()
                ->boolean($node->isMessageComplete())
                    ->isTrue();
    }
}
