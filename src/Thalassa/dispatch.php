<?php
namespace Thalassa;
use Components\EventDispatcher\Event;
  /*Routes events between different classes
  */
class dispatch{
     private $event;
     public function __construct(Event $event)
	 {
	 $this->event = $event;
	 $protocol = new wampProtocol($event);
	 $event->on('onOpen', array($protocol, 'welcomeProtocol', 1));
	 $event->on('onData', array($protocol, 'switchboard', 2));
	 }
	 
	 public function install(wampAppInterface $app)
	 {
	 $this->event->on('onSessionEstablish', array($app, 'onOpen', 2));
	 $this->event->on('onSubscribeRequest', array($app, 'onSubscribe', 4));
	 $this->event->on('unSubscribe', array($app, 'onUnSubscribe', 4));
	 $this->event->on('Publish', array($app, 'onPublish', 4));
	 $this->event->on('Register', array($app, 'Onregister', 5));
	 $this->event->on('Unregister', array($app, 'OnUnregister', 4));
	 $this->event->on('Call', array($app, 'OnCall', 5));
	 $this->event->on('Flag', array($app, 'OnFlag', 3));
	 $this->event->on('Error', array($app, 'onError', 2));
	 $this->event->on('Close', array($app, 'onClose', 1));	 
	 }
}