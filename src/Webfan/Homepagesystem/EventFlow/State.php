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
//use SuperClosure\Serializer;
//use SuperClosure\Analyzer\AstAnalyzer;
//use SuperClosure\Analyzer\TokenAnalyzer;


use webfan\hps\Event as Event;


class State extends \frdl\Flow\EventEmitter 
{
		
   public function once($event, $callback, $obj = null) {
	    
   	  $THAT =$this; 
   	  $obj = $obj;
   	  $callback= $callback;
      $listener = (function() use($event, &$callback, &$THAT, &$obj, &$listener){
   	    	$THAT->removeEventListener($event, $listener);
   	  	     call_user_func_array(($obj === null)  ? $callback : array($obj, $callback), func_get_args());
   	  });
   	  $this->on($event, $listener);
   	  
    return $this;
  }



  public function removeEventListener($event, $listener){
     if (!isset($this->events[$event])) return $this;
    
    $indexOf = 0;
    foreach ($this->Iterator('Array', $this->events[$event]) as $EventListener) {
      // if($EventListener === $listener)	{
	   if(spl_object_id((object)$EventListener) === spl_object_id((object)$listener))	{
         array_splice($this->events[$event], $indexOf, 1);
         $indexOf--;
		 
	   }
         $indexOf++;
    }
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
		
     	if(!is_callable($callback)){
			trigger_error('Cannot trigger Event '.$eventName.' on Listener #'.$indexOf, E_USER_WARNING);
			continue;
		} 	

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
  	
  	
	

	
	function __destruct(){
		
	}
	
	
}
