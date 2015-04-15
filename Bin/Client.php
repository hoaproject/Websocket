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

namespace Hoa\Websocket\Bin;

use Hoa\Console;
use Hoa\Socket;
use Hoa\Websocket;

/**
 * Class \Hoa\Websocket\Bin\Client.
 *
 * Basic WebSocket client.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */

class Client extends Console\Dispatcher\Kit
{
    /**
     * Options description.
     *
     * @var array
     */
    protected $options = [
        ['server', Console\GetOption::REQUIRED_ARGUMENT, 's'],
        ['help',   Console\GetOption::NO_ARGUMENT,       'h'],
        ['help',   Console\GetOption::NO_ARGUMENT,       '?']
    ];



    /**
     * The entry method.
     *
     * @return  int
     */
    public function main()
    {
        $server = '127.0.0.1:8889';

        while (false !== $c = $this->getOption($v)) {
            switch ($c) {

                case 's':
                    $server = $v;

                    break;

                case 'h':
                case '?':
                    return $this->usage();

                case '__ambiguous':
                    $this->resolveOptionAmbiguity($v);

                    break;

            }
        }


        $readline = new Console\Readline();
        $client   = new Websocket\Client(
            new Socket\Client('tcp://' . $server)
        );
        $client->setHost('localhost');
        $client->connect();

        do {
            $line = $readline->readLine('> ');

            if (false === $line || 'quit' === $line) {
                break;
            }

            $client->send($line);
        } while (true);

        $client->close();

        return;
    }

    /**
     * The command usage.
     *
     * @return  int
     */
    public function usage()
    {
        echo
            'Usage   : websocket:client <options>', "\n",
            'Options :', "\n",
            $this->makeUsageOptionsList([
                's'    => 'Server URI (default: 127.0.0.1:8889).',
                'help' => 'This help.'
            ]), "\n";

        return;
    }
}

__halt_compiler();
Basic WebSocket client.
