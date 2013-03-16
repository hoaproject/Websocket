<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2013, Ivan Enderlin. All rights reserved.
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
 * \Hoa\Websocket\Protocol\Rfc6455
 */
-> import('Websocket.Protocol.Rfc6455')

/**
 * \Hoa\Websocket\Protocol\Hybi00
 */
-> import('Websocket.Protocol.Hybi00')

/**
 * \Hoa\Http\Request
 */
-> import('Http.Request');

}

namespace Hoa\Websocket {

/**
 * Class \Hoa\Websocket\Server.
 *
 * A cross-protocol Websocket server.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2013 Ivan Enderlin.
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
     * Close: normal.
     *
     * @const int
     */
    const CLOSE_NORMAL              = 1000;

    /**
     * Close: going away.
     *
     * @const int
     */
    const CLOSE_GOING_AWAY          = 1001;

    /**
     * Close: protocol error.
     *
     * @const int
     */
    const CLOSE_PROTOCOL_ERROR      = 1002;

    /**
     * Close: data error.
     *
     * @const int
     */
    const CLOSE_DATA_ERROR          = 1003;

    /**
     * Close: status error.
     *
     * @const int
     */
    const CLOSE_STATUS_ERROR        = 1005;

    /**
     * Close: abnormal.
     *
     * @const int
     */
    const CLOSE_ABNORMAL            = 1006;

    /**
     * Close: message error.
     *
     * @const int
     */
    const CLOSE_MESSAGE_ERROR       = 1007;

    /**
     * Close: policy error.
     *
     * @const int
     */
    const CLOSE_POLICY_ERROR        = 1008;

    /**
     * Close: message too big.
     *
     * @const int
     */
    const CLOSE_MESSAGE_TOO_BIG     = 1009;

    /**
     * Close: extension missing.
     *
     * @const int
     */
    const CLOSE_EXTENSION_MISSING   = 1010;

    /**
     * Close: server error.
     *
     * @const int
     */
    const CLOSE_SERVER_ERROR        = 1011;

    /**
     * Close: TLS.
     *
     * @const int
     */
    const CLOSE_TLS                 = 1015;


    /**
     * Listeners.
     *
     * @var \Hoa\Core\Event\Listener object
     */
    protected $_on      = null;

    /**
     * Server.
     *
     * @var \Hoa\Socket\Server object
     */
    protected $_server  = null;

    /**
     * Request (mainly parser).
     *
     * @var \Hoa\Http\Request object
     */
    protected $_request = null;



    /**
     * Create a websocket server.
     * 6 events can be listened: open, message, binary-message, ping, close and
     * error.
     *
     * @access  public
     * @param   \Hoa\Socket\Server  $server    Server.
     * @param   \Hoa\Http\Request   $request   Request parser.
     * @return  void
     * @throw   \Hoa\Socket\Exception
     */
    public function __construct ( \Hoa\Socket\Server $server,
                                  \Hoa\Http\Request  $request = null ) {

        $this->_server = $server;
        $this->_server->setNodeName('\Hoa\Websocket\Node');
        $this->_on     = new \Hoa\Core\Event\Listener($this, array(
            'open',
            'message',
            'binary-message',
            'ping',
            'close',
            'error'
        ));

        if(null === $request)
            $request = new \Hoa\Http\Request();

        $this->setRequest($request);

        return;
    }

    /**
     * Attach a callable to this listenable object.
     *
     * @access  public
     * @param   string  $listenerId    Listener ID.
     * @param   mixed   $callable      Callable.
     * @return  \Hoa\Websocket\Server
     * @throw   \Hoa\Core\Exception
     */
    public function on ( $listenerId, $callable ) {

        $this->_on->attach($listenerId, $callable);

        return $this;
    }

    /**
     * Run the server.
     *
     * @access  public
     * @return  void
     */
    public function run ( ) {

        $this->getServer()->connectAndWait();

        while(true) foreach($this->getServer()->select() as $node) {

            try {

                if(FAILED === $node->getHandshake()) {

                    $this->doHandshake();
                    $this->_on->fire(
                        'open',
                        new \Hoa\Core\Event\Bucket()
                    );

                    continue;
                }

                $frame = $node->getProtocolImplementation()->readFrame();

                if(false === $frame)
                    continue;

                $fromText   = false;
                $fromBinary = false;

                switch($frame['opcode']) {

                    case self::OPCODE_BINARY_FRAME:
                        $fromBinary = true;

                    case self::OPCODE_TEXT_FRAME:
                        if(0x1 === $frame['fin']) {

                            if(0 < $node->getNumberOfFragments()) {

                                $this->close(self::CLOSE_PROTOCOL_ERROR);

                                break;
                            }

                            if(true === $fromBinary) {

                                $fromBinary = false;
                                $this->_on->fire(
                                    'binary-message',
                                    new \Hoa\Core\Event\Bucket(array(
                                        'message' => $frame['message']
                                    ))
                                );

                                break;
                            }

                            if(false === (bool) preg_match('//u', $frame['message'])) {

                                $this->close(self::CLOSE_MESSAGE_ERROR);

                                break;
                            }

                            $this->_on->fire(
                                'message',
                                new \Hoa\Core\Event\Bucket(array(
                                    'message' => $frame['message']
                                ))
                            );

                            break;
                        }

                        $fromText = true;

                    case self::OPCODE_CONTINUATION_FRAME:
                        if(false === $fromText) {

                            if(0 === $node->getNumberOfFragments()) {

                                $this->close(self::CLOSE_PROTOCOL_ERROR);

                                break;
                            }
                        }
                        else {

                            $fromText = false;

                            if(true === $fromBinary) {

                                $node->setBinary(true);
                                $fromBinary = false;
                            }
                        }

                        $node->appendMessageFragment($frame['message']);

                        if(0x1 === $frame['fin']) {

                            $message  = $node->getFragmentedMessage();
                            $isBinary = $node->isBinary();
                            $node->clearFragmentation();

                            if(true === $isBinary) {

                                $this->_on->fire(
                                    'binary-message',
                                    new \Hoa\Core\Event\Bucket(array(
                                        'message' => $message
                                    ))
                                );

                                break;
                            }

                            if(false === (bool) preg_match('//u', $message)) {

                                $this->close(self::CLOSE_MESSAGE_ERROR);

                                break;
                            }

                            $this->_on->fire(
                                'message',
                                new \Hoa\Core\Event\Bucket(array(
                                    'message' => $message
                                ))
                            );
                        }
                      break;

                    case self::OPCODE_PING:
                        $message = &$frame['message'];

                        if(   0x0  === $frame['fin']
                           || 0x7d  <  $frame['length']) {

                            $this->close(self::CLOSE_PROTOCOL_ERROR);

                            break;
                        }

                        $this->getServer()
                             ->getCurrentNode()
                             ->getProtocolImplementation()
                             ->writeFrame(
                                 $message,
                                 self::OPCODE_PONG,
                                 true
                             );

                        $this->_on->fire(
                            'ping',
                            new \Hoa\Core\Event\Bucket(array(
                                'message' => $message
                            ))
                        );
                      break;

                    case self::OPCODE_PONG:
                        if(0 === $frame['fin']) {

                            $this->close(self::CLOSE_PROTOCOL_ERROR);

                            break;
                        }
                      break;

                    case self::OPCODE_CONNECTION_CLOSE:
                        $length = &$frame['length'];

                        if(   1    === $length
                           || 0x7d  <  $length) {

                            $this->close(self::CLOSE_PROTOCOL_ERROR);

                            break;
                        }

                        $code   = self::CLOSE_NORMAL;
                        $reason = null;

                        if(0 < $length) {

                            $message = &$frame['message'];
                            $_code   = unpack('nc', substr($message, 0, 2));
                            $code    = &$_code['c'];

                            if(   1000  >  $code
                               || (1004 <= $code && $code <= 1006)
                               || (1012 <= $code && $code <= 1016)
                               || 5000  <= $code) {

                                $this->close(self::CLOSE_PROTOCOL_ERROR);

                                break;
                            }

                            if(2 < $length) {

                                $reason = substr($message, 2);

                                if(false === (bool) preg_match('//u', $reason)) {

                                    $this->close(self::CLOSE_MESSAGE_ERROR);

                                    break;
                                }
                            }
                        }

                        $this->close(self::CLOSE_NORMAL);
                        $this->_on->fire(
                            'close',
                            new \Hoa\Core\Event\Bucket(array(
                                'code'   => $code,
                                'reason' => $reason
                            ))
                        );
                      break;

                    default:
                        $this->close(self::CLOSE_PROTOCOL_ERROR);
                }
            }
            catch ( \Hoa\Core\Exception\Idle $e ) {

                $this->close(self::CLOSE_SERVER_ERROR);
                $this->_on->fire('error', new \Hoa\Core\Event\Bucket(array(
                    'exception' => $e
                )));
            }
        }

        $this->getServer()->disconnect();

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

        $buffer  = $this->getServer()->read(2048);
        $server  = $this->getServer();
        $request = $this->getRequest();
        $request->parse($buffer);

        // Rfc6455.
        try {

            $rfc6455 = new Protocol\Rfc6455($server);
            $rfc6455->doHandshake($request);
            $server->getCurrentNode()->setProtocolImplementation($rfc6455);
        }
        catch ( Exception\BadProtocol $e ) {

            unset($rfc6455);

            // Hybi00.
            try {

                $hybi00 = new Protocol\Hybi00($server);
                $hybi00->doHandshake($request);
                $server->getCurrentNode()->setProtocolImplementation($hybi00);
            }
            catch ( Exception\BadProtocol $e ) {

                unset($hybi00);
                $server->disconnect();

                throw new Exception\BadProtocol(
                    'All protocol failed.', 1);
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
     * @param   int                  $opcode     Opcode.
     * @param   bool                 $end        Whether it is the last frame of
     *                                           the message.
     * @return  void
     */
    public function send ( $message, Node $node = null,
                           $opcode = self::OPCODE_TEXT_FRAME, $end = true ) {

        return $this->getServer()
                    ->getCurrentNode()
                    ->getProtocolImplementation()
                    ->send($message, $node, $opcode, $end);
    }

    /**
     * Close a specific node/connection.
     * It is just a “inline” method, a shortcut.
     *
     * @access  public
     * @param   int                  $code      Code (please, see
     *                                          self::CLOSE_* constants).
     * @param   string               $reason    Reason.
     * @param   \Hoa\Websocket\Node  $node      Node.
     * @return  void
     */
    public function close ( $code = self::CLOSE_NORMAL, $reason = null,
                            Node $node = null ) {

        $server = $this->getServer();
        $server->getCurrentNode()
               ->getProtocolImplementation()
               ->close($code, $reason, $node);

        return $server->disconnect();
    }

    /**
     * Get server.
     *
     * @access  public
     * @return  \Hoa\Socket\Server
     */
    public function getServer ( ) {

        return $this->_server;
    }

    /**
     * Set request (mainly parser).
     *
     * @access  public
     * @param   \Hoa\Http\Request  $request    Request.
     * @return  \Hoa\Http\Request
     */
    public function setRequest ( \Hoa\Http\Request $request ) {

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

}
