<?
namespace Thalassa\UI;
class systemLoad{
     public static function getcpu()
	 {
	    if(windows)
		{
		$percentage = shell_exec('wmic cpu get loadpercentage');
	    return $percentage;
		}else if(linux)
		  {
		  $cmd = shell_exec('free');
		  $cmd = (string)trim($free)
		  }else(unsupported)
		    {
			return 'unknown';
			}
	 }
	 
	 public static function getram()
	 {
	    if(windows)
		{
		$free = shell_exec('wmic os getfreephysicalmemory');
	    $total = shell_exec('wmic os totalvisisblememorysize');
	    $percent = $free/$total * 100;
	    return $percentage;
		}else if(linux)
		   {
		   
		   }else(unsupported)
		     {
			 return 'unknown';
			 }
	 }
}
?>