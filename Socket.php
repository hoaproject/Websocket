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

use Hoa\Socket as HoaSocket;

/**
 * Class \Hoa\Websocket\Socket.
 *
 * Websocket specific socket extension.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Socket extends HoaSocket
{
    /**
     * Endpoint.
     *
     * @var string
     */
    protected $_endPoint = '/';

    /**
     * Constructor
     *
     * @param string  $uri      Socket URI
     * @param boolean $secured  Secure mode
     * @param string  $endPoint Websocket endpoint
     */
    public function __construct($uri, $secured = false, $endPoint = '/')
    {
        parent::__construct($uri);

        $this->_secured  = $secured;
        $this->_endPoint = $endPoint;

        return;
    }

    /**
     * Retrieve the websocket endpoint
     *
     * @return string
     */
    public function getEndPoint()
    {
        return $this->_endPoint;
    }

    /**
     * Factory to create a valid instance from URI
     * @param string $socketUri
     * @return void
     */
    public static function createFromUri($socketUri)
    {
        $parsed = parse_url($socketUri);
        if( false === $parsed ) {
            throw new Exception(
                'URI %s is not recognized.',
                0,
                $socketUri
            );
        }

        $secure = isset($parsed['scheme'])?
            'wss' === $parsed['scheme']:
            false;

        if (isset($parsed['port'])) {
            $port = $parsed['port'];
        } else {
            $port = true === $secure ? 443 : 80;
        }

        return new static(
            'tcp://' . $parsed['host'] . ':' . $port,
            $secure,
            isset($parsed['path'])?$parsed['path']:'/'
        );
    }
}

/**
 * Register socket wrappers
 */
HoaSocket\Transport::register('ws',  ['Hoa\Websocket\Socket', 'transportFactory']);
HoaSocket\Transport::register('wss', ['Hoa\Websocket\Socket', 'transportFactory']);
