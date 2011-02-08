<?php

/**
 * Hoa Framework
 *
 *
 * @license
 *
 * GNU General Public License
 *
 * This file is part of Hoa Open Accessibility.
 * Copyright (c) 2007, 2010 Ivan ENDERLIN. All rights reserved.
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
 * \Hoa\Socket\Connection\Node
 */
-> import('Socket.Connection.Node');

}

namespace Hoa\Websocket {

/**
 * Class \Hoa\Websocket\Node.
 *
 * Describes a websocket node.
 *
 * @author     Ivan ENDERLIN <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright (c) 2007, 2010 Ivan ENDERLIN.
 * @license    http://gnu.org/licenses/gpl.txt GNU GPL
 */

class Node extends \Hoa\Socket\Connection\Node {

    /**
     * Whether it is the first message.
     *
     * @var \Hoa\Websocket\Node bool
     */
    protected $_first     = true;

    /**
     * Whether the handshake succeed.
     *
     * @var \Hoa\Websocket\Node bool
     */
    protected $_handshake = false;



    /**
     * Set whether it is the first message.
     *
     * @access  public
     * @param   bool    $first    First.
     * @return  bool
     */
    public function setFirst ( $first ) {

        $old          = $this->_first;
        $this->_first = $first;

        return $old;
    }

    /**
     * Set handshake success.
     *
     * @access  public
     * @param   bool    $handshake    Handshake.
     * @return  bool
     */
    public function setHandshake ( $handshake ) {

        $old              = $this->_handshake;
        $this->_handshake = $handshake;

        return $old;
    }

    /**
     * Whether it is the first message.
     *
     * @access  public
     * @return  bool
     */
    public function isFirstMessage ( ) {

        return $this->_first;
    }

    /**
     * Whether the handshake succeed.
     *
     * @access  public
     * @return  bool
     */
    public function getHandshake ( ) {

        return $this->_handshake;
    }
}

}
