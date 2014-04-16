<?php
namespace Thalassa\wsProtocols\RFC6455;
  /*
  RFC6455:
  
   0                   1                   2                   3
   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
  +-+-+-+-+-------+-+-------------+-------------------------------+
  |F|R|R|R|opcode |M| Payload len |    Extended payload length    |
  |I|S|S|S|  (4)  |A|     (7)     |            16/64              |
  |N|V|V|V|       |S|             |  (if payload len==126/127)    |
  | |1|2|3|       |K|             |                               |
  +-+-+-+-+-------+-+-------------+ - - - - - - - - - - - - - - - +
  |   Extended payload length  continued, if payload len == 127   |
  + - - - - - - - - - - - - - - - + - - - - - - - - - - - - - - - +
  |                               | Masking-key, if MASK set to 1 |
  + - - - - - - - - - - - - - - - + - - - - - - - - - - - - - - - +
  | Masking-key(continued)        |         Payload Data          |
  + - - - - - - - - - - - - - - - + - - - - - - - - - - - - - - - +
  :                  Payload data continued...                    :
  + - - - - - - - - - - - - - - - + - - - - - - - - - - - - - - - +
  |                  Payload data continued...                    |
  + - - - - - - - - - - - - - - - + - - - - - - - - - - - - - - - +
  
  + - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - +
  |             from raw binary to base16 to base10               |
  + - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - +
  |           opcode              |        first byte & 15        |
  + - - - - - - - - - - - - - - - + - - - - - - - - - - - - - - - +
  |           MASK                |        second byte & 128      |
  + - - - - - - - - - - - - - - - + - - - - - - - - - - - - - - - +
  |        payload len            |        second byte & 127      +
  + - - - - - - - - - - - - - - - + - - - - - - - - - - - - - - - +
  |     extended payload len      |     third byte.fourth byte    +
  + - - - - - - - - - - - - - - - + - - - - - - - - - - - - - - - +
  | extended payload len continued|         byte5-byte13          |
  + - - - - - - - - - - - - - - - + - - - - - - - - - - - - - - - +
  
  */
class wsData extends console{
     const FRAME_CONTINUATION     = 0x0;
     const FRAME_TEXT             = 0x1;
     const FRAME_BINARY           = 0x2;
     const FRAME_CONNECTION_CLOSE = 0x8;
     const FRAME_PING             = 0x9;
     const FRAME_PONG             = 0xA;
	 const MASK_SET               = 128;
	 const FIN_RSV                = 0x8;
	 const CLOSE_REPLY            = 0x88;
	 
     public function __construct()
	 {
	 parent::__construct();
	 }
     public function wsEncode($rawdata)
	 {
	 $fin_rsv_op = 0x81;
	 $bytes = strlen($rawdata);
	   if($bytes <= 125)
	   {
		$header = pack('CC', $fin_rsv_op, $bytes);
		}elseif($bytes > 125 && $bytes < 65536)
		   {
		   $header = pack('CCn', $fin_rsv_op, 126, $bytes);
		   }elseif($bytes >= 65536)
		     {
		     $header = pack('CCNN', $fin_rsv_op, 127, $bytes);
		     }
	 return $header. $rawdata;
	 }
	 
	 public function wsDecode($clientData)
	 {
	 $data = array();
       do
	   {
	   $decoded = $this->decrypt($clientData);
	     if($decoded === false)
		 {
		 return false;
		 }
	   $data[] = $decoded[0];
	   $clientData = $decoded[2];
	   }while($decoded[1] === true);
	 return $data;
	 }
	 
	 private function decrypt($clientData)
	 {
	   if(base_convert(bin2hex(substr($clientData, 1, 1)), 16, 10) & 128 !== self::MASK_SET)
	   {
	   return false;
	   }
	   if((base_convert(bin2hex(substr($clientData, 0, 1)), 16, 10) & 15) == self::FRAME_CONNECTION_CLOSE)
	   {
	   return false;
	   }
	 $bytes = base_convert(bin2hex(substr($clientData, 1, 1)), 16, 10) & 127;
	   if($bytes <= 125)
	   {
	   $lng =& $bytes;
	   $masks = substr($clientData,2, 4);
	   $data = substr($clientData, 6);
	   strlen($data) > $lng ? $more = true : $more = false;
	   $more === true ? $moredata = substr($data, $lng) : $moredata = null;
	   }elseif($bytes == 126)
	     {
		 $lng = base_convert(bin2hex(substr($clientData, 2, 2)), 16, 10);
		 $masks = substr($clientData, 4, 4);
		 $data = substr($clientData, 8);
		 strlen($data) > $lng ? $more = true : $more = false;
		 $more === true ? $moredata = substr($data, $lng) : $moredata = null;
	     }elseif($bytes == 127)
		   {
		   $lng = base_convert(bin2hex(substr($clientData, 2, 8)), 16, 10);
		   $masks = substr($clientData, 10, 4);
		   $data = substr($clientData, 14);
		   strlen($data) > $lng ? $more = true : $more = false;
		   $more === true ? $moredata = substr($data, $lng) : $moredata = null;
	       }else{
		     return false;
			 }
	 $decoded = "";
	   for($i = 0; $i < $lng; $i++)
	   {
	   $decoded .= $data[$i] ^ $masks[$i%4];
	   }
	  $fs = strlen($clientData);
	 return array($decoded, $more, $moredata);
	 }
	 
	 public function close()
	 {
	 $frame = pack('CC', 0x88, 0);
	 return $frame;
	 }
}