<?php


function AVSHME_getCache($key)
{
    try {
        $calendar = IntlCalendar::createInstance();
        $cache = get_transient("AVSHME_" . $key, [
            "date" => $calendar->getTime(),
            "data" => [NULL]
        ]);
        AVSHME_addLogAveonline(array(
            "type" => "getCache",
            "key" => $key,
            "dif" => abs($cache["date"] - $calendar->getTime()),
            "getTime" => $calendar->getTime(),
            "cache" => $cache,
        ));
        if ($cache == NULL || $cache["data"] == NULL || $cache["data"][0] == NULL) {
            return NULL;
        }
        if (abs($cache["date"] - $calendar->getTime()) > (1000 * 60 * 50 * 4)) {
            return NULL;
        }
        // echo "<pre>";
        // echo json_encode(array(
        //     "type"=>"getCache",
        //     "key"=>$key,
        //     "cache"=>$cache,
        //     "data"=>$cache["data"][0],
        // ));
        // echo "</pre>";
        return json_decode($cache["data"][0]);
    } catch (\Throwable $th) {
        return NULL;
    }
}
function AVSHME_setCache($key, $value)
{
    try {
        AVSHME_addLogAveonline(array(
            "type" => "setCache",
            "key" => $key,
            "value" => [json_encode($value)]
        ));
        $calendar = IntlCalendar::createInstance();
        set_transient("AVSHME_" . $key, [
            "date" => $calendar->getTime(),
            "data" => [json_encode($value)]
        ], 12 * HOUR_IN_SECONDS);
    } catch (\Throwable $th) {
        //throw $th;
    }
}
