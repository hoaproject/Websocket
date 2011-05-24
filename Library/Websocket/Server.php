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
 * \Hoa\Websocket\Exception
 */
-> import('Websocket.Exception.~')

/**
 * \Hoa\Websocket\Exception\BadProtocol
 */
-> import('Websocket.Exception.BadProtocol')

/**
 * \Hoa\Websocket\Node
 */
-> import('Websocket.Node')

/**
 * \Hoa\Websocket\Protocol\Hybi07
 */
-> import('Websocket.Protocol.Hybi07')

/**
 * \Hoa\Websocket\Protocol\Hybi00
 */
-> import('Websocket.Protocol.Hybi00');

}

namespace Hoa\Websocket {

/**
 * Class \Hoa\Websocket\Server.
 *
 * A cross-protocol Websocket server.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2011 Ivan Enderlin.
 * @license    New BSD License
 */

class Server implements \Hoa\Core\Event\Listenable {

    /**
     * Opcode: continuation frame.
     *
     * @const int
     */
    const OPCODE_CONTINUATION_FRAME = 0x0;

    /**
     * Opcode: text frame.
     *
     * @const int
     */
    const OPCODE_TEXT_FRAME         = 0x1;

    /**
     * Opcode: binary frame.
     *
     * @const int
     */
    const OPCODE_BINARY_FRAME       = 0x2;

    /**
     * Opcode: connection close.
     *
     * @const int
     */
    const OPCODE_CONNECTION_CLOSE   = 0x8;

    /**
     * Opcode: ping.
     *
     * @const int
     */
    const OPCODE_PING               = 0x9;

    /**
     * Opcode: pong.
     *
     * @const int
     */
    const OPCODE_PONG               = 0xa;

    /**
     * Listeners.
     *
     * @var \Hoa\Core\Event\Listener object
     */
    protected $_on     = null;

    /**
     * Server.
     *
     * @var \Hoa\Socket\Connection\Server object
     */
    protected $_server = null;



    /**
     * Create a websocket server.
     * 3 events can be listened: message, close & error.
     *
     * @access  public
     * @param   \Hoa\Socket\Connection\Server  $server    Server.
     * @return  void
     * @throw   \Hoa\Socket\Connection\Exception
     */
    public function __construct ( \Hoa\Socket\Connection\Server $server ) {

        $this->_server = $server;
        $this->_server->setNodeName('\Hoa\Websocket\Node');
        $this->_on     = new \Hoa\Core\Event\Listener($this, array(
            'message',
            'close',
            'error'
        ));

        return;
    }

    /**
     * Attach a callable to this listenable object.
     *
     * @access  public
     * @param   string  $listenerId    Listener ID.
     * @param   mixed   $call          First callable part.
     * @param   mixed   $able          Second callable part (if needed).
     * @return  \Hoa\Websocket\Server
     * @throw   \Hoa\Core\Exception
     */
    public function on ( $listenerId, $call, $able = '' ) {

        return $this->_on->attach($listenerId, $call, $able);
    }

    /**
     * Run the server.
     *
     * @access  public
     * @return  void
     */
    public function run ( ) {

        $this->_server->connectAndWait();

        while(true) foreach($this->_server->select() as $node) {

            try {

                if(FAILED === $node->getHandshake()) {

                    $this->doHandshake();

                    continue;
                }

                $frame = $node->getProtocolImplementation()->readFrame();

                switch($frame['opcode']) {

                    case self::OPCODE_CONTINUATION_FRAME:
                        $node->appendMessageFragment($frame['message']);

                        if(0x1 == $frame['fin']) {

                            $message = $node->getFragmentedMessage();
                            $node->clearFragmentation();
                            $this->_on->fire(
                                'message',
                                new \Hoa\Core\Event\Bucket($message)
                            );
                        }
                      break;

                    case self::OPCODE_TEXT_FRAME:
                        $this->_on->fire(
                            'message',
                            new \Hoa\Core\Event\Bucket($frame['message'])
                        );
                      break;

                    case self::OPCODE_CONNECTION_CLOSE:
                        $this->_on->fire(
                            'close',
                            new \Hoa\Core\Event\Bucket()
                        );
                        $this->_server->disconnect();
                      break;

                    default:
                        throw new Exception(
                            'Opcode 0x%x is not supported by this server.',
                            0, $frame['opcode']);
                }
            }
            catch ( \Hoa\Core\Exception\Idle $e ) {

                $this->_on->fire('error', new \Hoa\Core\Event\Bucket($e));
                $this->_server->disconnect();
            }
        }

        $this->_server->disconnect();

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

        $buffer = $this->_server->read(2048);
        $x      = explode("\r\n", $buffer);
        $h      = array();

        for($i = 1, $m = count($x) - 3; $i <= $m; ++$i)
            $h[strtolower(trim(substr($x[$i], 0, $p = strpos($x[$i], ':'))))] =
                trim(substr($x[$i], $p + 1));

        if(0 !== preg_match('#^GET (.*) HTTP/1\.[01]#', $buffer, $match))
            $h['resource'] = trim($match[1], '/');

        $h['__Body'] = $x[count($x) - 1];

        // Hybi07.
        try {

            $hybi07 = new Protocol\Hybi07($this->_server);
            $hybi07->doHandshake($h);
            $this->_server->getCurrentNode()->setProtocolImplementation(
                $hybi07
            );
        }
        catch ( Exception\BadProtocol $e ) {

            unset($hybi07);

            // Hybi00.
            try {

                $hybi00 = new Protocol\Hybi00($this->_server);
                $hybi00->doHandshake($h);
                $this->_server->getCurrentNode()->setProtocolImplementation(
                    $hybi00
                );
            }
            catch ( Exception\BadProtocol $e ) {

                unset($hybi00);
                $this->_server->disconnect();

                throw new Exception\BadProtocol(
                    'All protocol failed.', 1, null);
            }
        }

        return;
    }

    /**
     * Send a message to a specific node/connection.
     * It is just a “inline” method, a shortcut.
     *
     * @access  public
     * @param   string               $message    Message.
     * @param   \Hoa\Websocket\Node  $node       Node.
     * @return  void
     */
    public function send ( $message, Node $node = null ) {

        return $this->_server
                    ->getCurrentNode()
                    ->getProtocolImplementation()
                    ->send($message, $node);
    }

    /**
     * Get server.
     *
     * @access  public
     * @return  \Hoa\Socket\Connection\Server
     */
    public function getServer ( ) {

        return $this->_server;
    }
}

}
