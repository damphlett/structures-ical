<?php
/**
 * This class Parse iCal standard. Is prepare to iCal feature version.
 * Now is testing with apple iCal standard 2.0.
 *
 * PHP Version 5
 *
 * <code>
 * $gateway = new Structures_IcalGateway();
 * $ical = $gateway->getFromUri($uri);
 * print_r($ical->getAllData());
 * </code>
 *
 * @package Structures_ICal
 * @author  Roman Ožana (Cz)
 * @copyright Roman Ožana (Cz)
 * @link www.nabito.net
 * @version 1.0
 */
class Structures_Ical
{
    protected $description;

    /**
     * Text in file
     *
     * @var string
     */
    private $file_text;
    /**
     * This array save iCalendar parse data
     *
     * @var array
     */
    private $cal;
    /**
     * Number of Events
     *
     * @var integer
     */
    private $event_count;
    /**
     * Number of ToDos
     *
     * @var unknown_type
     */
    private $todo_count;
    /**
     * Help variable save last key (multiline string)
     *
     * @var unknown_type
     */
    private $last_key;
    /**
     * Read text file, icalender text file
     *
     * @param string $file
     * @return string
     */
    private function readFile($file)
    {
        $this->file = $file;
        $file_text = join ("", file ($file)); //load file

        # next line withp preg_replace is because Mozilla Calendar save values wrong, like this ->

        #SUMMARY
        # :Text of sumary

        # good way is, for example in SunnyBird. SunnyBird save iCal like this example ->

        #SUMMARY:Text of sumary

        $file_text = preg_replace("/[\r\n]{1,} ([:;])/","\\1",$file_text);

        return $file_text; // return all text
    }

    /**
     * Vraci pocet udalosti v kalendari
     *
     * @return unknown
     */
    function getEventCount()
    {
        return count($this->getEvents());
    }

    /**
     * Vraci pocet ToDo uloh
     *
     * @return unknown
     */
    function getTodoCount()
    {
        return $this->todo_count;
    }

    function parseFile($file)
    {
        $this->file_text = $this->readFile($file);
        return $this->parse($file);
    }

    function getTimeZone()
    {
        return $this->cal['VCALENDAR']['X-WR-TIMEZONE'];
    }

    function parseUrl($url)
    {
        require_once 'HTTP/Request.php';
        $request = new HTTP_Request($url);
        if (PEAR::isError($request->sendRequest())) {
            throw new Exception('Could not read uri');
        }
        $this->file_text = $request->getResponseBody();
        return $this->parse($url);
    }

    function parseContent($content)
    {
        $this->file_text = $content;
        return $this->parse();
    }

    /**
     * Prekladac kalendare
     *
     * @param unknown_type $uri
     * @return unknown
     */
    protected function parse($uri = null)
    {
        // read FILE text
        // $this->file_text = $this->readFile($uri);

        $this->cal = array(); // new empty array

        $this->event_count = -1;

        $this->file_text = split("[\n]", $this->file_text);

        // is this text vcalendar standard text ? on line 1 is BEGIN:VCALENDAR
        if (!stristr($this->file_text[0],'BEGIN:VCALENDAR')) {
            return 'error not VCALENDAR';
        }

        foreach ($this->file_text as $text) {
            $text = trim($text); // trim one line
            if (!empty($text)) {
                // get Key and Value VCALENDAR:Begin -> Key = VCALENDAR, Value = begin
                list($key, $value) = $this->returnKeyValue($text);

                switch ($text) { // search special string
                    case "BEGIN:VTODO":
                        $this->todo_count = $this->todo_count+1; // new todo begin
                        $type = "VTODO";
                        break;

                    case "BEGIN:VEVENT":
                        $this->event_count = $this->event_count+1; // new event begin
                        $type = "VEVENT";
                        break;
                    case "BEGIN:VCALENDAR": // all other special string
                    case "BEGIN:DAYLIGHT":
                    case "BEGIN:VTIMEZONE":
                    case "BEGIN:STANDARD":
                        $type = $value; // save to array under value key
                        break;
                    case "END:VTODO": // end special text - goto VCALENDAR key
                    case "END:VEVENT":
                    case "END:VCALENDAR":
                    case "END:DAYLIGHT":
                    case "END:VTIMEZONE":
                    case "END:STANDARD":
                        $type = "VCALENDAR";
                        break;
                    default: // no special string
                        $this->addToArray($type, $key, $value); // add to array
                        break;
                }
            }
        }

        return $this->cal;
    }

    /**
     * Add to $this->ical array one value and key. Type is VTODO, VEVENT, VCALENDAR ... .
     *
     * @param string $type
     * @param string $key
     * @param string $value
     *
     * @return array
     */
    private function addToArray($type, $key, $value)
    {
        if ($key == false) {
            $key = $this->last_key;
            switch ($type) {
                case 'VEVENT':
                    $value = $this->cal[$type][$this->event_count][$key].$value;
                    break;
                case 'VTODO':
                    $value = $this->cal[$type][$this->todo_count][$key].$value;
                    break;
            }
        }

        if (($key == "DTSTAMP") or ($key == "LAST-MODIFIED") or ($key == "CREATED")) {
            $value = $this->icalDateToUnix($value);
        }

        if ($key == "RRULE") {
            $value = $this->icalRrule($value);
        }

        if ($key == "LOCATION") {
            $value = $data = str_replace('\\,', ',', $value);
            $value = $data = str_replace('\\;', ';', $value);
        }

        if ($key == "X-WR-CALDESC") {
            // how do we get this to store all the information
            $this->description .= $value;
        }

        if ($key == "DESCRIPTION") {
            $value = $data = str_replace('\\,', ',', $value);
            $value = $data = str_replace('\\;', ';', $value);
            $value = $data = str_replace('\\n', "\n", $value);
        }

        if (stristr($key,"DTSTART") or stristr($key,"DTEND")) {
            list($key,$value) = $this->icalDtDate($key,$value);
        }

        switch ($type) {
            case "VTODO":
                $this->cal[$type][$this->todo_count][$key] = $value;
                break;

            case "VEVENT":
                $this->cal[$type][$this->event_count][$key] = $value;
                break;

            default:
                $this->cal[$type][$key] = $value;
                break;
        }
        $this->last_key = $key;
    }

    /**
     * Parse text "XXXX:value text some with : " and return array($key = "XXXX", $value="value");
     *
     * @param string $text
     *
     * @return array
     */
    private function returnKeyValue($text)
    {
        preg_match("/([^:]+)[:]([\w\W]+)/", $text, $matches);

        if (empty($matches)) {
            return array(false,$text);
        } else  {
            $matches = array_splice($matches, 1, 2);
            return $matches;
        }
    }

    /**
     * Parse RRULE  return array
     *
     * @param unknown_type $value
     *
     * @return array
     */
    private function icalRrule($value)
    {
        $rrule = explode(';',$value);
        foreach ($rrule as $line) {
            $rcontent = explode('=', $line);
            $result[$rcontent[0]] = $rcontent[1];
        }
        return $result;
    }

    /**
     * Return Unix time from ical date time fomrat (YYYYMMDD[T]HHMMSS[Z] or YYYYMMDD[T]HHMMSS)
     *
     * @param unknown_type $ical_date
     * @return unknown
     */
    private function icalDateToUnix($ical_date)
    {
        return strtotime($ical_date);
    }

    /**
     * Return unix date from iCal date format
     *
     * @param string $key
     * @param string $value
     * @return array
     */
    private function icalDtDate($key, $value)
    {
        /*
         $value = $this->icalDateToUnix($value);

         // zjisteni TZID
         $temp = explode(";",$key);

         $data = '';

         if (empty($temp[1])) { // neni TZID
         $data = str_replace('T', '', $data);
         return array($key,$value);
         }
         // pridani $value a $tzid do pole
         $key =     $temp[0];
         $temp = explode("=", $temp[1]);
         $return_value[$temp[0]] = $temp[1];
         $return_value['unixtime'] = $value;
         */
        return array($key,strtotime($value));
        //return array($key,$return_value);
    }


    /**
     * Compare two unix timestamp
     *
     * @param array $a
     * @param array $b
     *
     * @return integer
     */
    private static function icalDtstartCompare($a, $b)
    {
        return strnatcasecmp($a['DTSTART']['unixtime'], $b['DTSTART']['unixtime']);
    }

    /**
     * Return eventlist array (not sort eventlist array)
     *
     * @return array
     */
    function getEvents()
    {
        return $this->cal['VEVENT'];
    }

    /**
     * @see getSortEventList()
     *
     * Which is better?
     */
    function getSortedEvents()
    {
        $events = $this->getEvents(); //udfyldt med alle dine events
        $sort = array();
        foreach($events as $eid=>$event) { $sort[$eid] = $event['DTSTART']; }
        array_multisort($events,SORT_ASC, SORT_NUMERIC,$sort);
        $this->cal['VEVENT'] = $events;
        return $this->cal['VEVENT'];
    }

    /**
     * Return sorted eventlist as array or false if calenar is empty
     *
     * @deprecated
     *
     * @return unknown
     */
    function getSortEventList()
    {
        $temp = $this->getEventList();
        if (!empty($temp)) {
            usort($temp, array($this, "icalDtstartCompare"));
            return    $temp;
        } else {
            return false;
        }
    }

    /**
     * Return todo arry (not sort todo array)
     *
     * @return array
     */
    function getTodoList()
    {
        return $this->cal['VTODO'];
    }

    /**
     * Return base calendar data
     *
     * @return array
     */
    function getCalendarData()
    {
        return $this->cal['VCALENDAR'];
    }

    /**
     * Return calender name
     *
     * @return array
     */
    function getCalendarName()
    {
        return $this->cal['VCALENDAR']['X-WR-CALNAME'];
    }

    function getCalendarDescription()
    {
        $this->description = $data = str_replace('\\,', ',', $this->description);
        $this->description = $data = str_replace('\\;', ';', $this->description);
        $this->description = $data = str_replace('\\n', "\n", $this->description);

        return $this->description;
    }

    /**
     * Return all data
     *
     * @return array
     */
    function getAllData()
    {
        return $this->cal;
    }

    /**
     * Gets an event
     *
     * @param string $event_identifier UID for the entry
     *
     * return array
     */
    function getEvent($event_identifier)
    {
        $events = $this->getEvents();
        foreach ($events as $event) {
            if ($event['UID'] == $event_identifier) {
                return $event;
                continue;
            }
        }
        throw new Exception('Event not found');
    }
}
