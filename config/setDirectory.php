<?php
use Psr\Container\ContainerInterface;
use function DI\decorate;


return  [
	
		
	'state.emitter' => decorate(function($emitter, ContainerInterface $c){
	     $emitter->once('bootstrap', function($eventName, $emitter, $c){
             $d = $c->get('project')->dir.\DIRECTORY_SEPARATOR.'compiled'.\DIRECTORY_SEPARATOR.'~events';
             if(!is_dir($d)){
               mkdir($d, 0755, true);
             }
             \Webfan\App\EventModule::setBaseDir($d);
          });
		return $emitter;
	}),
	
	

		
];
