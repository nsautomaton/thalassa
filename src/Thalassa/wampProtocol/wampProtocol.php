<?php
namespace Thalassa\wampProtocol;
use Thalassa\Rand\Chaos;
use Thalassa\wsProtocols\RFC6455\wsData;
use Thalassa\Broker\Channels;
use Thalassa\Dealer\Procedures;
use Thalassa\EventDispatcher\Event;
class wampProtocol extends wsData{
    const HELLO          =1;
    const WELCOME        =2;
    const ABORT          =3;
    const CHALLENGE      =4;
    const AUTHENTICATE   =5;
    const GOODBYE        =6;
    const HEARTBEAT      =7;
    const ERROR          =8;
    const PUBLISH       =16;
    const PUBLISHED     =17;
    const SUBSCRIBE     =32;
    const SUBSCRIBED    =33;
    const UNSUBSCRIBE   =34;
    const UNSUBSCRIBED  =35;
    const EVENT         =36;
    const CALL          =48;
    const CANCEL        =49;
    const RESULT        =50;
    const REGISTER      =64;
    const REGISTERED    =65;
    const UNREGISTER    =66;
    const INVOCATION    =68;
    const INTERRUPT     =69;
    const YIELD         =70;
	
	protected $sessionConnections;
	protected $sessionIDs;
	
	public function __construct(Event $event)
	{
	$this->spectrometer = new Channels($this);
	$this->rpc = new Procedures($this);
	$event->on('ghostDataCleanUp', array($this, 'route_data_cleanup', 1));
	$this->event = $event;
	$this->sessionConnections = array();
	$this->sessionIDs = array();
	}
	
	public function switchboard($conn, $data)
	{
	$pcap = $this->decode($data);
	  if($pcap === false || null || '')
	  {
	  $this->flag($conn);
	  }else{
	    foreach($pcap as $msg)
		{
	      $protocol =& $msg[0];
	      switch($protocol)
	      {
	        case self::HELLO:
		      $this->welcomeProtocol($conn, $data);
		      break;
		    case self::ABORT:
		      $this->abortProtocol($conn, $msg);
		      break;
		    case self::AUTHENTICATE:
			  //todo
		      break;
			case self::HEARTBEAT:
			  //todo
			  break;
		    case self::GOODBYE:
		      $this->goodbyeProtocol($conn, $msg);
		      break;
		    case self::PUBLISH:
		      $this->publishProtocol($conn, $msg);
		      break;
		    case self::SUBSCRIBE:
		      $this->subscribeProtocol($conn, $msg);
		      break;
		    case self::UNSUBSCRIBE:
		      $this->unsubscribeProtocol($conn, $msg);
		      break;
		    case self::CALL:
		      $this->callProtocol($conn, $msg);
		      break;
		    case self::CANCEL:
		      $this->cancelProtocol($conn, $msg);
		      break;
		    case self::REGISTER:
		      $this->registerProtocol($conn, $msg);
		      break;
		    case self::UNREGISTER:
		      $this->unregisterProtocol($conn, $msg);
		      break;
		    case self::YIELD:
		      $this->yieldProtocol($conn, $msg);
		      break;
			case self::ERROR:
			  if($msg[1] === self::INVOCATION)
			  {
			  $this->invocationErrorProtocol($conn, $msg);
			  }
		    default:
			//todo
			//var_dump($this->wsDecode($data));
			//var_dump($pcap);
		    //$this->invalid_message($conn, $msg);
	      }
		}
	   }
	}
	
	public function welcomeProtocol($conn, $data)
	{
	$sessionID = Chaos::keyGen();
	$welcomeMessage = $this->encode(
							 array(
							 self::WELCOME, $sessionID, array(
							 'agent'=>"Thalassa V_0.1.pre-alpha",
							 'roles'=>array(
	                           'broker'=>array(
									   'features'=>array(
										           "subscriber_blackwhite_listing"=> true,
                                                   "publisher_exclusion"=>           true,
                                                   "publisher_identification"=>      true,
                                                   "publication_trustlevels"=>       true,
                                                   "pattern_based_subscription"=>    true,
                                                   "partitioned_pubsub"=>            true,
                                                   "subscriber_metaevents"=>         true,
                                                   "subscriber_list"=>               true,
                                                   "event_history"=>                 true),
							    'dealer'=>array(
							           'features'=>array(
									                "callee_blackwhite_listing" =>true,
													"callee_exclusion"          =>true,
													"caller_identification"     =>true,
													"call_trustlevels"          =>true,
													"pattern_based_registration"=>true,
													"partitioned_rpc"           =>true,
													"call_timeout"              =>true,
													"call_cancelling"           =>true,
													"progressive_call_results"  =>true)
												   ))))));
	fwrite($conn, $welcomeMessage, strlen($welcomeMessage));
	$this->sessionConnections[$sessionID] = $conn;
	$this->sessionIDs[(int)$conn] = $sessionID; 
	$this->event->call('onSessionEstablish', array($conn, $sessionID));
	}
	
	public function & get_connections($sessionID)
	{
	  if(is_array($sessionID))
	  {
	  $results = array();
	    foreach($sessionID as $id)
		{
		$results[] = $this->sessionConnections[$id];
		}
	  return $results;
	  }else{
	  return $this->sessionConnections[$sessionID];
	  }
	}
	
	public function get_session_id($conn)
	{
	return $this->sessionIDs[(int)$conn];
	}
	
	public function challengeProtocol($data)
	{
	//
	//$event->call('onChallenge', array($conn, $msg));
	}
	
	public function abortProtocol($conn, $msg)
	{
	$this->flag($conn);
	}
	
	public function subscribeProtocol($conn, $msg)
	{
	$this->event->call('onSubscribeRequest', array($this->spectrometer, $conn, $msg[1], $msg[3]));
	}

	public function subscribedProtocol($requestID)
	{
	$subscriptionID = Chaos::keyGen();
	$msg = array(self::SUBSCRIBED, $requestID, $subscriptionID);
	$data = $this->encode($msg);
	return array($data, $subscriptionID);
	}
	
	public function unsubscribeProtocol($conn, $msg)
	{
	$channel = $this->spectrometer->get_channel($conn, $msg[2]);
	  if($channel === false)
	  {
	  $this->unsubscribeErrorProtocol($conn);
	  }else{
	    $this->event->call('unSubscribe', array($this->spectrometer, $conn, $channel, $msg[1]));
		}
	}
	
	public function & unsubscribedProtocol($requestID)
	{
	$msg = array(self::UNSUBSCRIBED, $requestID);
	$data = $this->encode($msg);
	return $data;
	}
	
	public function unsubscribeErrorProtocol($conn, $requestID)
	{
	$error_msg = array(self::ERROR, self::UNSUBSCRIBE, $requestID, (object)array(), "wamp.error.no_such_subscription");
	$data = $this->encode($error_msg);
	$this->spectrometer->send($conn, $data);
	}
	
	public function publishProtocol($conn, $msg)
	{
	$dict = (array)$msg[2];
	  if($dict)
	  {
	    if(isset($dict['acknowledge']))
		{
		$dict['acknowledge']===true ? $acknowledge = true : $acknowledge = false;
		}else{
		  $acknowledge = false;
		  }
	  isset($dict['exclude']) ? $exclude = $dict['exclude'] : $exclude = false;
	  isset($dict['eligible']) ? $eligibles = $dict['exclude'] : $eligibles = false;
	  (isset($dict['exclude_me']) && $dict['exclude_me'] === true) ? $notme = true : $notme = false;
	  (isset($dict['disclose_me']) && $dict['disclose_me'] === true) ? $identify = true : $identify = false;
	  }else{
	    $acknowledge = false;
	    $exclude = false;
	    $eligibles = false;
	    $notme = false;
	    $identify = false;
		}
	isset($msg[4]) ? $Arguments = $msg[4] : $Arguments = null;
	isset($msg[5]) ? $ArgumentsKw = $msg[5] : $ArgumentsKw = null;
	$this->event->call('Publish', array($this->spectrometer, $conn,
                                  array("requestID"=>$msg[1],
                                        "channel"=>$msg[3],
										"pubID"=>Chaos::keyGen(10),
                                        "payload"=>["Arguments"=>$Arguments, "ArgumentsKw"=>$ArgumentsKw]),
                                  array("acknowledge"=>$acknowledge,
								        "exclude"=>$exclude,
     								    "eligibles"=>$eligibles,
										"exclude_me"=>$notme,
										"disclose_me"=>$identify)));
	}
	
	public function publishedProtocol($requestID, $pubID)
	{
	return $this->encode([self::PUBLISHED, $requestID, $pubID]);
	}
	
	public function publishErrorProtocol($requestID, $reason = null)
	{
	$reason === null ? $reason = 'wamp.error.invalid_topic' : $reason;
	return $this->encode([self::ERROR, self::PUBLISH, $requestID, (object)[], $reason]);
	}
	
	public function eventProtocol(array $msg)
	{
	array_unshift($msg, self::EVENT);
	$data = $this->encode($msg);
	return $data;
	}
	
	public function goodbyeProtocol($conn, $msg)
	{
	$reply = [self::GOODBYE, (object)[], 'wamp.error.goodbye_and_out'];
	fwrite($conn, $reply, strlen($reply));
	$this->flag($conn);
	}
	
	public function registerProtocol($conn, $msg)
	{
	$args = (array)$msg[2];
	  if($args)
	  {
	  isset($args['match']) ? $match = $args['match'] : $match = false;
	  }else{
	    $match = false;
		}
	$options = array();
	$this->event->call('Register', array($this->rpc, $conn, $msg[3], $msg[1], array('match'=>$match)));
	}
	
	public function registeredProtocol($requestID)
	{
	$id = Chaos::keyGen();
	$data = array(self::REGISTERED, $requestID, $id);
	return [$this->encode($data), $id];
	}
	
	public function registerErrorProtocol($requestID)
	{
	return $this->encode([self::ERROR, self::REGISTER, $requestID, (object)[], 'wamp.error.procedure_already_exists']);
	}
	
	public function unregisterProtocol($conn, $msg)
	{
	$this->event->call('Unregister', array($this->rpc, $conn, $msg[2], $msg[1]));
	}
	
	public function unregisteredProtocol($requestID)
	{
	return $this->encode([self::UNREGISTERED, $requestID]);
	}
	
	public function unregisterErrorProtocol($requestID)
	{
	return $this->encode([self::ERROR, self::UNREGISTER, $requestID, (object)[], 'wamp.error.no_such_registration']);
	}
	
	public function callProtocol($conn, $msg)
	{
	isset($msg[4]) ? $Arguments = $msg[4] : $Arguments = null;
	isset($msg[4]) ? $ArgumentsKw = $msg[5] : $ArgumentsKw = null;
	$this->event->call('Call', array($this->rpc, $conn, $msg[3], ['requestID'=>$msg[1], 'Arguments'=>$Arguments, 'ArgumentsKw'=>$ArgumentsKw], (array)$msg[2]));
	}
	
	public function callErrorProtocol($requestID, $errormsg)
	{
	  if(is_array($errormsg))/*means it's an invocation error*/
	  {
	  return $this->encode([self::ERROR, self::CALL, $requestID, $errormsg['Details'], $errormsg['Error'], $errormsg['Arguments'], $errormsg['ArgumentsKw']]);
	  }
	$errormsg === null ? $errormsg = 'wamp.error.no_such_procedure' : $errormsg;
	return $this->encode([self::ERROR, self::CALL, $requestID, null, $errormsg]);
	}
	
	public function cancelProtocol($conn, $msg)
	{
	$this->rpc->interrupt($conn, $msg[1]);
	}
	
	public function interruptProtocol($invocationID)
	{
	return $this->encode([self::INTERRUPT, $invocationID, null]);
	}
	
	public function invokeProtocol($invocutionID, $registrationID, $Arguments, $ArgumentsKw)
	{
	return $this->encode([self::INVOCATION, $invocutionID, $registrationID, (object)[], $Arguments, $ArgumentsKw]);
	}
	
	public function yieldProtocol($conn, $msg)
	{
	isset($msg[3]) ? $Arguments = $msg[3] : $Arguments = null;
	isset($msg[4]) ? $ArgumentsKw = $msg[4] : $ArgumentsKw = null;
	$this->rpc->handle_call_results($conn, $msg[1], ['Options'=>$msg[2], 'Arguments'=>$Arguments, 'ArgumentsKw'=>$ArgumentsKw]);
	}
	
	public function invocationErrorProtocol($conn, $msg)
	{
	isset($msg[3]) ? $details = $msg[3] : $details = null;
	isset($msg[4]) ? $error = $msg[4] : $error = null;
	isset($msg[5]) ? $arguments = $msg[5] : $arguments = null;
	isset($msg[6]) ? $argumentskw = $msg[6] : $argumentskw = null;
	$this->rpc->handle_call_results($conn, $msg[2], ['Details'=>$details, 'Error'=>$error, 'Arguments'=>$arguments, 'ArgumentsKw'=>$argumentskw], /*$flag=*/'invocation_error');
	}
	
	public function resultProtocol($requestID, $Arguments, $ArgumentsKw)
	{
	return $this->encode([self::RESULT, $requestID, (object)[], $Arguments, $ArgumentsKw]);
	}
	
	public function decode($data)
	{
	$msgarray = array();
	$msg = $this->wsDecode($data);
	  if($msg === false || null || '' || 0)
	  {
	  return false;
	  }
	  foreach($msg as $current)
	  {
	  $unjained = json_decode($current);
	    if($unjained === false || null || '' || 0)
	    {
	    return false;
	    }
	  $msgarray[] = $unjained;
	  }
	return $msgarray;
	}
	
	public function encode($data)
	{
	$msg = json_encode($data);
	$msg = $this->wsEncode($msg);
	return $msg;
	}

	public function flag($conn)
	{
	$close_frame = $this->close();
	fwrite($conn, $close_frame, strlen($close_frame));
	stream_socket_shutdown($conn, STREAM_SHUT_RDWR);
	$this->event->call('ghostDataCleanUp', array($conn));
	}
	
	public function route_data_cleanup($conn)
	{
	$this->spectrometer->clear_ghost_data($conn);
	$this->rpc->clear_ghost_data($conn);
	}
}