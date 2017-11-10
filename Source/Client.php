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

use Hoa\Consistency;
use Hoa\Event;
use Hoa\Http;
use Hoa\Socket as HoaSocket;

/**
 * Class \Hoa\Websocket\Client.
 *
 * A cross-protocol Websocket client.
 */
class Client extends Connection
{
    /**
     * Endpoint.
     */
    protected $_endPoint = null;

    /**
     * Host name.
     */
    protected $_host     = null;

    /**
     * Response (mainly parser).
     */
    protected $_response = null;



    /**
     * Create a Websocket client.
     */
    public function __construct(
        HoaSocket\Client $client,
        string $endPoint        = null,
        Http\Response $response = null
    ) {
        parent::__construct($client);

        if (null === $endPoint) {
            $endPoint = '/';
            $socket   = $client->getSocket();

            if ($socket instanceof Socket) {
                $endPoint = $socket->getEndPoint();
            }
        }

        $this->setEndPoint($endPoint);

        if (null === $response) {
            $response = new Http\Response(false);
        }

        $this->setResponse($response);

        return;
    }

    /**
     * Connect, i.e. open the connection and do handshake.
     */
    public function connect(): self
    {
        $this->doHandshake();

        return $this;
    }

    /**
     * Override the parent run() method to open the connection.
     */
    public function run(): void
    {
        $this->connect();
        parent::run();
    }

    /**
     * Receive data. Fire listeners.
     */
    public function receive(): void
    {
        $connection = $this->getConnection();
        $node       = $connection->getCurrentNode();

        do {
            $this->_run($node);
        } while (
            false === $connection->isDisconnected() &&
            true !== $node->isMessageComplete()
        );
    }

    /**
     * Try the handshake by trying different protocol implementation.
     */
    protected function doHandshake(): void
    {
        $connection = $this->getConnection();
        $connection->connect();

        if (true === $connection->getSocket()->isSecured()) {
            $connection->enableEncryption(true, $connection::ENCRYPTION_TLS);
        }

        $connection->setStreamBlocking(true);

        $key      = $this->getNewChallenge();
        $expected = base64_encode(sha1($key . Protocol\Rfc6455::GUID, true));

        if (null === $host = $this->getHost()) {
            throw new Exception(
                'Host name is null. Please, use the %s::setHost() method.',
                0,
                __CLASS__
            );
        }

        $connection->writeAll(
            $request =
                'GET ' . $this->getEndPoint() . ' HTTP/1.1' . CRLF .
                'Host: ' . $host . CRLF .
                'User-Agent: Hoa' . CRLF .
                'Upgrade: WebSocket' . CRLF .
                'Connection: Upgrade' . CRLF .
                'Pragma: no-cache' . CRLF .
                'Cache-Control: no-cache' . CRLF .
                'Sec-WebSocket-Key: ' . $key . CRLF .
                'Sec-WebSocket-Version: 13' . CRLF . CRLF
        );

        $buffer   = $connection->read(2048);
        $response = $this->getResponse();
        $response->parse($buffer);

        if ($response::STATUS_SWITCHING_PROTOCOLS !== $response['status'] ||
            'websocket' !== strtolower($response['upgrade']) ||
            'upgrade' !== strtolower($response['connection']) ||
            $expected !== $response['sec-websocket-accept']) {
            throw new Exception\BadProtocol(
                'Handshake has failed, the server did not return a valid ' .
                'response.' . "\n\n" .
                'Client:' . "\n" . '    %s' . "\n" .
                'Server:' . "\n" . '    %s',
                0,
                [
                    str_replace("\n", "\n" . '    ', $request),
                    str_replace("\n", "\n" . '    ', $buffer)
                ]
            );
        }

        $currentNode = $connection->getCurrentNode();
        $currentNode->setHandshake(SUCCEED);
        $currentNode->setProtocolImplementation(new Protocol\Rfc6455($connection));

        $this->getListener()->fire(
            'open',
            new Event\Bucket()
        );
    }

    /**
     * Generate a challenge.
     */
    public function getNewChallenge(): string
    {
        static $_tail = ['A', 'Q', 'g', 'w'];

        return
            substr(base64_encode(Consistency::uuid()), 0, 21) .
            $_tail[mt_rand(0, 3)] . '==';
    }

    /**
     * Close a specific node/connection.
     */
    public function close(int $code = self::CLOSE_NORMAL, string $reason = null): void
    {
        $connection = $this->getConnection();
        $protocol   = $connection->getCurrentNode()->getProtocolImplementation();

        if (null !== $protocol) {
            $protocol->close($code, $reason, true);
        }

        $connection->mute();
        $connection->setStreamTimeout(0, 2 * 15000); // 2 * MLS (on FreeBSD)
        $connection->read(1);

        $connection->disconnect();
    }

    /**
     * Set end-point.
     */
    protected function setEndPoint(string $endPoint): ?string
    {
        $old             = $this->_endPoint;
        $this->_endPoint = $endPoint;

        return $old;
    }

    /**
     * Get end-point.
     */
    public function getEndPoint(): string
    {
        return $this->_endPoint;
    }

    /**
     * Set response (mainly parser).
     */
    public function setResponse(Http\Response $response): ?Http\Response
    {
        $old             = $this->_response;
        $this->_response = $response;

        return $old;
    }

    /**
     * Get response.
     */
    public function getResponse(): Http\Response
    {
        return $this->_response;
    }

    /**
     * Set host.
     */
    public function setHost(string $host): ?string
    {
        $old         = $this->_host;
        $this->_host = $host;

        return $old;
    }

    /**
     * Get host.
     *
     * @return  string
     */
    public function getHost(): ?string
    {
        return
            null !== $this->_host
                ? $this->_host
                : ($_SERVER['HTTP_HOST'] ?? null);
    }
}
