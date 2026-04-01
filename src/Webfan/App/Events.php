<?php 

/* 
 Copyright (c) 2019 Webfan Homepagesystem MIT License
 https://raw.githubusercontent.com/webfan3/hps/master/LICENSE
*/
namespace Webfan\App;
use webfan\hps\Event;
//use Webfan\Homepagesystem\EventFlow\StateVM2;
use Webfan\Homepagesystem\EventFlow\State;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;


class Events
{

	
	//const MODEL = StateVM2::class;  
	const MODEL = State::class;  
	protected $action;
	protected static $emitters = [];
	protected $emitter = null;
	protected $mark;
	
	protected $dirCompiled;
	protected static $dirBase = 'compiled.events';
	
	public static function setBaseDir($dirBase){
		if(!is_dir($dirBase) || !is_writable($dirBase)){
		  throw new \Exception('Cannot apply baseDir in '.__METHOD__);	
		}
		self::$dirBase = $dirBase;
	}
	
	public static function action($action, $reload = false, $dirBase = null){
		return new self($action, $reload, $dirBase); 
	}	
	
	public function __construct( $action, $reload = false, $dirBase = null){
		if(is_string($dirBase) && is_dir($dirBase)){
           self::setBaseDir($dirBase);
		}
		
		$action = trim($action, '\\/ ');
	//	$pathes = explode('\\', $action);
		$pathes = preg_split("/[\\\@\:\s\.\/]/", $action);
		$ft = str_replace('\\', '_', substr($action, 0,1));
		if(strlen($action) > 4){
			$ft.= \DIRECTORY_SEPARATOR. substr($action, 0,5);
		} 
		$ft.= \DIRECTORY_SEPARATOR;
		$path = $ft . implode(\DIRECTORY_SEPARATOR, $pathes).\DIRECTORY_SEPARATOR;
		
		$path = str_replace('\\', \DIRECTORY_SEPARATOR, $path);
		
		
		$this->dirCompiled = rtrim(self::$dirBase, \DIRECTORY_SEPARATOR.'/ ') .\DIRECTORY_SEPARATOR .$path;
		
	
			
		
		if(empty($action)){
		    throw new \Exception('No action/EventModule given in '.__METHOD__.' '.__LINE__);	
		}
		
		$this->action=$action;
		
		if(!isset(self::$emitters[$this->action]) || true ===$reload){
		   $this->_loadEmitter($this->emitter);	
		   self::$emitters[$this->action] = $this->emitter;
		}elseif( isset(self::$emitters[$this->action]) ){
			$this->emitter = self::$emitters[$this->action];
		}else{
			$classname = self::MODEL;
			$this->emitter = new $classname();	
		    self::$emitters[$this->action] = $this->emitter;
		}
		
		
		
	}
	public function getEmitter(){ 
		return $this->emitter;
	}		
	
	public function __call($name, $params){
		    if(null!==$this->emitter && is_callable([$this->emitter, $name])){
			   return call_user_func_array([$this->emitter, $name], $params);	
			}	
	}
	
	
	protected function _loadEmitter(&$emitter = null){
		
		if(file_exists($this->filepath() ) ){
			
    		$emitter = require $this->filepath();
			
		}else{
			$classname = self::MODEL;
			$emitter =new $classname();
		}
	}
	
	
	
	public function filepath($action = null){
		if(null===$action)$action=$this->action;
		$a = preg_replace("/[^A-Za-z0-9\_\-]/", '_', $action);
		return $this->dirCompiled.'on'.ucfirst($a).'.'.strlen($action).'.'.sha1($action).'.php';
	}	
	
	
	public function wrap($listener, $obj = null){
		
				if(null !== $obj && is_object($obj) && is_string($listener)  ){
				  $callback = [$obj, $listener];	
				}else{
				   $callback = $listener;	
				}	
		
		return (static function($eventName, $Emitter, $event) use ($callback){
			
			 $args = func_get_args();
	         $event = array_pop($args);					
			  if(is_object($event) && true === $event instanceof Event){
				   if($event->isPropagationStopped() || $event->isDefaultPrevented() ){					   
					    return false;   
				   }
				  
				
			  }
			$args[]=$event;
			try{
		         return call_user_func_array($callback, $args);
			}catch(\Exception $e){
			    throw $e;	
			}
		});
	}
	
	
	public static function register($action, $eventName, $listener, $obj = null, $once = false, $save = true){
		$E = new self($action);
		self::unregister($action, $eventName, $listener, $obj, false);
		$method = (true===$once) ? 'once' : 'on';				
		$E->{$method}($eventName, $E->wrap($listener, $obj), $obj);
		     if(true===$save){
				 $E->save(true);
			 }		
		return $E;
	}
	
	public static function unregister($action, $eventName = null, $listener = null, $obj = null, $save = true){
		$E = new self($action);
		$method = 'removeEventListener';
		
		if(null !== $eventName){
		     $E->{$method}($eventName, $E->wrap($listener, $obj), $obj);
		     if(true===$save){
				 $E->save(true);
			 }
		}
		
		if(null === $eventName 
		  // || 0 === count($E->getEmitter()->getEvents() )
		  ){
			if(file_exists( $E->filepath() ) ){
				unlink( $E->filepath() ) ;
			}
		}
		
		
		return $E;
	}	
	
	
	
	public function save($reload = true){
		if(!is_dir($this->dirCompiled)){
			mkdir($this->dirCompiled, 0775, true);
		}
		chmod($this->dirCompiled, 0775);
		
		$t = time();
		$action = $this->action;
	//	$numEvents = count($this->getEvents());
		
		$namespace = new PhpNamespace('Webfan\\App\\Generated');
		
		// Generate class using Nette\PhpGenerator
		$class = new ClassType('GeneratedEventEmitter');
		$namespace->add($class);
		$class->setExtends(self::MODEL);
		
		// Add property to store event metadata
		$class->addProperty('_generatedAt', $t)
			->setPublic()
			->setStatic();
		
		$class->addProperty('_action', $action)
			->setPublic()
			->setStatic();
		
	//	$class->addProperty('_eventCount', $numEvents)
		//	->setPublic()
	//		->setStatic();
		
		// Generate method that initializes event handlers from the parent emitter
		$initMethod = $class->addMethod('initGeneratedHandlers');
		$initMethod->setReturnType('void');
		$initMethod->addBody('// Event handlers are bound to this instance during instantiation');
		
		// Generate a factory method
		$factoryMethod = $class->addMethod('fromParent');
		$factoryMethod->setStatic();
		$factoryMethod->addParameter('parent');
		$factoryMethod->setReturnType('self');
		$factoryMethod->addBody('$instance = new self();' . "\n");
		$factoryMethod->addBody('// Copy event listeners from parent emitter');
		$factoryMethod->addBody('// Implementation depends on parent class structure');
		$factoryMethod->addBody('return $instance;');
		
		$banner = <<<BANNER
/*
* This file was generated by Webfan Php-Installer/Frdlweb CMS using Nette\PhpGenerator
* YOU SHOULD NOT edit this file manually! (created: $t)
* It contains $numEvents EventHandlers of the action/group `$action`
* 
* @generated
*/

BANNER;
		
		$code = "<?php\n" . $banner . "\n" . $class;
		
		$code .= "\n\n// Event emitter factory function\nreturn function() { return new \\Webfan\\App\\Generated\\GeneratedEventEmitter(); };";
		
		if(!is_dir(dirname($this->filepath()))){
			mkdir(dirname($this->filepath()), 0775, true);	
		}
		chmod(dirname($this->filepath()), 0775);	
		
		file_put_contents($this->filepath(), $code);
		
		chmod($this->filepath(), 0775);	
		
		if(true === $reload){
			self::$emitters[$this->action] = $this->emitter;
		}
	}
}
