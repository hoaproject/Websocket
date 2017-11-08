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
 * Class \Hoa\Websocket\Socket.
 *
 * WebSocket specific socket and transports.
 */
class Socket extends HoaSocket
{
    /**
     * Endpoint.
     */
    protected $_endPoint = null;



    /**
     * Constructor
     */
    public function __construct(string $uri, bool $secured = false, string $endPoint = '/')
    {
        parent::__construct($uri);

        $this->_secured  = $secured;
        $this->_endPoint = $endPoint;

        return;
    }

    /**
     * Retrieve the websocket endpoint
     */
    public function getEndPoint(): string
    {
        return $this->_endPoint;
    }

    /**
     * Factory to create a valid `Hoa\Socket\Socket` object.
     */
    public static function transportFactory(string $socketUri): self
    {
        $parsed = parse_url($socketUri);

        if (false === $parsed || !isset($parsed['host'])) {
            throw new Exception(
                'URI %s seems invalid, cannot parse it.',
                0,
                $socketUri
            );
        }

        $secure =
            isset($parsed['scheme'])
                ? 'wss' === $parsed['scheme']
                : false;

        $port =
            $parsed['port']
                ?? (true === $secure
                    ? 443
                    : 80);

        return new static(
            'tcp://' . $parsed['host'] . ':' . $port,
            $secure,
            ($parsed['path'] ?? '/') .
            (isset($parsed['query']) ? '?' . $parsed['query'] : '')
        );
    }
}

/**
 * Register `ws://` and `wss://` transports.
 */
HoaSocket\Transport::register('ws', [Socket::class, 'transportFactory']);
HoaSocket\Transport::register('wss', [Socket::class, 'transportFactory']);
