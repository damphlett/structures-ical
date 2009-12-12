<?php
require_once '../src/Structures/Ical.php';

$ical = file_get_contents('http://www.google.com/calendar/ical/8qle53tc1ogekrkuf3ndam51as%40group.calendar.google.com/public/basic.ics');
$parser = new Structures_Ical;
$res = $parser->test($ical);
print_r($res);