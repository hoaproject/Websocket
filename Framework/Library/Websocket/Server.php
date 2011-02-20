<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * GNU General Public License
 *
 * This file is part of Hoa Open Accessibility.
 * Copyright (c) 2007, 2011 Ivan ENDERLIN. All rights reserved.
 *
 * HOA Open Accessibility is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * HOA Open Accessibility is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with HOA Open Accessibility; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace {

from('Hoa')

/**
 * \Hoa\Socket\Connection\Server
 */
-> import('Socket.Connection.Server')

/**
 * \Hoa\Websocket\Node
 */
-> import('Websocket.Node');

}

namespace Hoa\Websocket {

/**
 * Class \Hoa\Websocket\Server.
 *
 * Websocket server.
 * Please, read the http://dev.w3.org/html5/websockets/ documentation.
 *
 * @author     Ivan ENDERLIN <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright (c) 2007, 2011 Ivan ENDERLIN.
 * @license    http://gnu.org/licenses/gpl.txt GNU GPL
 */

abstract class Server extends \Hoa\Socket\Connection\Server {

    /**
     * Create a websocket server.
     *
     * @access  public
     * @param   \Hoa\Socket\Socketable  $socket     Socket.
     * @param   int                     $timeout    Timeout.
     * @param   int                     $flag       Flag, see the parent::*
     *                                              constants.
     * @param   string                  $context    Context ID (please, see the
     *                                              \Hoa\Stream\Context class).
     * @return  void
     * @throw   \Hoa\Socket\Connection\Exception
     */
    public function __construct ( \Hoa\Socket\Socketable $socket, $timeout = 30,
                                  $flag = -1, $context = null ) {

        parent::__construct($socket, $timeout, $flag, $context);
        $this->connectAndWait();
        $this->setNodeName('\Hoa\Websocket\Node');

        while(true)
            foreach($this->select() as $node) {

                $buffer = $this->read(2048);

                if(FAILED === $node->getHandshake())
                    $this->handshake($node, $buffer);
                else {

                    $buffer = $this->unwrap($buffer);

                    if(empty($buffer)) {

                        $this->disconnect();
                        continue;
                    }

                    $this->compute($node, $buffer);
                }
            }

        return;
    }

    /**
     * Try the handshake.
     *
     * @access  private
     * @param   \Hoa\Websocket\Node  $node      Current connection node.
     * @param   string              $buffer    HTTP headers.
     * @return  void
     */
    final private function handshake ( Node $node, $buffer ) {

        $x = explode("\r\n", $buffer);
        $h = array();

        for($i = 1, $m = count($x) - 3; $i <= $m; ++$i)
            $h[strtolower(substr($x[$i], 0, strpos($x[$i], ':')))] =
                trim(substr($x[$i], strpos($x[$i], ':') + 2));

        if(0 !== preg_match('#GET (.*) HTTP#', $buffer, $match))
            $h['resource'] = $match[1];

        $key1      = $h['sec-websocket-key1'];
        $key2      = $h['sec-websocket-key2'];
        $key3      = $x[count($x) - 1];
        $location  = $h['host'] . '/Server.php';
        $keynumb1  = (int) preg_replace('#[^0-9]#', '', $key1);
        $keynumb2  = (int) preg_replace('#[^0-9]#', '', $key2);

        $spaces1   = substr_count($key1, ' ');
        $spaces2   = substr_count($key2, ' ');        

        $part1     = pack('N', $keynumb1 / $spaces1);
        $part2     = pack('N', $keynumb2 / $spaces2);
        $challenge = $part1 . $part2 . $key3;
        $response  = md5($challenge, true);

        $this->writeAll(
            'HTTP/1.1 101 WebSocket Protocol Handshake' . "\r\n" .
            'Upgrade: WebSocket' . "\r\n" .
            'Connection: Upgrade' . "\r\n" .
            'Sec-WebSocket-Origin: ' . $h['origin'] . "\r\n" .
            'Sec-WebSocket-Location: ws://' . $h['host'] .
            $h['resource'] . "\r\n" .
            "\r\n" .
            $response . "\r\n"
        );

        $node->setHandshake(SUCCEED);

        return;
    }

    /**
     * Compute the receive message.
     *
     * @access  protected
     * @param   \Hoa\Websocket\Node  $sourceNode    Source node.
     * @param   string              $message       Message.
     * @return  void
     */
    abstract protected function compute ( $sourceNode, $message );

    /**
     * Send a message to a specific node/connection.
     * It is just a “inline” method, a shortcut.
     *
     * @access  protected
     * @param   \Hoa\Websocket\Node  $node       Node.
     * @param   string              $message    Message.
     * @return  void
     */
    protected function send ( Node $node, $message ) {

        $old = $this->getStream();
        $this->_setStream($node->getSocket());

        $message = $this->wrap($message);
        $this->writeAll($message);

        $this->_setStream($old);

        return;
    }

    /**
     * Wrap a string before writing/sending.
     *
     * @access  public
     * @param   string  $string    String to wrap.
     * @return  string
     */
    final public function wrap ( $string ) {

        return chr(0) . $string . chr(255);
    }

    /**
     * Unwrap a string before reading/receiving.
     *
     * @access  public
     * @param   string  $string    String to unwrap.
     * @return  string
     */
    final public function unwrap ( $string ) {

        return substr($string, 1, strlen($string) - 2);
    }
}

}
