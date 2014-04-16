<?php
namespace Components;
use Components\Piston;
class pool{
    public $workers;
	
	public function __construct($n)
	{
	$n == null ? $this->size = 4 : $this->size = $n;
	$this->workers = array();
	$this->i = 0;
	}
	
	public function spawn()
	{
	$i = 0;
      while($i++ < $this->size)
	  {
	  $thread = new Piston;
	  $this->workers[$i] =& $thread;
	  $thread->start();
	  }
	}
	
	public function getThread()
	{
	//$selected = array_rand(array_flip($this->workers));
	$selected = $this->workers[2];
	return $selected;
	}
}