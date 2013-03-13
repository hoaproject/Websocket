![Hoa](http://static.hoa-project.net/Image/Hoa_small.png)

Hoa is a **modular**, **extensible** and **structured** set of PHP libraries.
Moreover, Hoa aims at being a bridge between industrial and research worlds.

# Hoa\Websocket

This library allows to manipulate Websockets and proposes a server. It supports
two specifications [RFC6455](https://tools.ietf.org/html/rfc6455) and
[Hybi](https://tools.ietf.org/wg/hybi/draft-ietf-hybi-thewebsocketprotocol/) (at
the same time).

## Quick usage

As a quick overview, we propose to start a websocket server and echo messages.
The class `Hoa\Websocket\Server` proposes five listeners: `open`, `message`,
`binary-message`, `ping`, `close` and `error`. Thus:

    $websocket = new Hoa\Websocket\Server(
        new Hoa\Socket\Server('tcp://127.0.0.1:8889')
    );
    $websocket->on('open', function ( Hoa\Core\Event\Bucket $bucket ) {

        echo 'new connection', "\n";

        return;
    });
    $websocket->on('message', function ( Hoa\Core\Event\Bucket $bucket ) {

        $data = $bucket->getData();
        echo '> message ', $data['message'], "\n";
        $bucket->getSource()->send($data['message']);
        echo '< echo', "\n";

        return;
    });
    $websocket->on('close', function ( Hoa\Core\Event\Bucket $bucket ) {

        echo 'connection closed', "\n";

        return;
    });
    $websocket->on('error', function ( Hoa\Core\Event\Bucket $bucket ) {

        $data = $bucket->getData();
        echo 'error: ', $data['exception']->raise(), "\n";

        return;
    });
    $websocket->run();

Finally, we have to write a client in HTML and Javascript:

    <input type="text" id="input" placeholder="Message…" />
    <hr />
    <pre id="output"></pre>

    <script>
      var host   = 'ws://127.0.0.1:8889';
      var socket = null;
      var input  = document.getElementById('input');
      var output = document.getElementById('output');
      var print  = function ( message ) {

          var samp       = document.createElement('samp');
          samp.innerHTML = message + '\n';
          output.appendChild(samp);

          return;
      };

      input.addEventListener('keyup', function ( evt ) {

          if(13 === evt.keyCode) {

              var msg = input.value;

              if(!msg)
                  return;

              try {

                  socket.send(msg);
                  input.value = '';
                  input.focus();
              }
              catch ( e ) {

                  console.log(e);
              }

              return;
          }
      });

      try {

          socket = new WebSocket(host);
          socket.onopen = function ( ) {

              print('connection is opened');
              input.focus();

              return;
          };
          socket.onmessage = function ( msg ) {

              print(msg.data);

              return;
          };
          socket.onclose = function ( ) {

              print('connection is closed');

              return;
          };
      }
      catch ( e ) {

          console.log(e);

          return;
      }
    </script>

Here we are. All sent messages are echoed.

## Documentation

Different documentations can be found on the website:
[http://hoa-project.net/](http://hoa-project.net/).

## License

Hoa is under the New BSD License (BSD-3-Clause). Please, see
[`LICENSE`](http://hoa-project.net/LICENSE).
