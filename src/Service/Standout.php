<?php

namespace App\Service;

class Standout
{
    public function __construct(private readonly String $start_time)
    {}

    public function getStartTime(): string
    {
        return $this->start_time;
    }
}
/*
 function next_standout_date() : String {
    $today = date('D');
    if ($today !== 'Sun') {
        return date("F j",strtotime("next Sunday"));
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
