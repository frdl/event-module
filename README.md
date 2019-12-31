# event-module
Register EventHandlers in PHP to be lazy loaded.


# Usage
### Configuration 
Set the directory to save the events in.
````php
$my_directory =  __DIR__.\DIRECTORY_SEPARATOR.'compiled-events';
 \Webfan\App\EventModule::setBaseDir($my_directory);
 ````
 ### Register Events
 Register the events by your configuration/build script/process.
 ````php
 @\Webfan\App\EventModule::register('test', 'testing', static function($eventName, $emitter, \webfan\hps\Event $Event){
        print_r($Event->getArgument("testParam"));
});
 ````
 ### Call Events
 Dispatch the events later in a different script/process.
 ````php
$event = new \webfan\hps\Event('testing');
$event->setArgument('testParam', 'testValue'); 
\Webfan\App\EventModule::action('test')->emit('testing', $event);
 ````
