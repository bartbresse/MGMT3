<?php
use Phalcon\Mvc\User\Component;

class EventHandler extends Component
{
    private $observers;
    private $eventnames;
    private $events;

    protected $eventlist;

    public function registerObserver(String $eventname,String $handler)
    {
        if(!is_array($this->eventnames))
        {
            $this->eventnames = [];
        }

        $this->observers[] = ['handler' => $handler,'eventname' => $eventname];
        $this->eventnames[] = $eventname;
    }

    public function registerEvent($eventname,EntityInfo $ei)
    {
        $this->eventlist[] = ['eventname' => $eventname,'entityinfo' => $ei];
    }

    public function handleEvents()
    {
        if(count($this->eventnames) > 0 && count($this->eventlist) > 0) {
            foreach ($this->eventlist as $event) {

                if(in_array($event['eventname'],$this->eventnames))
                {
                    foreach($this->observers as $observer)
                    {
                        if($observer['eventname'] == $event['eventname'])
                        {
                            $classname = $observer['handler'];
                            $class = new $classname($event['eventname'],$event['entityinfo']);
                        }
                    }
                }
            }
        }
    }
}
?>