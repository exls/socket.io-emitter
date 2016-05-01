<?php
/**
 * @author Anton Pavlov <anton.pavlov.it@gmail.com>
 * @license MIT
 */
namespace Exls\SocketIOEmitter;

use Exls\SocketIOEmitter\Constants\Emitter\Type;
use MessagePack\Packer;
use Predis;

/**
 * Class Emitter
 * @package Exls\SocketIOEmitter
 */
class Emitter
{
    /**
     * Default namespace
     */
    const DEFAULT_NAMESPACE = '/';

    /**
     * @var string
     */
    protected $uid = 'emitter';

    /**
     * @var int
     */
    protected $type;
    /**
     * @var string
     */
    protected $prefix;
    /**
     * Rooms
     * @var array
     */
    protected $rooms;
    /**
     * @var array
     */
    protected $flags;
    /**
     * @var Packer
     */
    protected $packer;
    /**
     * @var Predis\Client
     */
    protected $client;
    /**
     * @var string
     */
    protected $namespace;
    /**
     * Emitter constructor.
     * @param Predis\Client $client
     * @param string $prefix
     */
    function __construct(Predis\Client $client, $prefix = 'socket.io') {
        $this->client = $client;
        $this->prefix = $prefix;
        $this->packer = new Packer();
        $this->reset();
    }
    /*
     * Set room
     */
    public function in($room) {
        //multiple
        if (is_array($room)) {
            foreach ($room as $r) {
                $this->in($r);
            }
            return $this;
        }
        //single
        if (!in_array($room, $this->rooms)) {
            array_push($this->rooms, $room);
        }
        return $this;
    }

    // Alias for in
    public function to($room) {
        return $this->in($room);
    }

    /**
     * Set a namespace
     *
     * @param $namespace
     * @return $this
     */
    public function of($namespace) {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Set flags
     *
     * @param $flag
     * @return $this
     */
    public function __get($flag) {
        $this->flags[$flag] = TRUE;
        return $this;
    }

    /**
     * Set type
     * @param int $type
     * @return $this
     */
    public function type($type = Type::REGULAR_EVENT) {
        $this->type = $type;
        return $this;
    }

    /*
     * Emitting
     */
    public function emit() {
        $packet = [
            'type'  => $this->type,
            'data'  => func_get_args(),
            'nsp'   => $this->namespace,
        ];

        $options = [
            'rooms' => $this->rooms,
            'flags' => $this->flags
        ];
        $channelName = sprintf('%s#%s#', $this->prefix, $packet['nsp']);

        $message = $this->packer->pack([$this->uid, $packet, $options]);

        // hack buffer extensions for msgpack with binary
        if ($this->type === Type::BINARY_EVENT) {
            $message = str_replace(pack('c', 0xda), pack('c', 0xd8), $message);
            $message = str_replace(pack('c', 0xdb), pack('c', 0xd9), $message);
        }

        // publish
        if (is_array($this->rooms) && count($this->rooms) > 0) {
            foreach ($this->rooms as $room) {
                $chnRoom = $channelName . $room . '#';
                $this->client->publish($chnRoom, $message);
            }
        } else {
            $this->client->publish($channelName, $message);
        }

        // reset state
        return $this->reset();
    }

    /**
     * Reset all values
     */
    protected function reset()
    {
        $this->rooms = [];
        $this->flags = [];
        $this->namespace = self::DEFAULT_NAMESPACE;
        $this->type = Type::REGULAR_EVENT;
        return $this;
    }
}