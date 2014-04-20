<?php
namespace Thalassa\Broker;
use Thalassa\wampProtocol\wampProtocol;
class Telescope{
    public function __construct(wampProtocol $protocol)
	{
	$this->protocol = $protocol;
	}
    public function broadcast(channelsInterface $chann, $me, array $data, array $options)
	{
	  if(!$chann->exists($data['channel']))
	  {
	    if($options['acknowledge'] === true)
	    {
	    $feedback = $this->protocol->publishErrorProtocol($data['requestID']);
	    fwrite($me, $feedback, strlen($feedback));
	    }
	  return null;
	  }
	  if($options['eligibles'])
	  {
	  $subscribers =& $this->protocol->get_connections($options['eligibles']);
	  }else{
	    $subscribers =& $chann->get_subscribers($data['channel']);
	      if($subscribers === false)
	        {
	        return false;
	        }
	    }
	$options['disclose_me'] === true ? $kwargs = array('topic'=>$data['channel'], 'publisher'=>$this->protocol->get_session_id($me)) : $kwargs = array('topic'=>$data['channel']);
	count($subscribers) < 10 ? $connections =& $subscribers : $connections = new \ArrayIterator($subscribers);
	$options['exclude'] ? $exclude_list = $this->protocol->get_connections($options['exclude']) : $exclude_list = false;
	  foreach($connections as $conn)
	  {
	    if($exclude_list)
		{
		  if(array_search($conn, $exclude_list) !== false)
		  {
		  continue;
		  }
		}
        if($options['exclude_me'] === true && $conn == $me)
		{
		continue;
		}
	  $id = $chann->get_subscription_id($conn, $data['channel']);
	  $raw_data = $this->protocol->eventProtocol(array($id, $data['pubID'], $kwargs, $data['payload']['Arguments'], $data['payload']['ArgumentsKw']));
	  fwrite($conn, $raw_data, strlen($raw_data));
      }
	  //breath ;)
	  if($options['acknowledge'] === true)
	  {
	  $feedback = $this->protocol->publishedProtocol($data['requestID'], $data['pubID']);
	  fwrite($me, $feedback, strlen($feedback));
	  }
	}
	
	public function send($conn, $data)
	{
	fwrite($conn, $data, strlen($data));
	}
}