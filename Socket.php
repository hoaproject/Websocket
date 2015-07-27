<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2015, Hoa community. All rights reserved.
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

use Hoa\Core;
use Hoa\Socket as BaseSocket;

/**
 * Class \Hoa\Websocket\Socket.
 *
 * Socket analyzer.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Socket extends BaseSocket
{
    /**
     * Address.
     *
     * @var string
     */
    protected $_secured     = false;

    /**
     * Constructor.
     *
     * @param   string  $uri    URI.
     * @return  void
     */
    public function __construct($uri)
    {
        if( false === $parts = parse_url($uri) ) {
            throw new Exception(
                'URI "%s" can\'t be parsed.',
                3,
                $uri
            );
        }

        switch( $parts['scheme'] ) {
            case 'ws':
                $uri = 'tcp://'.$parts['host'].(isset($parts['port'])?':'.$parts['port']:':80');
                break;
            case 'wss':
                $uri = 'tcp://'.$parts['host'].(isset($parts['port'])?':'.$parts['port']:':443');
                $this->_secured = true;
                break;
        }

        parent::__construct($uri);
    }

    /**
     * Set secured mode on the current socket.
     *
     * @param   boolean  $secured    Node name.
     * @return  boolean
     */
    public function setSecured($secured)
    {
        $old            = $this->_secured;
        $this->_secured = $secured;

        return $old;
    }


    /**
     * Check if the current socket is secured or not
     *
     * @return boolean
     */
    public function isSecured()
    {
        return $this->_secured;
    }
}
