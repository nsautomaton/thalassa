<?php
namespace Thalassa\Dealer;
interface proceduresInterface{
    function __construct($protocol);
	
	function add($conn, $call, $options, $requestID);
	
	function delete($conn, $regID, $requestID);
	
	function send_register_error($conn, $requestID);
	
	function send_unregister_error($conn, $requestID);
	
	function execute($caller, $call, array $args, array $options);
	
	function send_call_error($conn, $requestID, $errormsg);
	
	function invoke($call, $invocutionID, $Arguments, $ArgumentsKw);
	
	function handle_call_results($conn, $invocutionID, array $args);
}