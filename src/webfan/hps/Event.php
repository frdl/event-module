<?php
namespace webfan\hps;

use webfan\hps\EmitterInterface;

//abstract 
class Event
{
    /**
     * Has propagation stopped?
     *
     * @var bool
     */
    protected $propagationStopped = false;
    protected $defaultPrevented = false;
    /**
     * The emitter instance.
     *
     * @var EmitterInterface|null
     */
    protected $emitter;
  /**
     * The event name.
     *
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $result;

    /**
     * @var array
     */
    protected $arguments;


	protected $exception;
	
	
	
	
    /**
     * Create a new event instance.
     *
     * @param string $name
     */
    public function __construct($name = null, EmitterInterface $emitter = null)
    {
		if(null===$name)$name=get_class($this);
        $this->name = $name;
		
       // $this->result = $result;
     //   $this->arguments = $arguments;		
		
		if(null !== $emitter){
		 $this->setEmitter($emitter);	
		}
    }

    public function __call($name, $args)
    {
		if('is'===substr($name, 0, 2) && is_callable([$this, 'is' . substr($name, 2, strlen($name))])){
		   return call_user_func_array([$this, 'is' . substr($name, 2, strlen($name))], $args);	
		}elseif('is'===substr($name, 0, 2) && is_callable([$this, '_is'])){
			array_unshift($args,  substr($name, 2, strlen($name)) );
		   return call_user_func_array([$this, '_is'], $args);	
		}
    }
	
	public function _is($type){
		$type = strtolower($type);
	  return (      $type === substr($this->getName(), 0, strlen($type)) 
		         ||	$type === substr($this->getName(), strlen($this->getName()) - strlen($type),  strlen($this->getName())) 
			) ? true : false;	
	}
	

	
	public function getException(){
        	 return $this->exception;	
	}
		
	public function setException(\Exception $e){
        $this->exception = $e;	
	}
	public function hasException(){
        return (is_object($this->exception) && $this->exception instanceof \Exception) ? true : false;
	}
			
	
	
	
    /**
     * Set an argument value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setArgument($key, $value)
    {
        $this->arguments[$key] = $value;

        return $this;
    }
    public function setArgumentReference($key, &$value)
    {
        $this->arguments[$key] = $value;

        return $this;
    }

    /**
     * Set the arguments.
     *
     * @param array $arguments
     *
     * @return self
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = array_merge($this->arguments, $arguments);

        return $this;
    }

    /**
     * Set the result, used when the operation is canceled.
     *
     * @param mixed $result
     *
     * @return self
     */
    public function setResult($result = null)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get the result, used when the operation is canceled.
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Cancel the operation.
     *
     * @param mixed $result
     */
    public function cancelOperation($result = null)
    {
        $this->setResult($result);
        $this->stopPropagation();
    }




    /**
     * Get the passed arguments.
     *
     * @return array method arguments
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Get an argument by key.
     *
     * @param string $key argument key
     *
     * @throws ErrorException
     *
     * @return mixed
     */
    public function getArgument($key)
    {
        if (!$this->hasArgument($key) ) {
           // throw new \Exception('Undefined index: '.$key);
			return null;
        }

        return $this->arguments[$key];
    }
	
    public function hasArgument($key)
    {
        return ( array_key_exists($key, $this->arguments)) ? true : false;
    }	
	
    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
	
	
	
    /**
     * Set the Emitter.
     *
     * @param EmitterInterface $emitter
     *
     * @return $this
     */
    public function setEmitter(EmitterInterface $emitter = null)
    {
        $this->emitter = $emitter;

        return $this;
    }

    /**
     * Get the Emitter.
     *
     * @return EmitterInterface
     */
    public function getEmitter()
    {
        return $this->emitter;
    }

    /**
     * Stop event propagation.
     *
     * @return $this
     */
    public function stopPropagation()
    {
        $this->propagationStopped = true;

        return $this;
    }

    /**
     * Check weather propagation was stopped. defaultPrevented
     *
     * @return bool
     */
    public function isPropagationStopped()
    {
        return $this->propagationStopped;
    }

	
	
    public function preventDefault()
    {
        $this->defaultPrevented = true;

        return $this;
    }


    public function isDefaultPrevented()
    {
        return $this->defaultPrevented;
    }


}
