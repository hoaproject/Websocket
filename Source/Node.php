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

namespace Hoa\Websocket;

use Hoa\Socket as HoaSocket;

/**
 * Class \Hoa\Websocket\Node.
 *
 * Describe a WebSocket node.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
class Node extends HoaSocket\Node
{
    /**
     * Protocol implementation.
     *
     * @var \Hoa\Websocket\Protocol\Generic
     */
    protected $_protocol          = null;

    /**
     * Whether the handshake succeed.
     *
     * @var bool
     */
    protected $_handshake         = false;

    /**
     * Fragments of message.
     *
     * @var string
     */
    protected $_messageFragments  = null;

    /**
     * Number of fragments.
     *
     * @var int
     */
    protected $_numberOfFragments = 0;

    /**
     * Whether the message is complete or not.
     *
     * @var bool
     */
    protected $_complete          = true;

    /**
     * Whether the message is binary or not.
     *
     * @var bool
     */
    protected $_isBinary          = false;



    /**
     * Set protocol implementation.
     *
     * @param   \Hoa\Websocket\Protocol\Generic  $protocol    Protocol.
     * @return  \Hoa\Websocket\Protocol\Generic
     */
    public function setProtocolImplementation(Protocol\Generic $protocol)
    {
        $old             = $this->_protocol;
        $this->_protocol = $protocol;

        return $old;
    }

    /**
     * Get protocol implementation.
     *
     * @return  \Hoa\Websocket\Protocol\Generic
     */
    public function getProtocolImplementation()
    {
        return $this->_protocol;
    }

    /**
     * Set handshake success.
     *
     * @param   bool    $handshake    Handshake.
     * @return  bool
     */
    public function setHandshake($handshake)
    {
        $old              = $this->_handshake;
        $this->_handshake = $handshake;

        return $old;
    }

    /**
     * Whether the handshake succeed.
     *
     * @return  bool
     */
    public function getHandshake()
    {
        return $this->_handshake;
    }

    /**
     * Append a fragment to a message (if we have fragmentation).
     *
     * @param   string  $fragment    Fragment.
     * @return  string
     */
    public function appendMessageFragment($fragment)
    {
        ++$this->_numberOfFragments;

        return $this->_messageFragments .= $fragment;
    }

    /**
     * Get the fragmented message.
     *
     * @return  string
     */
    public function getFragmentedMessage()
    {
        return $this->_messageFragments;
    }

    /**
     * Get number of fragments.
     *
     * @return  int
     */
    public function getNumberOfFragments()
    {
        return $this->_numberOfFragments;
    }

    /**
     * Set whether the message is complete or not.
     *
     * @param   bool  $complete    Is it complete?
     * @return  bool
     */
    public function setComplete($complete)
    {
        $old             = $this->_complete;
        $this->_complete = $complete;

        return $old;
    }

    /**
     * Check if the message is complete or not.
     *
     * @return  bool
     */
    public function isMessageComplete()
    {
        return $this->_complete;
    }

    /**
     * Whether the message is binary or not.
     *
     * @param   bool  $binary    Binary.
     * @return  bool
     */
    public function setBinary($binary)
    {
        $old             = $this->_isBinary;
        $this->_isBinary = $binary;

        return $old;
    }

    /**
     * Check if the message is binary or not.
     *
     * @return  bool
     */
    public function isBinary()
    {
        return $this->_isBinary;
    }

    /**
     * Clear the fragmentation.
     *
     * @return  void
     */
    public function clearFragmentation()
    {
        $this->_messageFragments  = null;
        $this->_numberOfFragments = 0;
        $this->_isBinary          = false;
        $this->_complete          = true;

        return;
    }
}
