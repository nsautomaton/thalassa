<?php
namespace Thalassa;
interface wampAppInterface{
    function __construct();
	
    function onOpen($conn, $sessionKey);
	
    function onSubscribe(channelsInterface $channelAccess, $conn, $requestID, $channel);
	
	function onUnsubscribe(channelsInterface $channelAccess, $conn, $channel, $requestID);
	
	function onPublish(channelsInterface $channelAccess, $conn, array $data, array $options);
	
	function onRegister(proceduresInterface $exe, $conn, $call, $requestID, array $options);
	
	function onUnregister(proceduresInterface $exe, $conn, $regID, $requestID);
	
	function onCall(proceduresInterface $exe, $conn, $call, array $args, array $options);
	
	function onFlag($conn, $reason);
	
	function onError($conn, $details);
	
	function onClose($conn);
}