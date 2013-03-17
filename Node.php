<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2013, Ivan Enderlin. All rights reserved.
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

namespace {

from('Hoa')

/**
 * \Hoa\Socket\Node
 */
-> import('Socket.Node');

}

namespace Hoa\Websocket {

/**
 * Class \Hoa\Websocket\Node.
 *
 * Describe a websocket node.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2013 Ivan Enderlin.
 * @license    New BSD License
 */

class Node extends \Hoa\Socket\Node {

    /**
     * Protocol implementation.
     *
     * @var \Hoa\Websocket\Protocol\Generic object
     */
    protected $_protocol          = null;

    /**
     * Whether the handshake succeed.
     *
     * @var \Hoa\Websocket\Node bool
     */
    protected $_handshake         = false;

    /**
     * Fragments of message.
     *
     * @var \Hoa\Websocket\Node string
     */
    protected $_messageFragments  = null;

    /**
     * Number of fragments.
     *
     * @var \Hoa\Websocket\Node int
     */
    protected $_numberOfFragments = 0;

    /**
     * Whether the message is binary or not.
     *
     * @var \Hoa\Websocket\Node bool
     */
    protected $_isBinary          = false;



    /**
     * Set protocol implementation.
     *
     * @access  public
     * @param   \Hoa\Websocket\Protocol\Generic  $protocol    Protocol.
     * @return  \Hoa\Websocket\Protocol\Generic
     */
    public function setProtocolImplementation ( Protocol\Generic $protocol ) {

        $old             = $this->_protocol;
        $this->_protocol = $protocol;

        return $old;
    }

    /**
     * Get protocol implementation.
     *
     * @access  public
     * @return  \Hoa\Websocket\Protocol\Generic
     */
    public function getProtocolImplementation ( ) {

        return $this->_protocol;
    }

    /**
     * Set handshake success.
     *
     * @access  public
     * @param   bool    $handshake    Handshake.
     * @return  bool
     */
    public function setHandshake ( $handshake ) {

        $old              = $this->_handshake;
        $this->_handshake = $handshake;

        return $old;
    }

    /**
     * Whether the handshake succeed.
     *
     * @access  public
     * @return  bool
     */
    public function getHandshake ( ) {

        return $this->_handshake;
    }

    /**
     * Append a fragment to a message (if we have fragmentation).
     *
     * @access  public
     * @param   string  $fragment    Fragment.
     * @return  string
     */
    public function appendMessageFragment ( $fragment ) {

        ++$this->_numberOfFragments;

        return $this->_messageFragments .= $fragment;
    }

    /**
     * Get the fragmented message.
     *
     * @access  public
     * @return  string
     */
    public function getFragmentedMessage ( ) {

        return $this->_messageFragments;
    }

    /**
     * Get number of fragments.
     *
     * @access  public
     * @return  int
     */
    public function getNumberOfFragments ( ) {

        return $this->_numberOfFragments;
    }

    /**
     * Whether the message is binary or not.
     *
     * @access  public
     * @param   bool  $binary    Binary.
     * @return  bool
     */
    public function setBinary ( $binary ) {

        $old             = $this->_binary;
        $this->_isBinary = $binary;

        return $old;
    }

    /**
     * Check if the message is binary or not.
     *
     * @access  public
     * @return  bool
     */
    public function isBinary ( ) {

        return $this->_isBinary;
    }

    /**
     * Clear the fragmentation.
     *
     * @access  public
     * @return  string
     */
    public function clearFragmentation ( ) {

        unset($this->_messageFragments);
        $this->_numberOfFragments = 0;
        $this->_isBinary          = false;

        return;
    }
}

}
