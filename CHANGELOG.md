# 3.17.01.09

  * Quality: Happy new year! (Ivan Enderlin, 2017-01-09T14:50:50+01:00)
  * Test: Fix namespace. (Ivan Enderlin, 2016-10-25T08:01:35+02:00)
  * Quality: Fix `CHANGELOG.md`. (Ivan Enderlin, 2016-10-24T15:58:06+02:00)

# 3.16.10.24

  * Documentation: Update Composer instructions. (Ivan Enderlin, 2016-10-14T23:51:25+02:00)
  * Documentation: New `README.md` file. (Ivan Enderlin, 2016-10-14T23:47:22+02:00)
  * Connection: Start TLS encryption on handshake. (Ivan Enderlin, 2016-10-11T09:20:45+02:00)
  * Documentation: Update `support` properties. (Ivan Enderlin, 2016-10-11T08:51:04+02:00)

# 3.16.07.05

  * Test: Write integration test suite. (Ivan Enderlin, 2016-06-20T09:43:20+02:00)
  * Protocol: Relax UTF-8 checking when sending. (Ivan Enderlin, 2016-07-05T08:26:23+02:00)
  * Test: Fix a test case. (Ivan Enderlin, 2016-06-24T09:30:27+02:00)
  * Protocol: Read the whole frame when length is zero. (Ivan Enderlin, 2016-06-24T08:11:34+02:00)
  * Quality: Fix API documentation. (Ivan Enderlin, 2016-06-17T17:10:38+02:00)
  * Connection: Wrap listeners into a try/catch block. (Ivan Enderlin, 2016-06-17T09:22:15+02:00)
  * Connection: Better safety for `binary-message`. (Ivan Enderlin, 2016-06-15T13:41:28+02:00)
  * Connection: Capture all exceptions in `message`. (Ivan Enderlin, 2016-06-15T13:39:09+02:00)
  * Quality: Fix CS. (Ivan Enderlin, 2016-06-15T13:39:03+02:00)
  * Connection: Use `::class` instead of a string. (Ivan Enderlin, 2016-06-15T13:38:47+02:00)
  * Test: Write test suite of `…Websocket\Connection`. (Ivan Enderlin, 2016-06-15T13:38:09+02:00)
  * Client: Extract the `getNewChallenge` method. (Ivan Enderlin, 2016-05-31T23:21:03+02:00)
  * Test: Write test suite of `Hoa\Websocket\Client`. (Ivan Enderlin, 2016-05-30T17:08:33+02:00)
  * Test: Ensure disconnection if handshake fails. (Ivan Enderlin, 2016-05-30T16:53:08+02:00)
  * Quality: Rename an internal variable. (Ivan Enderlin, 2016-05-30T08:58:52+02:00)
  * Test: Write test suite of `Hoa\Websocket\Server`. (Ivan Enderlin, 2016-05-30T08:27:07+02:00)
  * Protocol: Use the `getConnection` method. (Ivan Enderlin, 2016-05-27T21:21:33+02:00)
  * Protocol: Update an exception message. (Ivan Enderlin, 2016-05-27T17:02:52+02:00)
  * Test: Write test suite of `…cket\Protocol\Hybi00`. (Ivan Enderlin, 2016-05-27T17:02:09+02:00)
  * Test: Update a test case. (Ivan Enderlin, 2016-05-27T16:49:57+02:00)
  * Test: Write test suite of `…ket\Protocol\Generic`. (Ivan Enderlin, 2016-05-27T09:58:43+02:00)
  * Protocol: Extract the `getMaskingKey` method. (Ivan Enderlin, 2016-05-24T08:54:15+02:00)
  * Documentation: Update API documentation. (Ivan Enderlin, 2016-05-23T08:56:28+02:00)
  * Documentation: Update API documentation. (Ivan Enderlin, 2016-05-23T08:56:16+02:00)
  * Test: Write test suite of `Hoa\Websocket\Node`. (Ivan Enderlin, 2016-05-20T17:02:48+02:00)
  * Test: Write test suite of `…ption\InvalidMessage`. (Ivan Enderlin, 2016-05-20T09:31:07+02:00)
  * Test: Write test suite of `…\Websocket\Exception`. (Ivan Enderlin, 2016-05-20T09:30:49+02:00)
  * Test: Write test suite of `…Exception\CloseError`. (Ivan Enderlin, 2016-05-20T09:07:59+02:00)
  * Test: Write test suite of `…xception\BadProtocol`. (Ivan Enderlin, 2016-05-20T09:07:32+02:00)
  * Test: Write test suite of `…ket\Protocol\Rfc6455`. (Ivan Enderlin, 2016-05-20T08:46:32+02:00)
  * Test: Write test suite of `…Websocket\Connection`. (Ivan Enderlin, 2016-05-20T08:45:39+02:00)
  * Protocol: `Rfc6455` uses `getConnection`. (Ivan Enderlin, 2016-05-20T08:19:45+02:00)
  * Protocol: Add the `getConnection` method. (Ivan Enderlin, 2016-05-20T08:17:32+02:00)

# 3.16.05.09

  * Test: Add a test case to `Socket` for query strs. (Ivan Enderlin, 2016-05-09T13:41:13+02:00)
  * Socket: Compile query strings. (Ivan Enderlin, 2016-05-09T13:40:10+02:00)

# 3.16.03.15

  * Connection: Catch disconnection of a node earlier. (Ivan Enderlin, 2016-02-24T07:51:18+01:00)
  * Connection: Ensure handshake before sending. (Ivan Enderlin, 2016-02-19T07:59:37+01:00)
  * Documentation: Introduce `ws://` and `wss://`. (Ivan Enderlin, 2016-02-15T08:11:28+01:00)
  * Test: Write test suite of `Hoa\Websocket\Socket`. (Ivan Enderlin, 2016-02-10T08:22:57+01:00)
  * Socket: Detect invalid URI in transport factory. (Ivan Enderlin, 2016-02-10T08:20:45+01:00)
  * Composer: Require `hoa/test`. (Ivan Enderlin, 2016-02-09T17:06:15+01:00)
  * Quality: Fix CS. (Stéphane HULARD, 2015-12-07T14:10:41+01:00)
  * Socket: Introduce `ws://` and `wss://` transports. (Stéphane HULARD, 2015-07-30T12:05:54+02:00)
  * Socket: Introduce the `Socket` class. (Stéphane HULARD, 2015-07-30T11:37:27+02:00)
  * CHANGELOG: Fix format. (Ivan Enderlin, 2016-01-14T22:23:09+01:00)

# 3.16.01.14

  * Composer: New stable libraries. (Ivan Enderlin, 2016-01-14T22:20:21+01:00)

# 3.16.01.11

  * Quality: Drop PHP5.4. (Ivan Enderlin, 2016-01-11T09:15:27+01:00)
  * Quality: Run devtools:cs. (Ivan Enderlin, 2016-01-09T09:11:24+01:00)
  * Core: Remove `Hoa\Core`. (Ivan Enderlin, 2016-01-09T08:28:37+01:00)
  * Consistency: Update `uuid` call. (Ivan Enderlin, 2015-12-08T23:48:52+01:00)
  * Consistency: Use `Hoa\Consistency`. (Ivan Enderlin, 2015-12-08T22:16:18+01:00)
  * Event: Use `Hoa\Event`. (Ivan Enderlin, 2015-11-23T22:26:40+01:00)
  * Exception: Use `Hoa\Exception`. (Ivan Enderlin, 2015-11-20T20:23:26+01:00)

# 2.15.08.05

  * Fix notice on PHP5.5+. (Metalaka, 2015-08-04T22:59:15+02:00)
  * Add `.gitignore` file. (Stéphane HULARD, 2015-08-03T11:06:58+02:00)
  * Add RFC references. (Ivan Enderlin, 2015-07-22T09:42:39+02:00)

# 2.15.05.29

  * Move to PSR-1 and PSR-2. (Ivan Enderlin, 2015-05-21T10:05:40+02:00)
  * Fix a typo. (Ivan Enderlin, 2015-04-06T12:34:42+02:00)
  * Fix typos. (Ivan Enderlin, 2015-04-06T08:39:39+02:00)
  * Fix some typos. (Ivan Enderlin, 2015-02-26T08:38:24+01:00)

# 2.15.02.16

  * Add the `CHANGELOG.md` file. (Ivan Enderlin, 2015-02-16T14:25:21+01:00)
  * Fix documentation links. (Ivan Enderlin, 2015-01-23T19:27:59+01:00)
  * Happy new year! (Ivan Enderlin, 2015-01-05T14:57:10+01:00)

# 2.14.12.10

  * Move to PSR-4. (Ivan Enderlin, 2014-12-09T18:52:41+01:00)

# 2.14.11.09

  * Remove `from`/`import` and update to PHP5.4. (Ivan Enderlin, 2014-09-24T09:22:46+02:00)

# 2.14.09.23

  * Add `branch-alias`. (Stéphane PY, 2014-09-23T11:56:24+02:00)

# 2.14.09.17

  * Continue Rüsh Release. (Ivan Enderlin, 2014-09-16T23:12:13+02:00)

(first snapshot)
