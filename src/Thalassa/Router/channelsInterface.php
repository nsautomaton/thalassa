<?php
namespace Thalassa\Router;
use Thalassa\wampProtocol\wampProtocol;
interface channelsInterface{
    function __construct(wampProtocol $protocol);
	
	function create($channel, $creator);
	
	function destroy($channel);
	
	function add($conn, $channel, $requestID);
	
	function get_subscription_id($conn, $channel);
	
	function clear_ghost_data($conn);
	
	function delete($conn, $channel, $requestID);
	
	function count_subscribers($channel);
	
	function get_subscribers($channel);
}