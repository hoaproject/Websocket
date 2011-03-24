<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2011, Ivan Enderlin. All rights reserved.
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
 * \Hoa\Socket\Connection\Node
 */
-> import('Socket.Connection.Node');

}

namespace Hoa\Websocket {

/**
 * Class \Hoa\Websocket\Node.
 *
 * Describes a websocket node.
 *
 * @author     Ivan ENDERLIN <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2011 Ivan ENDERLIN.
 * @license    New BSD License
 */

class Node extends \Hoa\Socket\Connection\Node {

    /**
     * Whether it is the first message.
     *
     * @var \Hoa\Websocket\Node bool
     */
    protected $_first     = true;

    /**
     * Whether the handshake succeed.
     *
     * @var \Hoa\Websocket\Node bool
     */
    protected $_handshake = false;



    /**
     * Set whether it is the first message.
     *
     * @access  public
     * @param   bool    $first    First.
     * @return  bool
     */
    public function setFirst ( $first ) {

        $old          = $this->_first;
        $this->_first = $first;

        return $old;
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
     * Whether it is the first message.
     *
     * @access  public
     * @return  bool
     */
    public function isFirstMessage ( ) {

        return $this->_first;
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
}

}
