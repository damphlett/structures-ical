<?php
/**
 * This class Parse iCal standard. Is prepare to iCal feature version.
 * Now is testing with apple iCal standard 2.0.
 *
 * PHP Version 5
 *
 * <code>
 * $ical = new Structures_IcalGateway();
 * $ical->loadFromUri();
 * $ical->getAllData();
 * </code>
 *
 * @package Structures_ICal
 * @author  Lars Olesen <lars@legestue.net>
 * @copyright Lars Olesen <lars@legestue.net>
 * @link intraface.dk
 */
class Structures_IcalGateway
{
    function getFromUri($uri)
    {
        $ical = new Structures_Ical();
        $ical->parseUrl($uri);
        return $ical;
    }
}