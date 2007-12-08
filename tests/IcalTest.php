<?php
require_once 'PHPUnit/Framework.php';

require_once '../src/Structures/Ical.php';

class IcalTest extends PHPUnit_Framework_TestCase
{
    private $ical;

    function setUp()
    {
        $this->ical = new Structures_Ical();
    }

    function testConstruction()
    {
        $this->assertTrue(is_object($this->ical));
    }

    function testParsingAnIcalFileReturnsAnArrayAndTheCorrectCount()
    {
        $this->ical->parseFile('./basic.ics');
        $this->assertTrue(is_array($this->ical->getAllData()));
        $this->assertEquals(5, count($this->ical->getAllData()));
    }

    function testGettingTheEventsReturnsCorrectNumberOfEvents()
    {
        $this->ical->parseFile('./basic.ics');
        $this->assertTrue(is_array($this->ical->getEventList()));
        $this->assertEquals(11, count($this->ical->getEventList()));
        $this->assertEquals(11, $this->ical->getEventCount());
    }

    function testgetCalendarNameReturnsCorrectName()
    {
        $this->ical->parseFile('./basic.ics');
        $this->assertEquals('Højskolens kalender', $this->ical->getCalendarName());
    }

    function testParseUrlCanParseAnUrl()
    {
        // Attention: this test needs access to the internet
        $this->ical->parseUrl('http://www.google.com/calendar/ical/scv5aba9r3r5qcs1m6uddskjic%40group.calendar.google.com/public/basic.ics');
        $this->assertEquals('Højskolens kalender', utf8_decode($this->ical->getCalendarName()));
    }

}
