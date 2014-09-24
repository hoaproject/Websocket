<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2014, Ivan Enderlin. All rights reserved.
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

use Hoa\Http;
use Hoa\Socket;

/**
 * Class \Hoa\Websocket\Server.
 *
 * A cross-protocol Websocket server.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2014 Ivan Enderlin.
 * @license    New BSD License
 */

class Server extends Connection {

    /**
     * Request (mainly parser).
     *
     * @var \Hoa\Http\Request object
     */
    protected $_request = null;



    /**
     * Create a Websocket server.
     *
     * @access  public
     * @param   \Hoa\Socket\Server  $server    Server.
     * @param   \Hoa\Http\Request   $request   Request parser.
     * @return  void
     * @throw   \Hoa\Socket\Exception
     */
    public function __construct ( Socket\Server $server,
                                  Http\Request  $request = null ) {

        parent::__construct($server);

        if(null === $request)
            $request = new Http\Request();

        $this->setRequest($request);

        return;
    }

    /**
     * Try the handshake by trying different protocol implementation.
     *
     * @access  protected
     * @return  void
     * @throw   \Hoa\Websocket\Exception\BadProtocol
     */
    protected function doHandshake ( ) {

        $connection = $this->getConnection();
        $buffer     = $connection->read(2048);
        $request    = $this->getRequest();
        $request->parse($buffer);

        // Rfc6455.
        try {

            $rfc6455 = new Protocol\Rfc6455($connection);
            $rfc6455->doHandshake($request);
            $connection->getCurrentNode()->setProtocolImplementation($rfc6455);
        }
        catch ( Exception\BadProtocol $e ) {

            unset($rfc6455);

            // Hybi00.
            try {

                $hybi00 = new Protocol\Hybi00($connection);
                $hybi00->doHandshake($request);
                $connection->getCurrentNode()->setProtocolImplementation($hybi00);
            }
            catch ( Exception\BadProtocol $e ) {

                unset($hybi00);
                $connection->disconnect();

                throw new Exception\BadProtocol(
                    'All protocol failed.', 1);
            }
        }

        return;
    }

    /**
     * Set request (mainly parser).
     *
     * @access  public
     * @param   \Hoa\Http\Request  $request    Request.
     * @return  \Hoa\Http\Request
     */
    public function setRequest ( Http\Request $request ) {

        $old            = $this->_request;
        $this->_request = $request;

        return $old;
    }

    /**
     * Get request.
     *
     * @access  public
     * @return  \Hoa\Http\Request
     */
    public function getRequest ( ) {

        return $this->_request;
    }
}
