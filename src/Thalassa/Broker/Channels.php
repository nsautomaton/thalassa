<?php
namespace Thalassa\Broker;
use Thalassa\wampProtocol\wampProtocol;
class Channels extends Telescope implements channelsInterface{
    private $spectrum;
	private $creator;
	protected $protocol;
	protected $subscriptionIDs;
    public function __construct(wampProtocol $protocol)
	{
	parent::__construct($protocol);
	$this->protocol = $protocol;
	$this->spectrum = array();
	$this->subscriptionIDs = array();
	}
	
    public function create($channel, $creator)
	{
	$this->creator[$channel] =& $creator;
	$this->spectrum[$channel] = array();
	$this->spectrum[$channel][(int)$creator] = $creator;
	}
	
	public function destroy($channel)
	{
	
	}
	
	public function add($conn, $channel, $requestID)
	{
	!isset($this->spectrum[$channel]) ? $this->create($channel, $conn) : $this->spectrum[$channel][(int)$conn] = $conn;
	$replyData = $this->protocol->subscribedProtocol($requestID);
	$channel_list =& $this->subscriptionIDs[$channel];
	$channel_list[$replyData[1]] = $conn;
	$this->send($conn, $replyData[0]);
	}
	
	public function get_subscription_id($conn, $channel)
	{
	$id = array_search($conn, $this->subscriptionIDs[$channel]);
	return $id;
	}
	
	public function get_channel($conn, $subscriptionID)
	{
	  foreach($this->subscriptionIDs as $key=>$value)
	  {
	    if(isset($value[$subscriptionID]))
		{
	      if($value[$subscriptionID] == $conn)
		  {
		  return $key;
		  }
		}
	  }
	return false;
	}
	
	public function clear_ghost_data($conn)
	{
	$bool_or_array = $this->is_subscribed($conn);
	  if($bool_or_array !== false)
	  {
	    foreach($bool_or_array as $key => $value)
		{
		unset($this->spectrum[$value][$key]);
		}
	  }
	}
	
	public function is_subscribed($conn, $channel = null)
	{
	  if($channel === null)
	  {
	  $results = array();
	    foreach($this->spectrum as $key=>$value)
		{
		isset($value[(int)$conn]) ? $find = (int)$conn : $find = false;
		  if($find !== false)
		  {
		  $results[$find] = $key;
		  }
		}
      return (bool)$results === false ? false : $results;
	  }else{
		return isset($this->spectrum[$channel][(int)$conn]) ? (int)$conn : false;
		}
	}
	
	public function delete($conn, $channel, $requestID)
	{
	$find = $this->is_subscribed($conn, $channel);
	  if($find)
	  {
	  unset($this->spectrum[$channel][$find]);
	  }
	$data =& $this->protocol->unsubscribedProtocol($requestID);
	$this->send($conn, $data);
	}
	
	public function count_subscribers($channel)
	{
	return isset($this->spectrum[$channel]) ? count($this->spectrum[$channel]) : null;
	}
	
	public function & get_subscribers($channel)
	{
	  if(!isset($this->spectrum[$channel]))
	  {
	  $x = false;
	  return $x;
	  }
	return $subscribers =& $this->spectrum[$channel];
	}
	
	public function exists($channel)
	{
	return isset($this->spectrum[$channel]) ? true : false;
	}
	
	public function who_is_creator($channel)
	{
	$creator =& $this->creator[$channel];
	return $creator;
	}
}