<?php
/**
 * This class Parse iCal standard. Is prepare to iCal feature version.
 * Now is testing with apple iCal standard 2.0.
 *
 * PHP Version 5
 *
 * <code>
 * $ical = new ical();
 * $ical->parse('./calendar.ics');
 * $ical->getAllData();
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
        return count($this->getEventList());
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

    /**
     * Prekladac kalendare
     *
     * @param unknown_type $uri
     * @return unknown
     */
    function parse($uri)
    {
        // read FILE text
        // $this->file_text = $this->readFile($uri);

        $this->cal = array(); // new empty array

        $this->event_count = -1;

        $this->file_text = split("[\n]", $this->file_text);

        // is this text vcalendar standart text ? on line 1 is BEGIN:VCALENDAR
        if (!stristr($this->file_text[0],'BEGIN:VCALENDAR')) return 'error not VCALENDAR';

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
                        $type = $value; // save tu array under value key
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
     */
    private function addToArray($type, $key, $value)
    {
        if ($key == false) {
            $key = $this->last_key;
            switch ($type) {
                case 'VEVENT': $value = $this->cal[$type][$this->event_count][$key].$value;break;
                case 'VTODO': $value = $this->cal[$type][$this->todo_count][$key].$value;break;
            }
        }

        if (($key == "DTSTAMP") or ($key == "LAST-MODIFIED") or ($key == "CREATED")) $value = $this->icalDateToUnix($value);
        if ($key == "RRULE" ) $value = $this->icalRrule($value);

        if (stristr($key,"DTSTART") or stristr($key,"DTEND")) list($key,$value) = $this->icalDtDate($key,$value);

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
     * @param unknown_type $text
     * @return unknown
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
     * @return unknown
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
        $ical_date = str_replace('T', '', $ical_date);
        $ical_date = str_replace('Z', '', $ical_date);

        // TIME LIMITED EVENT
        ereg('([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{0,2})([0-9]{0,2})([0-9]{0,2})', $ical_date, $date);

        // UNIX timestamps can't deal with pre 1970 dates
        if ($date[1] <= 1970) {
            $date[1] = 1971;
        }
        return  mktime($date[4], $date[5], $date[6], $date[2],$date[3], $date[1]);
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

        return array($key,$return_value);
    }

    /**
     * Return sorted eventlist as array or false if calenar is empty
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
     * Compare two unix timestamp
     *
     * @param array $a
     * @param array $b
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
    function getEventList()
    {
        return $this->cal['VEVENT'];
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
    function getCalenderData()
    {
        return $this->cal['VCALENDAR'];
    }

    /**
     * Return base calendar data
     *
     * @return array
     */
    function getCalendarName()
    {
        return $this->cal['VCALENDAR']['X-WR-CALNAME'];
    }

    /**
     * Return array with all data
     *
     * @return array
     */
    function getAllData()
    {
        return $this->cal;
    }
}
