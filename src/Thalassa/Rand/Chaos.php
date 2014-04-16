<?php
namespace Thalassa\Rand;
  /*
  this class generates random keys,
  one could use a better lib/implementation
  of course, just that I love playing around with
  chaos :)
  */
class Chaos{
    public static function keyGen($length = null)
	{
	$chaos = array('a','A','b','c','C','d','D','e','E','f',
				   '0','!','@','#','$','%','^','&','*','_',
	               'F','g','G','h','H','j','J','i','I','k',
				   'P','q','Q','r','R','s','S','t','T','u',
				   '-','+','=','.','/','?','~','`','[',']',
				   'K','l','L','m','M','n','N','o','O','p',
				   '|',',','<','>',')','(','U','v','V','w',
				   'W','x','X','y','Y','z','Z','1','2','3',
				   '4','5','6','7','8','9',"\\");
	 $key='';
	 $length === null ? $lng = 30 : $lng =& $length;
		while(strlen($key) < $lng)
		{
	    $key .= array_rand(array_flip($chaos));
		shuffle($chaos);
		str_shuffle($key);
		}
	 return $key;
	}
}
?>