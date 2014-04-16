<?php
namespace Thalassa\UI;
  /*
  most of the styling is not used,
  it is included so one could easily customize
  console appearance to their liking, and for reference.
  */
class console{
     public $esc;
     public $white;
	 public $bgwhite;
     public $yellow;	 
     public $green;	 
     public $blue;	 
     public $black;	 
     public $red;	 
     public $magenta;	 
     public $cyan;	 
     public $purple;
     public $brown;
     public $lgray;
     public $dgray;
     public $lblue;
     public $lgreen;
     public $lcyan;
     public $lred;
     public $lpurple;
     public $lyellow;
	 public $bgcyan;
	 public $bgmagenta;
	 public $bgblue;
	 public $bgyellow;
	 public $bggreen;
	 public $bgred;
	 public $bgblack;
	 public $bold;
	 public $ul;
	 public $italics;
	 public $blink;
	 public $strike;
	 public $reset;
	 public $cls;
	 public $home;
	 public $inverse;
	 
     public function __construct()
     {
	 $this->esc = chr(27);
	 $this->cls = '[2j';
	 $this->inverse = '7m';
     $this->white = '[37m';
     $this->lwhite = '[1;37m';
	 $this->bgwhite = '[47m';
	 $this->bgcyan = '[46m';
	 $this->bgmagenta = '[45m';
	 $this->bgblue = '[44m';
	 $this->bgyellow = '[43m';
	 $this->bggreen = '[42m';
	 $this->bgred = '[41m';
	 $this->bgblkack = '[40m';
     $this->yellow = '[33m';
     $this->lyellow = '[1;33m';
     $this->lgreen = '[1;32m';
     $this->green = '[32m';
     $this->blue = '[34m';
     $this->lblue = '[1;34m';
     $this->black = '[30m';
     $this->red = '[31m';
     $this->lred = '[1;31m';
     $this->magenta = '[35m';
     $this->lmagenta = '[1;35m';
     $this->cyan = '[36m';
     $this->lcyan = '[1;36m';
     $this->bold = '[1m';
     $this->ul = '[4m';
	 $this->blink = '[5m';
	 $this->italics = '[3m';
	 $this->strike = '[9m';
	 $this->reset = '[0m';
	 $this->home = '[;H';
     }	

	 public function etch($clientCount)
	 {
	 //cli_set_process_title('THALASSA V 1.0');
	 // $gui = str_repeat("-", 37);
	 // echo "\n\tWelcome to the Thalassa server console.\n\tAny startup warnings or error messages should appear here.\n\tPress Enter to continue";
	 // $input = fgets(STDIN);
	     // if($input)
	      // {
	      // $nothing = str_repeat(" ", 2000);
	      // echo $this->esc . $this->home . $nothing.$this->esc .$this->home;
	      // echo "\t\t+". $this->esc. $this->lcyan .$gui. "$this->esc$this->white+\n\t\t| THE ENGINE HAS STARTED SUCCESSFULLY |\n\t\t+". $this->esc. $this->lcyan .$gui. "+\n";
          // echo "\t+". str_repeat("-", 50). "\n";
          // echo "\t| ACTIVE CONNECTIONS\t| $clientCount |\n";
	      // echo "\t+". str_repeat("-", 50). "\n";
	      // echo "\t$this->esc$this->lgreen| PERFOMANCE\t| 10 |\n";
	      // echo "\t+". str_repeat("-", 50). "\n";		  
		  // }

	 }
	 
     public function log($type, $status)
	 {
	  switch($type)
	  {
	     case 'clientCount':
		   $this->update_clientCount($status);
		   break;
		 case 'performance':
		   $this->update_Perfomance($status);
		   break;
		 case 'benchmark':
		   $this->update_Benchmark($status);
		   break;
		 case 'flags':
		   $this->update_Flags($status);
		   break;
		 default:
		   $this->print_Unknown();
	  }
	  
	 }
	 
	 public function coord($row, $col)
	 {
	 $coordinates = '['. $row. ';'. $col. 'H ';
	 return $coordinates;
	 }
	 
	 public function update_clientCount($status)
	 {
	 $etching = $this->esc .$this->coord(5, 34).$this->esc .$this->yellow .$status;
	 echo $etching;
	 }
	 
	 public function update_Performance($status)
	 {
	 
	 }
	 
	 public function update_Benchmark($status)
	 {
	 
	 }
	 
	 public function update_Flags($status)
	 {
	 
	 }
	 
	 public function print_Unknown($info)
	 {
	 
	 }
}