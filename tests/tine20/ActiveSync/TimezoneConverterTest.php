<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @version     $Id$
 */

class ActiveSync_TimezoneConverterTest extends PHPUnit_Framework_TestCase
{
	
	protected $_uit = null;
	
	protected $_fixtures = array(
               'Europe/Berlin' => array(                                                                                                                            
                    'bias' => -60,
                    'standardName' => '',
                    'standardYear' => 0,
                    'standardMonth' => 10,
                    'standardDayOfWeek' => 0,
                    'standardDay' => 5,
                    'standardHour' => 3,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => '',
                    'daylightYear' => 0,
                    'daylightMonth' => 3,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 5,
                    'daylightHour' => 2,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => -60                                                         
               ),
               'Europe/Berlin' => array( //fake test with standardYear and daylightYear => will evaluate the specified year when guessing the timezone
                    'bias' => -60,
                    'standardName' => '',
                    'standardYear' => 2009, 
                    'standardMonth' => 10,
                    'standardDayOfWeek' => 0,
                    'standardDay' => 5,
                    'standardHour' => 3,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => '',
                    'daylightYear' => 2009,
                    'daylightMonth' => 3,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 5,
                    'daylightHour' => 2,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => -60                                                         
               ),
               'US/Arizona' => array(
                    'bias' => 420,
                    'standardName' => '',
                    'standardYear' => 0,
                    'standardMonth' => 0,
                    'standardDayOfWeek' => 0,
                    'standardDay' => 0,
                    'standardHour' => 0,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => '',
                    'daylightYear' => 0,
                    'daylightMonth' => 0,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 0,
                    'daylightHour' => 0,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => 0  
               ),
//             'Asia/Tehran' => array(
//                  'bias' => -210,
//                    'standardName' => null,
//                    'standardYear' => 0,
//                    'standardMonth' => 9,
//                    'standardDayOfWeek' => 2,
//                    'standardDay' => 4,
//                    'standardHour' => 2,
//                    'standardMinute' => 0,
//                    'standardSecond' => 0,
//                    'standardMilliseconds' => 0,
//                    'standardBias' => 0,
//                    'daylightName' => null,
//                    'daylightYear' => 0,
//                    'daylightMonth' => 3,
//                    'daylightDayOfWeek' => 0,
//                    'daylightDay' => 1,
//                    'daylightHour' => 2,
//                    'daylightMinute' => 0,
//                    'daylightSecond' => 0,
//                    'daylightMilliseconds' => 0,
//                    'daylightBias' => -60   
//             ),
        );
        
    protected $_packedFixtrues = array(
        'Europe/Berlin' => 'xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==',
        'US/Arizona'    => 'pAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==',
        'Africa/Douala'   => 'xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==',
//        'Asia/Baghdad' => 'TP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAABAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAAAABAAMAAAAAAAAAxP///w==',
//        'Asia/Tehran' => 'Lv///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAkAAgAEAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAABAAIAAAAAAAAAxP///w==',
//        'America/Sao_Paulo' => 'tAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAACAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAADAAIAAAAAAAAAxP///w==',
         );
         
    protected $_timezoneIdentifierToAbbreviation = array(
        'Europe/Berlin'     => 'CET',
        'US/Arizona'        => 'MST',
        'Africa/Algiers'    => 'CET',
        'Africa/Douala'     => 'WAT',
    );
    
    public function setUp()
    {
    	$this->_uit = new ActiveSync_TimezoneConverter();
    	$this->_uit->setLogger(Tinebase_Core::getLogger());
    }
        
    public function testGetPackedStringForTimezone()
    {
         foreach ($this->_packedFixtrues as $timezoneIdentifier => $packedString) {
            $this->assertEquals($packedString, $this->_uit->getPackedTimezoneInfoForTimezone($timezoneIdentifier), "Testing for timezone $timezoneIdentifier");
        }
    }
    
    public function testCachedResults()
    {
    	$this->_uit->setCache(Tinebase_Core::get('cache'));
    	$this->testActiveSyncTimezoneGuesser();
    	$this->testGetPackedStringForTimezone();
    	$this->_uit->setCache(null);
    }

    public function testActiveSyncTimezoneGuesser()
    {       
        foreach ($this->_fixtures as $timezoneIdentifier => $offsets) {
        	$timezoneAbbr = $this->_timezoneIdentifierToAbbreviation[$timezoneIdentifier];
        	$result = $this->_uit->getTimezonesForOffsets($offsets);
            $this->assertTrue(array_key_exists($timezoneAbbr, $result));
            $this->assertContains($timezoneIdentifier,$result[$timezoneAbbr]);
        }        
    }
    
    public function testGetTimezonesForPackedTimezoneInfo()
    {
        foreach ($this->_packedFixtrues as $timezoneIdentifier => $packedTimezoneInfo) {
        	$timezoneAbbr = $this->_timezoneIdentifierToAbbreviation[$timezoneIdentifier];
        	$result = $this->_uit->getTimezonesForPackedTimezoneInfo($packedTimezoneInfo);
            $this->assertTrue(array_key_exists($timezoneAbbr, $result));
            $this->assertContains($timezoneIdentifier, $result[$timezoneAbbr]);
            
            $result = $this->_uit->getTimezoneForPackedTimezoneInfo($packedTimezoneInfo);
            $this->assertEquals($timezoneAbbr, $result);
        }
    }
    
    public function testExpectedTimezoneOption()
    {
        foreach ($this->_fixtures as $timezoneIdentifier => $offsets) {
        	$timezoneAbbr = $this->_timezoneIdentifierToAbbreviation[$timezoneIdentifier];
            $this->_uit->setExpectedTimezone($timezoneIdentifier);
            $matchedTimezones = $this->_uit->getTimezonesForOffsets($offsets);
            $this->assertTrue(array_key_exists($timezoneAbbr, $matchedTimezones));
            $this->assertEquals($timezoneIdentifier, $matchedTimezones[$timezoneAbbr]);
        }
        
        
        //Africa/Algiers exceptionally belongs to CET insetad of WAT
        $packed = 'xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==';
        $expectedTimezone = 'Africa/Algiers';
        $expectedAbbr = 'CET';
        
        $this->_uit->setExpectedTimezone($expectedTimezone);
        $result = $this->_uit->getTimezoneForPackedTimezoneInfo($packed);
        $this->assertEquals($expectedAbbr, $result);
    }

    public function testUnknownOffsets()
    {
    	$this->setExpectedException('ActiveSync_TimezoneNotFoundException');
        $offsets = array(                                                                                                                            
                    'bias' => -600000,
                    'standardName' => '',
                    'standardYear' => 0,
                    'standardMonth' => 10,
                    'standardDayOfWeek' => 0,
                    'standardDay' => 5,
                    'standardHour' => 3,
                    'standardMinute' => 0,
                    'standardSecond' => 0,
                    'standardMilliseconds' => 0,
                    'standardBias' => 0,
                    'daylightName' => '',
                    'daylightYear' => 0,
                    'daylightMonth' => 3,
                    'daylightDayOfWeek' => 0,
                    'daylightDay' => 5,
                    'daylightHour' => 2,
                    'daylightMinute' => 0,
                    'daylightSecond' => 0,
                    'daylightMilliseconds' => 0,
                    'daylightBias' => -60                                                         
               );
        $matchedTimezones = $this->_uit->getTimezonesForOffsets($offsets);
    }
    
    public function testInvalidOffsets()
    {
        $this->setExpectedException('ActiveSync_Exception');
        //When specifiying standardOffsest then it is invalid provide empty daylight offsets and vice versa 
        $offsets = array(
                        'bias' => 1,
                        'standardName' => null,
                        'standardYear' => 0,
                        'standardMonth' => 1,
                        'standardDayOfWeek' => 2,
                        'standardDay' => 3,
                        'standardHour' => 4,
                        'standardMinute' => 5,
                        'standardSecond' => 6,
                        'standardMilliseconds' => 7,
                        'standardBias' => 8,
                        'daylightName' => null,
                        'daylightYear' => 0,
                        'daylightMonth' => 1,
                        'daylightDayOfWeek' => 0,
                        'daylightDay' => 0,
                        'daylightHour' => 0,
                        'daylightMinute' => 0,
                        'daylightSecond' => 0,
                        'daylightMilliseconds' => 0,
                        'daylightBias' => 0
                   );

        $this->_uit->getTimezonesForOffsets($offsets);

    }

}