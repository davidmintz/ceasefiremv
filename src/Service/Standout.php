<?php

namespace App\Service;

class Standout
{
    public function __construct(private readonly Array $config)
    {}

//    public function getStartTime(): string
//    {
//        return $this->config['start_time'];
//    }

    public function getDayOfWeek(): string
    {
        return $this->config['day_of_week'];
    }

    public function getStartTime(): String
    {
        return $this->config['start_time'];
    }

    /**
     * @throws \Exception
     */
    public function getDuration() : \DateInterval
    {
        $duration = $this->config['duration'];
        return new \DateInterval(
            sprintf('PT%dH%dM',$duration['hours'],$duration['minutes'])
        );

    }

    /**
     * returns the next standout date
     * @return String
     * @throws \Exception
     */
    public function getNextStandoutDate() : String {

        $today = date('l');
        list($hour, $minute) = explode(':',$this->getStartTime());
        // if today is NOT the day-of-week of the standout
        if ($today !== $this->getDayOfWeek()) {
            $date = \DateTime::createFromFormat("U",strtotime('next '.$this->getDayOfWeek()));
            $date->setTime($hour,$minute);
            return $date->format('F j \a\t g:i a');
        }
        // today is Sunday. is it over yet?
        $now = new \DateTimeImmutable();
        $end = $now->add($this->getDuration());
        if ($now < $end) {
            $when = "today at ".$now->format('g:i a');
        } else {
            $when = $now->add(new \DateInterval('P7D'))->format('F j \a\t g:i a');
        }

        return $when;
    }
}
//$config = [ 'day_of_week' => "Sunday", 'start_time' => "16:00", 'duration' => ['hours' => 16, 'minutes' => 0 ]];
/*
 function next_standout_date() : String {
    $today = date('D');
    if ($today !== 'Sun') {
        return date("F j",);
    }
    // else
    // is it 6:00p or later?
    $hour = date("G");
    if ($hour >= 18) {
        return date("F j",strtotime("next Sunday"));
    } else {
        return date("F j");
    }
}
 */