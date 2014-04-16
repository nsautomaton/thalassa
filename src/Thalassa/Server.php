<?php
namespace Thalassa;
use Thalassa\dispatch as eV;
use Thalassa\EventDispatcher\Event;
//use Thalassa\Piston;
//use Thalassa\Task;
//use Thalassa\pool;
final class Server{
    const EVBUFFER_READ = 2;
    const EVBUFFER_WRITE = 3;
    const EVBUFFER_EOF = 17;
    const EVBUFFER_ERROR = 33;
    const EVBUFFER_TIMEOUT = 65;

    public $clientCount;
	protected $connections;
	protected $buffers;
	public $host;
    public $port;
	public $event;
	public $ev_base;
	public $unauthorized;
	public $close;
	protected $pool;
	protected $i;
	private $fds;
	
    public function __construct($host = '127.0.0.1', $port = 9000)
	{
	ini_set('precision', 50);
	$this->host = $host;
	$this->port = $port;
	$this->clientCount=0;
	$this->connections=array();
	$this->buffers = array();
	$this->unauthorised = array();
	$this->pool = array();
	$this->i = 0;
	$this->buffer_id = 0;
	$this->event = new Event;
	$this->ev_base = event_base_new();
	$this->fds = [];
	}
	
	public function feature_support($feature, $support)
	{
	//todo
	}
	
	public function listen($host, $port, $onConnection, $onData, $onError)
	{
	$server = stream_socket_server("tcp://$host:$port", $errno, $errstr);
	$this->fds[] = $server;
	$this->eventcb[(int)$server] = [$this, 'ipc_accept'];
	$this->ipc_callbacks = [$onConnection, $onData, $onError];
	}
	
    public function Build($app, $threads = 2)
	{
	$setup = new eV($this->event);
	$setup->install($app);
	$socket = stream_socket_server("tcp://$this->host:$this->port", $errno, $errstr);
	$this->fds[] = $socket;
	$this->eventcb[(int)$socket] = [$this, 'acceptcb'];
    //$this->etch($this->clientCount);
	}

    public function start()
	{
	$n = 0;
	  foreach($this->fds as $fd)
	  {
	  ++$n;
	  $event = 'event#'.$n;
	  stream_set_blocking($fd, 0);
	  $event = event_new();
	  event_set($event, $fd, EV_READ|EV_PERSIST, $this->eventcb[(int)$fd], $this->ev_base);
	  event_base_set($event, $this->ev_base);
	  event_add($event);
	  $this->events[] = $event;
	  }
	$this->run();
	}
	
	public function run()
	{
	event_base_loop($this->ev_base);
	}
	
	private function acceptcb($conn, $flag, $base)
    {
	$new = stream_socket_accept($conn);
	  if($new)
	  {
	  stream_set_blocking($new, 0);
      $this->initiate_handshake($new);
	  //$this->log('clientCount', $this->clientCount);
	  }
	}
	
	private function ipc_accept($conn, $flag, $base)
    {
	$new = stream_socket_accept($conn);
	call_user_func_array($this->ipc_callbacks[0], [$conn]);	
	  if($new)
	  {
	  stream_set_blocking($new, 0);
      $buffer = event_buffer_new($new, $this->ipc_callbacks[1], null, $this->ipc_callbacks[2], $new);
      event_buffer_base_set($buffer, $this->ev_base);
      event_buffer_enable($buffer, EV_READ|EV_PERSIST);
	  $this->ipc_connections[(int)$new] = $new;
	  $this->ipc_buffers[(int)$new] = $buffer;
	  }
	}
	
	private function initiate_handshake($conn)
	{
    $buffer = event_buffer_new($conn, [$this, 'attempt_handshake'], null, [$this, 'errorcb'], $conn);
    event_buffer_base_set($buffer, $this->ev_base);
    event_buffer_timeout_set($buffer, 10, null);
    event_buffer_watermark_set($buffer, EV_READ, 100, 1024);
    event_buffer_priority_set($buffer, 1);
    event_buffer_enable($buffer, EVLOOP_ONCE|EV_READ);
	$this->connections[(int)$conn] = $conn;
	$this->unauthorized[(int)$conn] = $buffer;
	}
	
	private function attempt_handshake($buffer, $conn)
	{
	$data = event_buffer_read($buffer, 1024);
	$lines = preg_split("/\r\n/", $data);
	$headers = array();
	  foreach($lines as $line)
	  {
	  $line = chop($line);
	    if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
		$headers[$matches[1]] = $matches[2];
		}
	  }
	  if(isset($headers['Sec-WebSocket-Key']))
	  {
	  $host =& $this->host;
	  $port =& $this->port;
	  $secKey = $headers['Sec-WebSocket-Key'];
	  $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	  $upgrade  = "HTTP/1.1 101 Switching Protocols\r\n".
	              "Server: Thalassa V-1.0\r\n".
	              "Upgrade: websocket\r\n".
	              "Connection: Upgrade\r\n".
	              "WebSocket-Origin: $host\r\n".
	              "WebSocket-Location: ws://$host:$port\r\n".
				  "Sec-Websocket-Protocol: wamp.2.json\r\n".
	              "Sec-WebSocket-Accept: $secAccept\r\n\r\n";				
	    if(event_buffer_write($buffer, $upgrade))
	    {
	    $this->monitor($conn, $buffer);
	    }else{
	       $this->abort_handshake($conn);
	       }
	  }else{
	    $this->abort_handshake($conn);
		}
	}
	
	private function monitor($conn, $buffer)
	{
    event_buffer_set_callback($buffer, [$this, 'readcb'], null, [$this, 'errorcb'], $conn);
    event_buffer_timeout_set($buffer, 1200, 1200);
    event_buffer_watermark_set($buffer, EV_READ, 1, null);
    event_buffer_enable($buffer, EV_READ | EV_PERSIST | EVLOOP_NONBLOCK);
	unset($this->unauthorized[(int)$conn]);
	$this->buffers[(int)$conn] = $buffer;
	}

	private function readcb($buffer, $conn)
    {
    $data = event_buffer_read($buffer, 1024 * 10);
	$this->event->call('onData', array($conn, $data));
	}
	
	private function errorcb($buffer, $error, $conn)
	{
	  if(feof($conn) === true || fgetc($conn) === false)
	  {
	    if(isset($this->unauthorized[(int)$conn]))
		{
		$this->abort_handshake($conn);
		return;
		}
	  $this->event->call('Close', array($conn));
	  $this->event->call('ghostDataCleanUp', array($conn));
	  return;
	  }
      switch($error)
	  {
	    case self::EVBUFFER_READ:
		  $details = "Unknown event during a read operation";
		  break;
		case self::EVBUFFER_WRITE:
		  $details = "Unknown event during a write operation";
		  break;
		case self::EVBUFFER_EOF:
		  $this->event->call('Close', array($conn));
	      $this->event->call('ghostDataCleanUp', array($conn));
		  return;
		case self::EVBUFFER_ERROR:
		  $details = "Unknown error";
		  break;
		case self::EVBUFFER_TIMEOUT:
		  if(isset($this->unauthorized[(int)$conn]))
		  {
		  $this->abort_handshake($conn);
		  return;
		  }
		  $details = "Socket activity time-out";
		  break;
	  }
	$this->event->call('Error', array($conn, $details));
	}
	
	private function abort_handshake($conn)
	{
	event_buffer_free($this->unauthorized[(int)$conn]);
    stream_socket_shutdown($conn, STREAM_SHUT_RDWR);
    unset($this->unauthorized[(int)$conn]);
    unset($this->connections[(int)$conn]);
	$this->clientCount--;
	//$this->log('clientCount', $this->clientCount);
	}
	
	private function get_ratio()
	{
	  if(count($this->pool) == 0)
	  {
	  return 0;
	  }
	$ratio = $this->clientCount/count($this->pool);
	return $ratio;
	}
	
	private function getThread()
	{
	$selected = array_rand($this->workers);
	return $selected;
	}
	
	private function spawnThread()
	{
	$this->i !== 0 ? $n=$this->i++ : $n=$this->i;
	$spawned = new Piston;
	$this->pool = $spawned;
	$spawned->start();
	var_dump($this->pool);
	return $spawned;
	}
	
	
	public function get_associated_connection($buffer)
	{
	array_search();
	return $conn;
	}
	
	public function get_associated_buffer($conn)
	{
	array_search();
	return $buffer;
	}
	
	private function flag($conn)
	{
	//todo
	}
}