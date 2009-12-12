<?php
date_default_timezone_set('Europe/Berlin');

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
        $this->ical->parseFile('./google_basic.ics');
        $this->assertTrue(is_array($this->ical->getAllData()));
        $this->assertEquals(5, count($this->ical->getAllData()));
    }

    function testGettingTheEventsReturnsCorrectNumberOfEvents()
    {
        $this->ical->parseFile('./google_basic.ics');
        $this->assertTrue(is_array($this->ical->getEvents()));
        $this->assertEquals(11, count($this->ical->getEvents()));
        $this->assertEquals(11, $this->ical->getEventCount());
    }

    function testgetCalendarNameReturnsCorrectName()
    {
        $this->ical->parseFile('./google_basic.ics');
        $this->assertEquals('Højskolens kalender', utf8_encode($this->ical->getCalendarName()));
    }

    /*
    function testParseUrlCanParseAnUrl()
    {
        // Attention: this test needs access to the internet
        $this->ical->parseUrl('http://www.google.com/calendar/ical/scv5aba9r3r5qcs1m6uddskjic%40group.calendar.google.com/public/basic.ics');
        $this->assertEquals('Højskolens kalender', utf8_decode($this->ical->getCalendarName()));
    }
    */


    function testParseEventDescriptionKeepsSpacesBetweenWords()
    {
        $this->ical->parseFile('./google_longdescription_basic.ics');
        $this->assertEquals(1, count($events = $this->ical->getEvents()));
        $expected = 'Et af Danmarks største rap-navne, Per Vers kommer forbi og holder et foredrag om, hvad det vil sige at være rapper. Publikum får en fornemmelse af det potente poetiske potentiale der gemmer sig bag hængerøven og håndtegnene.

Fordomme og dagdrømme bliver taget op – hvordan gør man,og især hvorfor? Per Vers bliver stadig inspireret af den originale hiphop-kultur, der fødtes i sluthalvfjerdsernes Bronx, og fortæller om dens oprindelse, idealer og historie – for det er en god historie!

Per Vers kombinerer sit foredrag med intense og intime smagsprøver på sin kunst i praksis, i små passager jævnt fordelt udover sit foredrag, så cirka en tredjedel af tiden bliver musikalsk. Her får man lov til at læne sig tilbage og leve sig ind i de fede rim og sjove tekster - og komme frem i sædet igen, nårdet er Danmarks ubetinget bedste freestyle-rap, hvor DU for lov til at bestemme hvad der skal spyttes i mikrofonen...!';

        $this->assertEquals($expected, $events[0]['DESCRIPTION']);
    }

    function testParseEventDescriptionNotCutOff()
    {
        $this->ical->parseFile('./google_longdescriptioncutofff_basic.ics');
        $this->assertEquals(1, count($events = $this->ical->getEvents()));
        $expected = 'Dette foredrag er i udgangspunktet kun for Vejle Idrætshøjskoles elever. Hvis du er meget interesseret i at deltage, kan du kontakte lars@vih.dk for yderligere oplysninger.

Turen går til Århus til et dobbeltarrangement. Først et foredrag og derefter en forestilling.

1) 18:30 Foredrag med præst Lars Tjalve: Døden og det evige liv.

Slut, færdig og ned i et hul i jorden. Sådan er det ikke siger den kristne
tro.. Når Gud er til, får døden ikke det sidste ord. Derfor er det muligt at
møde døden med håb, og derfor er det også muligt at tackle andres død uden
at fortvivle - selv om det er surt at skulle se sin egen og andres
dødelighed i øjnene.

2) 19:30 Forestillingen SNIP SNAP SNUDE - En poetisk og sanselig dans med døden.

Døden er forunderlig, fascinerende, mystisk og tabubelagt - en uomtvistelig del af livet. Døden kan man regne med.

I et rent sansebombardement, hvor det individuelle syn på døden flettes med forskellige kulturelle opfattelser af afslutningen på livet, tager dadadans døden under behandling. Det er danserens levende krop i leg med visuelle virtuelle elementer og døden som den evige dansepartner. SNIP SNAP SNUDE sætter perspektiv på vores opfattelse af døden - din, min og naboens!';

        $this->assertEquals($expected, $events[0]['DESCRIPTION']);
    }
}
