<?php

declare(strict_types=1);

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

namespace Hoa\Websocket;

use Hoa\Socket as HoaSocket;

/**
 * Class \Hoa\Websocket\Node.
 *
 * Describe a WebSocket node.
 */
class Node extends HoaSocket\Node
{
    /**
     * Protocol implementation.
     */
    protected $_protocol          = null;

    /**
     * Whether the handshake succeed.
     */
    protected $_handshake         = false;

    /**
     * Fragments of message.
     */
    protected $_messageFragments  = null;

    /**
     * Number of fragments.
     */
    protected $_numberOfFragments = 0;

    /**
     * Whether the message is complete or not.
     */
    protected $_complete          = true;

    /**
     * Whether the message is binary or not.
     */
    protected $_isBinary          = false;



    /**
     * Set protocol implementation.
     */
    public function setProtocolImplementation(Protocol\Generic $protocol): ?Protocol\Generic
    {
        $old             = $this->_protocol;
        $this->_protocol = $protocol;

        return $old;
    }

    /**
     * Get protocol implementation.
     */
    public function getProtocolImplementation(): ?Protocol\Generic
    {
        return $this->_protocol;
    }

    /**
     * Set handshake success.
     */
    public function setHandshake(bool $handshake): bool
    {
        $old              = $this->_handshake;
        $this->_handshake = $handshake;

        return $old;
    }

    /**
     * Whether the handshake succeed.
     */
    public function getHandshake(): bool
    {
        return $this->_handshake;
    }

    /**
     * Append a fragment to a message (if we have fragmentation).
     */
    public function appendMessageFragment(string $fragment): string
    {
        ++$this->_numberOfFragments;

        return $this->_messageFragments .= $fragment;
    }

    /**
     * Get the fragmented message.
     */
    public function getFragmentedMessage(): ?string
    {
        return $this->_messageFragments;
    }

    /**
     * Get number of fragments.
     */
    public function getNumberOfFragments(): int
    {
        return $this->_numberOfFragments;
    }

    /**
     * Set whether the message is complete or not.
     */
    public function setComplete(bool $complete): bool
    {
        $old             = $this->_complete;
        $this->_complete = $complete;

        return $old;
    }

    /**
     * Check if the message is complete or not.
     */
    public function isMessageComplete(): bool
    {
        return $this->_complete;
    }

    /**
     * Whether the message is binary or not.
     */
    public function setBinary(bool $binary): bool
    {
        $old             = $this->_isBinary;
        $this->_isBinary = $binary;

        return $old;
    }

    /**
     * Check if the message is binary or not.
     */
    public function isBinary(): bool
    {
        return $this->_isBinary;
    }

    /**
     * Clear the fragmentation.
     */
    public function clearFragmentation(): void
    {
        $this->_messageFragments  = null;
        $this->_numberOfFragments = 0;
        $this->_isBinary          = false;
        $this->_complete          = true;
    }
}
