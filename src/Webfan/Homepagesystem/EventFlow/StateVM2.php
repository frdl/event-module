<?php 
/**
 * 
 * Copyright  (c) 2017, Till Wehowski
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. All advertising materials mentioning features or use of this software
 *    must display the following acknowledgement:
 *    This product includes software developed by the frdl/webfan.
 * 4. Neither the name of frdl/webfan nor the
 *    names of its contributors may be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY frdl/webfan ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL frdl/webfan BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */
 /**
 * EventEmitter javascript like event based final state object : 
 *    https://github.com/frdl/-Flow/blob/master/api-d/4/js-api/library.js/core/plugin.core.js#L4501
 */
namespace Webfan\Homepagesystem\EventFlow;


use webfan\hps\Event as Event;


use function Opis\Closure\{serialize as cserialize, unserialize as cunserialize};


class StateVM2 extends State implements \Serializable
{
	
    const K = '^';	
	
  protected $SecretSigningKey = null;
  public $serializeClosures = true;	
//  protected $tagName = __CLASS__;	
	protected $events = [];
	
  public function getEvents(){
	  return $this->events;
  }
	
  public function on($event, $callback, $obj = null, &$index = null) {
    if (!isset($this->events[$event])) {
      $this->events[$event] = [];
    }
	  
	  
 	if(null===$index){
	//	$bin = new \frdl\webfan\Serialize\Binary\bin;  
		//$index = $bin->serialize($cb); 
		$index =  $this->_genKey($callback);
	}
	  
	  
	  if($obj === null){
		  if(true===$this->serializeClosures){
		 //  $serializer = (null=== $this->SecretSigningKey) ? new \SuperClosure\Serializer() : new \SuperClosure\Serializer(null, $this->SecretSigningKey);
		 //  $cb = $serializer->serialize($callback);		
		     $cb = \Opis\Closure\serialize($callback);
		  }else{
			   $cb = $callback;
		  }
		
	  }else{
		$cb = [$obj, $callback];
	  }	    
	

   $this->events[$event][$index] = $cb;
	 //   $this->events[$event][] = $cb;
    return $this;
  }
  	

 public function trigger($eventName, $data = [], Event &$event = null) {
     if (!isset($this->events[$eventName])) return $this;
   
  
    $indexOf=0;
    foreach ($this->Iterator('Array', $this->events[$eventName]) as $callback) {
      	$payload = array();
      
      	$target = &$this;
      	$evData = &$data;
   	    array_push($payload, $eventName);
   	    array_push($payload, $target);  
     	array_push($payload, $evData);   
		
	if(null===$event){	
		if(!is_object($data) || true !== $data instanceof Event){
		  $event = new Event($eventName); 	
		}elseif(true === $data instanceof Event){
		   $event = &$data;	
		}		
	}
		
		
	if(!is_object($data) || true !== $data instanceof Event){	 
     	$payload[] = &$event;   
	}
		
		
		if(is_string($callback)){
			// $serializer = (null=== $this->SecretSigningKey) ? new \SuperClosure\Serializer() : new \SuperClosure\Serializer(null, $this->SecretSigningKey);
			// $callback = $serializer->unserialize($callback);
			$callback = \Opis\Closure\unserialize($callback);
		}	
		
     	if(!is_callable($callback)){
			trigger_error('Cannot trigger Event '.$eventName.' on Listener #'.$indexOf, E_USER_WARNING);
			continue;
		} 	
	//  if(frdl\run($callback, $payload) === false) break;
	  if(false === call_user_func_array($callback, $payload))break;
				
		if($event->isDefaultPrevented()){
			$event->setResult(null);
			break;
		}
		
			
		if($event->isPropagationStopped()){
			break;
		}
		
      $indexOf++;
    }
    return $this;
 }	
	
	
	
	 public function once($event, $callback, $obj = null) {
   	  $THAT = $this; 
	  $k = $this->_genKey($callback);
   	  $callback= ($obj === null) ? $callback : [$obj, $callback];
		 
	 // $serializer = (null=== $this->SecretSigningKey) ? new \SuperClosure\Serializer() : new \SuperClosure\Serializer(null, $this->SecretSigningKey);
	 // $cb = (is_callable($callback)) ? $serializer->serialize($callback) : $callback;
	 $cb = (is_callable($callback)) ? \Opis\Closure\serialize($callback) : $callback;
	
		 
		 
       $func =function($event, $THAT, $data) use($cb, $k){
		    $callback = unserialize($cb);
   	  	 
		  //  $THAT->removeEventListener($event, $fn);
		    //  unset($THAT->events[$event][$k]);
		  //  $THAT->removeEventListener($event, $k);
		    $events = $THAT->getEvents();
		     unset($events[$event][$k]);
		   $THAT->setEvents($events);
		   
		    		   
		    $res = call_user_func_array($callback, func_get_args());
		
		  return $res;
   	  };
		 
 	  $this->on($event, $func, null, $k);
   	  	 
    return $this;
  }
  
	
	
  protected function _genKey($cb){	 
	//  $serializer = (null=== $this->SecretSigningKey) ? new \SuperClosure\Serializer() : new \SuperClosure\Serializer(null, $this->SecretSigningKey);
	//  $cb = (is_callable($cb)) ? $serializer->serialize($cb) : $cb;
	   $cb = (is_callable($cb)) ? \Opis\Closure\serialize($cb) : $cb;
	  
	  $bin = new \frdl\webfan\Serialize\Binary\bin;  
	  $d = $bin->serialize( $cb );
	  $k = sha1($d).strlen($d);
	  
	  return $k;
  }
	
	
  public function removeEventListener($event, $callback, $obj = null){
     if (!isset($this->events[$event])) return $this;
	  
	  $events = $this->events;
	  
	   $listener = ($obj === null) ? $callback : array($obj, $callback);
	  
     // $serializer = (null=== $this->SecretSigningKey) ? new \SuperClosure\Serializer() : new \SuperClosure\Serializer(null, $this->SecretSigningKey);
	  $bin = new \frdl\webfan\Serialize\Binary\bin;  
	//  $sl =(is_callable( $listener )) ? $serializer->serialize($listener) : $listener;
	  $sl =(is_callable( $listener )) ? \Opis\Closure\serialize($listener) : $listener;

  //   $indexOf = 0;
	
	  
 //   foreach ($this->Iterator('Array', $events[$event]) as $indexOf => $EventListener) {
	  foreach ($events[$event] as $indexOf => $EventListener) {
		/*
		if(is_string($listener)&& !is_array($EventListener) && !is_string($EventListener) ){
			
			 $EventListener = $serializer->serialize($EventListener);
		}	
		*/
			
       if($EventListener ===$sl || $EventListener === $bin->serialize($sl)  || $EventListener === $listener  
		   || $indexOf === $listener 
		   || $indexOf === $sl 
		   || $indexOf === $this->_genKey($callback) 
		 )	{
       //  array_splice($this->events[$event], $indexOf, 0);	  
		   unset($events[$event][$indexOf]);
		 
		    
		   
		   if(0===count($events[$event]))unset($events[$event]);
       
         //    $indexOf--;
	   }
        //    $indexOf++;
    }
 
	   $this->events=$events;

    return $this; 	
  }
	
	
	
	public function setSecretSigningKey($key){
	     $this->SecretSigningKey = $key;
		return $this;
	}	
	

	
	public function test(){
	       echo '<pre>test; '.__METHOD__.'</pre>';
		return $this;
	}	
	
	public function setEvents($events){
	        $this->events = $events;
		return $this;
	}		
	
	
	

    public function serialize() {
  //      echo "Serializing MyClass...\n";
    //    return serialize($this->data);
		$events = $this->events;
		foreach($events as $name => $listeners){
		   foreach(	$listeners as $index => $listener){
			   if(is_array($listener) ){
				   $list=$listener;
				   $l = function() use($list){
					 return call_user_func_array($list, func_get_args());  
				   };   
				   
			    //   $serializer = (null=== $this->SecretSigningKey) ? new \SuperClosure\Serializer() : new \SuperClosure\Serializer(null, $this->SecretSigningKey);
			    //   $events[$name][$index] = $serializer->serialize($l);	
				  $events[$name][$index] = \Opis\Closure\serialize($l);	
			   }  elseif( !is_string($listener) ){
			     //  $serializer = (null=== $this->SecretSigningKey) ? new \SuperClosure\Serializer() : new \SuperClosure\Serializer(null, $this->SecretSigningKey);
			      // $events[$name][$index] = $serializer->serialize($listener);	
				   $events[$name][$index] = \Opis\Closure\serialize($listener);	
			  }  else {
				   
				    $events[$name][$index] = $listener;
			   }
		   }
		}	
		
		//$context = $this->_context;
		
	///	$context = serialize($context);
		
		
		$data = array(
			'events' => $this->events,
			//'tagName' => $this->tagName,
			'name' => $this->name,
		//	'context' => $context
		);	
		
		$bin = new \frdl\webfan\Serialize\Binary\bin;
		return $bin->serialize($data);
    }
   
	
    public function unserialize($data) {
     //   echo "Unserializing MyClass...\n";
     //   $this->data = unserialize($data);
		$bin = new \frdl\webfan\Serialize\Binary\bin;
		$data = $bin->unserialize($data);
		
		//$data['context'] = unserialize($data['context']);
		
		//$this->_context=$data['context'];
		
		$this->name = $data['name'];
		
		
		//print_r($data['events']);die();
		
		foreach($data['events'] as $name => $listeners){
		   foreach(	$listeners as $index => $listener){
			   if( !is_string($listener) &&  !is_array($listener) ){
			      // $serializer = (null=== $this->SecretSigningKey) ? new \SuperClosure\Serializer() : new \SuperClosure\Serializer(null, $this->SecretSigningKey);
			      // $data['events'][$name][$index] = $serializer->unserialize($listener);		
				   $data['events'][$name][$index] = \Opis\Closure\unserialize($listener);
			  }  
		   }
		}	
		
		$this->setEvents($data['events']);		
		
		  //if( $this->tagName !== $data['tagName']){
		   //warning ??? 	
		  //}		
		
		
		return $this;
    }  

}


