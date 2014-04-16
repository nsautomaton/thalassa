<?php
namespace Thalassa\EventDispatcher;
class Event{
      private $events;
	  
	  public function __construct()
	  {
	  $this->events = array();
	  }
	  
      public function on($route, array $method)
	  {
        if($this->verify($method))
	      {
		  $this->register($route, $method);
		  }else{
		  throw new \Exception("\nFailed to register event $route, invalid callback function provided\n");
		  }
	  }
	  
	  public function register($route, $method)
	  {
	  $this->events["$route"] = $method;
	  }
	  
      public function call($route, array $params)
	  {
	  if($this->is_registered($route))
	      {
		  $method = $this->route($route, $params);
		  call_user_func_array($method, $params);
		  }else{
		   trigger_error("Handler $route must be registered to be called\n", E_USER_ERROR);
		  }
	  }
	  
	  public function route($route, array $params)
	  {
	  $method = $this->events["$route"];
	  $n = count($params);
	     if($method[2] !== $n)
		 {
		 trigger_error("Handler $route has exactly $method[2] parameter(s), $n given\n", E_USER_ERROR);
		 }
	  array_pop($method);
	  return $method;
	  }
	  
	  public function unregister()
	  {
	  
	  }
	  
	  public function is_registered($route)
	  {
	    if(array_key_exists($route, $this->events))
		{
	    return true;
		}else{
		return false;
		}
	  }
	  
	  public function verify($method)
	  {
	  if(method_exists($method[0], $method[1]))
       {
	    return true;
	   }
	  }
}