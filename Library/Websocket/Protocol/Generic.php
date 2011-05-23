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

namespace Hoa\Websocket\Protocol {

/**
 * Class \Hoa\Websocket\Protocol\Generic.
 *
 * An abstract protocol implementation.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2011 Ivan Enderlin.
 * @license    New BSD License
 */

abstract class Generic {

    /**
     * Server.
     *
     * @var \Hoa\Socket\Connection\Server object
     */
    protected $_server = null;



    /**
     * Construct the protocol implementation.
     *
     * @access  public
     * @param   \Hoa\Socket\Connection\Server  $server    Server.
     * @return  void
     */
    public function __construct ( \Hoa\Socket\Connection\Server $server ) {

        $this->_server = $server;

        return;
    }

    /**
     * Do the handshake.
     *
     * @access  public
     * @param   array  $headers    Headers.
     * @return  void
     * @throw   \Hoa\Websocket\Exception\BadProtocol
     */
    abstract public function doHandshake ( Array $headers );

    /**
     * Read a frame.
     *
     * @access  public
     * @return  array
     */
    abstract public function readFrame ( );

    /**
     * write a frame.
     *
     * @access  public
     * @param   string  $message    message.
     * @param   bool    $end        whether it is the last frame of the message.
     * @return  int
     */
    abstract public function writeFrame ( $message, $end = true );

    /**
     * Send a message to a node (if not specified, current node).
     *
     * @access  public
     * @param   string               $message    Message.
     * @param   \Hoa\Websocket\Node  $node       Node.
     * @return  void
     */
    abstract public function send ( $message, \Hoa\Websocket\Node $node = null );
}

}
