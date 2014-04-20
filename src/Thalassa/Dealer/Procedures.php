<?php
namespace Thalassa\Dealer;
use Thalassa\Rand\Chaos;
class Procedures implements proceduresInterface{
    private $calls;
	private $regIDs;
	private $pending;
    public function __construct($protocol)
	{
	$this->calls = array();
	$this->authorized = array();
	$this->protocol = $protocol;
	$this->pending = [];
	}
	
	public function add($conn, $call, $options, $requestID)
	{
	//todo:add support for pattern-based registration, and other features in the advanced spec
	  if(isset($this->calls[$call]))
	  {
	  $this->send_register_error($conn, $requestID);
	  return false;
	  }
	$reply = $this->protocol->registeredProtocol($requestID);
	$this->calls[$call] = ['connection'=>$conn, 'regid'=>$reply[1]];
	$this->regIDs[$reply[1]] = $call;
	$this->associated[(int)$conn] = $call;
	fwrite($conn, $reply[0], strlen($reply[0]));
	}
	
	public function delete($conn, $regID, $requestID)
	{
	  if(isset($this->regIDs[$regID]))
	  {
	  $call =& $this->regIDs[$regID];
	  unset($this->calls[$call]);
	  unset($this->regIDs[$regID]);
	  return true;
	  }else{
	    $this->send_unregister_error($conn, $requestID);
		return false;
		}
	}
	
	public function send_register_error($conn, $requestID)
	{
	$reply = $this->protocol->registerErrorProtocol($requestID);
	fwrite($conn, $reply, strlen($reply));
	}
	
	public function send_unregister_error($conn, $requestID)
	{
	$reply = $this->protocol->unregisterErrorProtocol($requestID);
	fwrite($conn, $reply, $strlen($reply));
	}
	
	public function execute($caller, $call, array $args, array $options)
	{
	$requestID =& $args['requestID'];
	  if(!isset($this->calls[$call]))
	  {
	  $this->send_call_error($caller, $requestID);
	  return false;
	  }
	$invocutionID = Chaos::keyGen(20);
	$this->pending[$invocutionID] = ['callee'=>$this->calls[$call]['connection'], 'caller'=>$caller, 'requestID'=>$requestID, 'options'=>$options, 'call'=>$call];
	$this->invoke($call, $invocutionID, $args['Arguments'], $args['ArgumentsKw']);
	return true;
	}
	
	public function send_call_error($conn, $requestID, $errormsg = null)
	{
	$reply = $this->protocol->callErrorProtocol($requestID, $errormsg);
	fwrite($conn, $reply, strlen($reply));
	}
	
	public function invoke($call, $invocutionID, $Arguments, $ArgumentsKw)
	{
	$callee =& $this->calls[$call]['connection'];
	$query = $this->protocol->invokeProtocol($invocutionID, $this->calls[$call]['regid'], $Arguments, $ArgumentsKw);
	fwrite($callee, $query, strlen($query));
	}
	
	public function handle_call_results($conn, $invocutionID, array $args, $flag = null)
	{
	  if(!isset($this->pending[$invocutionID]) || $this->pending[$invocutionID]['callee'] !== $conn)
	  {
	  /*CALL ONFLAG*/
	  }else if($flag === 'invocation_error')
	    {
		$this->send_call_error($conn, $this->pending[$invocutionID]['requestID'], $args);
		}else{
	      $msg = $this->protocol->resultProtocol($this->pending[$invocutionID]['requestID'], $args['Arguments'], $args['ArgumentsKw']);
		  fwrite($this->pending[$invocutionID]['caller'], $msg, strlen($msg));
		  unset($this->pending[$invocutionID]);
		  }
	}
	
	public function interrupt($conn, $requestID)
	{
	$hold = array_search($requestID, $this->pending);
	  if($hold && $this->pending[$hold]['caller'] === $conn)
	  {
	  $msg = $this->protocol->interruptProtocol($this->pending[$hold]['requestID']);
	  fwrite($conn, $msg, strlen($msg));
	  unset($this->pending[$hold]);
	  }
	}
	
	public function clear_ghost_data($conn)
	{
	$call =& $this->associated[(int)$conn];
	  while(array_search($conn, $this->pending))
	  {
	  $hold = array_search($conn, $this->pending);
	    if($hold && $this->pending[$hold]['call'] === $call)
	    {
	    $this->send_call_error($conn, $this->pending[$hold]['requestID'], 'wamp.error.connection_to_remote_procedure_endpoint_lost');
	    unset($this->pending[$hold]);
	    }
	  }
	unset($this->calls[$call]);
	unset($call);
	}
}