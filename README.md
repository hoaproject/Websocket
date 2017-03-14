<p align="center">
  <img src="https://static.hoa-project.net/Image/Hoa.svg" alt="Hoa" width="250px" />
</p>

---

<p align="center">
  <a href="https://travis-ci.org/hoaproject/Websocket"><img src="https://img.shields.io/travis/hoaproject/Websocket/master.svg" alt="Build status" /></a>
  <a href="https://coveralls.io/github/hoaproject/Websocket?branch=master"><img src="https://img.shields.io/coveralls/hoaproject/Websocket/master.svg" alt="Code coverage" /></a>
  <a href="https://packagist.org/packages/hoa/websocket"><img src="https://img.shields.io/packagist/dt/hoa/websocket.svg" alt="Packagist" /></a>
  <a href="https://hoa-project.net/LICENSE"><img src="https://img.shields.io/packagist/l/hoa/websocket.svg" alt="License" /></a>
</p>
<p align="center">
  Hoa is a <strong>modular</strong>, <strong>extensible</strong> and
  <strong>structured</strong> set of PHP libraries.<br />
  Moreover, Hoa aims at being a bridge between industrial and research worlds.
</p>

# Hoa\Websocket

[![Help on IRC](https://img.shields.io/badge/help-%23hoaproject-ff0066.svg)](https://webchat.freenode.net/?channels=#hoaproject)
[![Help on Gitter](https://img.shields.io/badge/help-gitter-ff0066.svg)](https://gitter.im/hoaproject/central)
[![Documentation](https://img.shields.io/badge/documentation-hack_book-ff0066.svg)](https://central.hoa-project.net/Documentation/Library/Websocket)
[![Board](https://img.shields.io/badge/organisation-board-ff0066.svg)](https://waffle.io/hoaproject/websocket)

This library allows to manipulate the WebSocket protocol and proposes a server
and a client. It supports two specifications
[RFC6455](https://tools.ietf.org/html/rfc6455) and
[Hybi](https://tools.ietf.org/wg/hybi/draft-ietf-hybi-thewebsocketprotocol/) (at
the same time).

[Learn more](https://central.hoa-project.net/Documentation/Library/Websocket).

## Installation

With [Composer](https://getcomposer.org/), to include this library into
your dependencies, you need to
require [`hoa/websocket`](https://packagist.org/packages/hoa/websocket):

```sh
$ composer require hoa/websocket '~3.0'
```

For more installation procedures, please read [the Source
page](https://hoa-project.net/Source.html).

## Testing

Before running the test suites, the development dependencies must be installed:

```sh
$ composer install
```

Then, to run all the test suites:

```sh
$ vendor/bin/hoa test:run
```

For more information, please read the [contributor
guide](https://hoa-project.net/Literature/Contributor/Guide.html).

## Quick usage

As a quick overview, we propose to start a websocket server and echo messages.
The class `Hoa\Websocket\Server` proposes six listeners: `open`, `message`,
`binary-message`, `ping`, `close` and `error`. Thus:

```php
$websocket = new Hoa\Websocket\Server(
    new Hoa\Socket\Server('ws://127.0.0.1:8889')
);
$websocket->on('open', function (Hoa\Event\Bucket $bucket) {
    echo 'new connection', "\n";

    return;
});
$websocket->on('message', function (Hoa\Event\Bucket $bucket) {
    $data = $bucket->getData();
    echo '> message ', $data['message'], "\n";
    $bucket->getSource()->send($data['message']);
    echo '< echo', "\n";

    return;
});
$websocket->on('close', function (Hoa\Event\Bucket $bucket) {
    echo 'connection closed', "\n";

    return;
});
$websocket->run();
```

Finally, we have to write a client in HTML and Javascript:

```html
<input type="text" id="input" placeholder="Message…" />
<hr />
<pre id="output"></pre>

<script>
  var host   = 'ws://127.0.0.1:8889';
  var socket = null;
  var input  = document.getElementById('input');
  var output = document.getElementById('output');
  var print  = function (message) {
      var samp       = document.createElement('samp');
      samp.innerHTML = message + '\n';
      output.appendChild(samp);

      return;
  };

  input.addEventListener('keyup', function (evt) {
      if (13 === evt.keyCode) {
          var msg = input.value;

          if (!msg) {
              return;
          }

          try {
              socket.send(msg);
              input.value = '';
              input.focus();
          } catch (e) {
              console.log(e);
          }

          return;
      }
  });

  try {
      socket = new WebSocket(host);
      socket.onopen = function () {
          print('connection is opened');
          input.focus();

          return;
      };
      socket.onmessage = function (msg) {
          print(msg.data);

          return;
      };
      socket.onclose = function () {
          print('connection is closed');

          return;
      };
  } catch (e) {
      console.log(e);
  }
</script>
```

Here we are. All sent messages are echoed.

## Awecode

The following awecodes show this library in action:

  * [`Hoa\Websocket`](https://hoa-project.net/Awecode/Websocket.html):
    *why and how to use `Hoa\Websocket\Server` and `Hoa\Websocket\Client`? A
    simple example will illustrate the WebSocket protocol*.

## Documentation

The
[hack book of `Hoa\Websocket`](https://central.hoa-project.net/Documentation/Library/Websocket) contains
detailed information about how to use this library and how it works.

To generate the documentation locally, execute the following commands:

```sh
$ composer require --dev hoa/devtools
$ vendor/bin/hoa devtools:documentation --open
```

More documentation can be found on the project's website:
[hoa-project.net](https://hoa-project.net/).

## Getting help

There are mainly two ways to get help:

  * On the [`#hoaproject`](https://webchat.freenode.net/?channels=#hoaproject)
    IRC channel,
  * On the forum at [users.hoa-project.net](https://users.hoa-project.net).

## Contribution

Do you want to contribute? Thanks! A detailed [contributor
guide](https://hoa-project.net/Literature/Contributor/Guide.html) explains
everything you need to know.

## License

Hoa is under the New BSD License (BSD-3-Clause). Please, see
[`LICENSE`](https://hoa-project.net/LICENSE) for details.

## Related projects

The following projects are using this library:

  * [Marvirc](https://github.com/Hywan/Marvirc), A dead simple,
    extremely modular and blazing fast IRC bot,
  * [WellCommerce](http://wellcommerce.org/), Modern e-commerce engine
    built on top of Symfony 3 full-stack framework.
