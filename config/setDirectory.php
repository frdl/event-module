<?php
use Psr\Container\ContainerInterface;
use function DI\decorate;


return  [
	
	
	'dir.compiled.events' =>function (ContainerInterface $c) {			                
	     $d = $c->get('project')->dir.\DIRECTORY_SEPARATOR.'compiled'.\DIRECTORY_SEPARATOR.'~events';
             if(!is_dir($d)){
               mkdir($d, 0755, true);
             }
	  return $d;	
        },	
	
	'state.emitter' => decorate(function($emitter, ContainerInterface $c){
	   $emitter->once('bootstrap', function($eventName, $emitter, $container){
                \Webfan\App\EventModule::setBaseDir($container->get('dir.compiled.events'));
           });
		return $emitter;
	}),
	
	

		
];
