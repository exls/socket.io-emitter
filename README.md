exls/socket.io-emitter
=====================

A PHP implementation of socket.io-emitter (0.1.0).

## Installation

composer require exls/socket.io-emitter

## Usage

### Emit payload message
```php
use Predis;
use Exls\SocketIO;
...

$client = new Predis\Client();

(new Emitter($client))
    ->of('namespace')->emit('event', 'payload message');
```

### Flags
Possible flags
* json
* volatile
* broadcast

#### To use flags, just call it like in example bellow
```php
use Predis;
use Exls\SocketIO;
...

$client = new Predis\Client();

(new Emitter($client))
    ->broadcast->emit('broadcast-event', 'payload message');
```

### Emit an object
```php
use Predis;
use Exls\SocketIO;
...

$client = new Predis\Client();

(new Emitter($client))
    ->emit('broadcast-event', ['param1' => 'value1', 'param2' => 'value2', ]);
```