<?php


function AVSHME_getCache($key)  {
            
    $calendar = IntlCalendar::createInstance(); 
    $cache = get_option("AVSHME_".$key,[
        "date"=>$calendar->getTime(),
        "data"=>[NULL]
    ]);
    AVSHME_addLogAveonline(array(
        "type"=>"getCache",
        "key"=>$key,
        "dif"=>abs($cache["date"] - $calendar->getTime()),
        "getTime"=>$calendar->getTime(),
        "cache"=>$cache,
    ));
    if(abs($cache["date"] - $calendar->getTime()) > (1000 * 60 * 30)){
        return NULL;
    }
    return json_decode($cache["data"][0]);
}
function AVSHME_setCache($key,$value)  {
    AVSHME_addLogAveonline(array(
        "type"=>"setCache",
        "key"=>$key,
        "value"=>[json_encode($value)]
    ));
    $calendar = IntlCalendar::createInstance(); 
    update_option("AVSHME_".$key,[
        "date"=>$calendar->getTime(),
        "data"=>[json_encode($value)]
    ],true);
}