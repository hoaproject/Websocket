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

namespace Hoa\Websocket\Test\Unit;

use Hoa\Socket as HoaSocket;
use Hoa\Test;
use Hoa\Websocket\Socket as SUT;

/**
 * Class \Hoa\Websocket\Test\Unit\Socket.
 *
 * Test suite for the socket class.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
class Socket extends Test\Unit\Suite
{
    public function case_is_a_socket()
    {
        $this
            ->when($result = new SUT('tcp://hoa-project:net:8889'))
            ->then
                ->object($result)
                    ->isInstanceOf('Hoa\Socket\Socket');
    }

    public function case_constructor()
    {
        $this
            ->given(
                $uri      = 'tcp://hoa-project.net:8889',
                $secured  = true,
                $endPoint = '/foobar'
            )
            ->when($result = new SUT($uri, $secured, $endPoint))
            ->then
                ->integer($result->getAddressType())
                    ->isEqualTo(SUT::ADDRESS_DOMAIN)
                ->string($result->getTransport())
                    ->isEqualTo('tcp')
                ->string($result->getAddress())
                    ->isEqualTo('hoa-project.net')
                ->integer($result->getPort())
                    ->isEqualTo(8889)
                ->boolean($result->isSecured())
                    ->isTrue()
                ->string($result->getEndPoint())
                    ->isEqualTo($endPoint);
    }

    public function case_get_endpoint()
    {
        $this
            ->given(
                $uri      = 'tcp://hoa-project.net:8889',
                $secured  = true,
                $endPoint = '/foobar',
                $socket   = new SUT($uri, $secured, $endPoint)
            )
            ->when($result = $socket->getEndPoint())
            ->then
                ->string($result)
                    ->isEqualTo($endPoint);
    }

    public function case_is_ws_transport_registered()
    {
        $this->_case_is_transport_registered('ws');
    }

    public function case_is_wss_transport_registered()
    {
        $this->_case_is_transport_registered('wss');
    }

    protected function _case_is_transport_registered($transport)
    {
        return
            $this
                ->when($result = HoaSocket\Transport::exists($transport))
                ->then
                    ->boolean($result)
                        ->isTrue();
    }

    public function case_transport_factory_invalid_URI()
    {
        $this
            ->exception(function () {
                SUT::transportFactory('foo');
            })
                ->isInstanceOf('Hoa\Websocket\Exception');
    }

    public function case_transport_unsecured_domain_with_port_with_endpoint()
    {
        $this->_case_transport_factory(
            'ws://hoa-project.net:8889/foobar',
            [
                'type'     => SUT::ADDRESS_DOMAIN,
                'address'  => 'hoa-project.net',
                'port'     => 8889,
                'endPoint' => '/foobar',
                'secured'  => false
            ]
        );
    }

    public function case_transport_unsecured_domain_with_port_without_endpoint()
    {
        $this->_case_transport_factory(
            'ws://hoa-project.net:8889',
            [
                'type'     => SUT::ADDRESS_DOMAIN,
                'address'  => 'hoa-project.net',
                'port'     => 8889,
                'endPoint' => '/',
                'secured'  => false
            ]
        );
    }

    public function case_transport_unsecured_domain_without_port_without_endpoint()
    {
        $this->_case_transport_factory(
            'ws://hoa-project.net',
            [
                'type'     => SUT::ADDRESS_DOMAIN,
                'address'  => 'hoa-project.net',
                'port'     => 80,
                'endPoint' => '/',
                'secured'  => false
            ]
        );
    }

    public function case_transport_secured_domain_with_port_with_endpoint()
    {
        $this->_case_transport_factory(
            'wss://hoa-project.net:8889/foobar',
            [
                'type'     => SUT::ADDRESS_DOMAIN,
                'address'  => 'hoa-project.net',
                'port'     => 8889,
                'endPoint' => '/foobar',
                'secured'  => true
            ]
        );
    }

    public function case_transport_secured_domain_with_port_without_endpoint()
    {
        $this->_case_transport_factory(
            'wss://hoa-project.net:8889',
            [
                'type'     => SUT::ADDRESS_DOMAIN,
                'address'  => 'hoa-project.net',
                'port'     => 8889,
                'endPoint' => '/',
                'secured'  => true
            ]
        );
    }

    public function case_transport_secured_domain_without_port_without_endpoint()
    {
        $this->_case_transport_factory(
            'wss://hoa-project.net',
            [
                'type'     => SUT::ADDRESS_DOMAIN,
                'address'  => 'hoa-project.net',
                'port'     => 443,
                'endPoint' => '/',
                'secured'  => true
            ]
        );
    }

    public function case_transport_query_strings_in_the_endpoint()
    {
        $this->_case_transport_factory(
            'wss://hoa-project.net:8889/hello/world?foo=bar&baz=qux',
            [
                'type'     => SUT::ADDRESS_DOMAIN,
                'address'  => 'hoa-project.net',
                'port'     => 8889,
                'endPoint' => '/hello/world?foo=bar&baz=qux',
                'secured'  => true
            ]
        );
    }

    protected function _case_transport_factory($uri, array $expect)
    {
        return
            $this
                ->when($result = SUT::transportFactory($uri))
                ->then
                    ->object($result)
                        ->isInstanceOf(SUT::class)
                    ->integer($result->getAddressType())
                        ->isEqualTo($expect['type'])
                    ->string($result->getTransport())
                        ->isEqualTo('tcp')
                    ->string($result->getAddress())
                        ->isEqualTo($expect['address'])
                    ->integer($result->getPort())
                        ->isEqualTo($expect['port'])
                    ->string($result->getEndPoint())
                        ->isEqualTo($expect['endPoint'])
                    ->boolean($result->isSecured())
                        ->isEqualTo($expect['secured']);
    }
}
