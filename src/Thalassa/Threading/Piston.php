<?php
namespace Components;
class Piston extends \Worker {
    public function run()
	{
	echo "worker started\n";
	}
}