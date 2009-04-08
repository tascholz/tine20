<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Model of an rrule
 *
 * @package Calendar
 */
class Calendar_Model_Rrule extends Tinebase_Record_Abstract
{
    /**
     * supported freq types
     */
    const FREQ_DAILY     = 'DAILY';
    const FREQ_WEEKLY    = 'WEEKLY';
    const FREQ_MONTHLY   = 'MONTHLY';
    const FREQ_YEARLY    = 'YEARLY';

    /**
     * weekdays
     */
    const WDAY_SUNDAY    = 'SU';
    const WDAY_MONDAY    = 'MO';
    const WDAY_TUESDAY   = 'TU';
    const WDAY_WEDNESDAY = 'WE';
    const WDAY_THURSDAY  = 'TH';
    const WDAY_FRIDAY    = 'FR';
    const WDAY_SATURDAY  = 'SA';
    
    /**
     * maps weeksdays to digits
     */
    static $WEEKDAY_DIGIT_MAP = array(
        self::WDAY_SUNDAY     => 0,
        self::WDAY_MONDAY     => 1,
        self::WDAY_TUESDAY    => 2,
        self::WDAY_WEDNESDAY  => 3,
        self::WDAY_THURSDAY   => 4,
        self::WDAY_FRIDAY     => 5,
        self::WDAY_SATURDAY   => 6
    );
    
    const TS_HOUR = 3600;
    const TS_DAY  = 86400;
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Calendar';
    
    /**
     * validators
     *
     * @var array
     */
    protected $_validators = array(
        /*
        // tine record fields
        'id'                   => array('allowEmpty' => true,  'Alnum'),
        'created_by'           => array('allowEmpty' => true,  'Int'  ),
        'creation_time'        => array('allowEmpty' => true          ),
        'last_modified_by'     => array('allowEmpty' => true          ),
        'last_modified_time'   => array('allowEmpty' => true          ),
        'is_deleted'           => array('allowEmpty' => true          ),
        'deleted_time'         => array('allowEmpty' => true          ),
        'deleted_by'           => array('allowEmpty' => true          ),
        'seq'                  => array('allowEmpty' => true,  'Int'  ),
    
        'cal_event_id'         => array('allowEmpty' => true,  'Alnum'),
        */
    
        'freq'                 => array('allowEmpty' => true, 'InArray' => array(self::FREQ_DAILY, self::FREQ_MONTHLY, self::FREQ_WEEKLY, self::FREQ_YEARLY)),
        'interval'             => array('allowEmpty' => true, 'Int'   ),
        'byday'                => array('allowEmpty' => true, 'Regex' => '/^[\-0-9A_Z,]{2,}$/'),
        'bymonth'              => array('allowEmpty' => true, 'Int'   ),
        'wkst'                 => array('allowEmpty' => true, 'InArray' => array(self::WDAY_SUNDAY, self::WDAY_MONDAY, self::WDAY_TUESDAY, self::WDAY_WEDNESDAY, self::WDAY_THURSDAY, self::WDAY_FRIDAY, self::WDAY_SATURDAY)),
        'until'                => array('allowEmpty' => true          ),
        
        'organizer_tz'          => array('allowEmpty' => true         ),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        //'creation_time', 
        //'last_modified_time', 
        //'deleted_time', 
        'until',
    );
    
    /**
     * @var array supported rrule parts
     */
    protected $_rruleParts = array('freq', 'interval', 'until', 'wkst', 'byday', 'bymonth');
    
    /**
     * set from ical rrule string
     *
     * @param string $_rrule
     */
    public function setFromString($_rrule)
    {
        $parts = explode(';', $_rrule);
        foreach ($parts as $part) {
            list($key, $value) = explode('=', $part);
            $part = strtolower($key);
            if (! in_array($part, $this->_rruleParts)) {
                throw new Tinebase_Exception_UnexpectedValue("$part is not a known rrule part");
            }
            $this->$part = $value;
        }
    }
    
    /**
     * returns a ical rrule string
     *
     * @return string
     */
    public function __toString()
    {
        $stringParts = array();
        
        foreach ($this->_rruleParts as $part) {
            if (!empty($this->$part)) {
                $value = $this->$part instanceof Zend_Date ? $this->$part->toString(self::ISO8601LONG) : $this->$part;
                $stringParts[] = strtoupper($part) . '=' . $value;
            }
        }
        
        return implode(';', $stringParts);
    }
    
    /**
     * set properties and convert them into internal representatin on the fly
     *
     * @param string $_name
     * @param mixed $_value
     * @return void
     */
    public function __set($_name, $_value) {
        switch ($_name) {
            case 'until':
                if (! empty($_value)) {
                    if ($_value instanceof Zend_Date) {
                        $this->_properties['until'] = $_value;
                    } else {
                        $this->_properties['until'] = new Zend_Date($_value, self::ISO8601LONG);
                    }
                }
                break;
            default:
                parent::__set($_name, $_value);
                break;
        }
    }
    
    /************************* recurance computation *****************************/
    
    /**
     * Computes the recurance set of the given event leaving out $_event->exdate and $_exceptions
     * 
     * @todo respect rrule_until!
     *
     * @param  Calendar_Model_Event         $_event
     * @param  Tinebase_Record_RecordSet    $_exceptions
     * @param  Zend_Date                    $_from
     * @param  Zend_Date                    $_until
     * @return Tinebase_Record_RecordSet
     */
    public static function computeRecuranceSet($_event, $_exceptions, $_from, $_until)
    {
        $rrule = new Calendar_Model_Rrule();
        $rrule->setFromString($_event->rrule);
        
        $_exceptionRecurIds = self::getExceptionsRecurIds($_event, $_exceptions);
        $recurSet = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        switch ($rrule->freq) {
            case self::FREQ_DAILY:
                self::_computeRecurDaily($_event, $rrule, $_exceptionRecurIds, $_from, $_until, $recurSet);
                break;
                
            case self::FREQ_WEEKLY:
                $dailyrrule = clone ($rrule);
                $dailyrrule->freq = self::FREQ_DAILY;
                $dailyrrule->interval = 7 * $rrule->interval;
                
                foreach (explode(',', $rrule->byday) as $recurWeekDay) {
                    $baseEvent = clone $_event;
                    
                    if($baseEvent->dtstart->get(Zend_Date::WEEKDAY_DIGIT) != self::$WEEKDAY_DIGIT_MAP[$recurWeekDay]) {
                        $baseEvent->dtstart = self::getNextWday($baseEvent->dtstart, $recurWeekDay);
                        $baseEvent->dtend   = self::getNextWday($baseEvent->dtend,   $recurWeekDay);
                    }
                    
                    self::_computeRecurDaily($baseEvent, $dailyrrule, $_exceptionRecurIds, $_from, $_until, $recurSet);
                }
                break;
                
            case self::FREQ_MONTHLY:
                break;
            case self::FREQ_YEARLY:
                break;
                
        }
        
        return $recurSet;
    }
    
    /**
     * returns array of exception recurids
     *
     * @param  Calendar_Model_Event         $_event
     * @param  Tinebase_Record_RecordSet    $_exceptions
     * @return array
     */
    public static function getExceptionsRecurIds($_event, $_exceptions)
    {
        $recurIds = $_exceptions->recurid;
        
        if (! empty($_event->exdate)) {
            $exdates = is_array($_event->exdate) ? $_event->exdate : array($_event->exdate);
            foreach ($exdates as $exdate) {
                $recurIds[] = $_event->uid . '-' . $exdate->toString(Tinebase_Record_Abstract::ISO8601LONG);
            }
        }
        return array_values($recurIds);
    }
    
    /**
     * gets an cloned event to be used for new recur events
     * 
     * @param  Calendar_Model_Event         $_event
     * @return Calendar_Model_Event         $_event
     */
    public static function cloneEvent($_event)
    {
        $clone = clone $_event;
        $clone->setId(NULL);
        unset($clone->exdate);
        unset($clone->rrule);
        unset($clone->rrule_until);
        
        return $clone;
    }
    
    /**
     * computes daily recuring events and inserts them into given $_recurSet
     *
     * @param Calendar_Model_Event      $_event
     * @param Calendar_Model_Rrule      $_rrule
     * @param array                     $_exceptionRecurIds
     * @param Zend_Date                 $_from
     * @param Zend_Date                 $_until
     * @param Tinebase_Record_RecordSet $_recurSet
     * @return void
     */
    protected static function _computeRecurDaily($_event, $_rrule, $_exceptionRecurIds, $_from, $_until, $_recurSet)
    {
        $computationStartDate = clone $_event->dtstart;
        $computationEndDate   = ($_rrule->until instanceof Zend_Date && $_until->isLater($_rrule->until)) ? $_rrule->until : $_until;
        
        // if dtstart is before $_from, we compute the offset where to start our calculations
        if ($_event->dtstart->isEarlier($_from)) {
            $computationOffsetDays = floor(($_from->getTimestamp() - $_event->dtstart->getTimestamp()) / (self::TS_DAY * $_rrule->interval)) * $_rrule->interval;
            $computationStartDate->add(new Zend_Date($computationOffsetDays * self::TS_DAY, Zend_Date::TIMESTAMP));
        }
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' $computationStartDate: ' . $computationStartDate);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' $computationEndDate: ' . $computationEndDate);
        
        $eventLength = clone $_event->dtend;
        $eventLength->sub($_event->dtstart);
        
        while ($computationStartDate->addDay($_rrule->interval)->isEarlier($computationEndDate)) {
            $recurEvent = self::cloneEvent($_event);
            $recurEvent->dtstart = clone ($computationStartDate);
            
            $originatorsDtstart = clone $recurEvent->dtstart;
            $originatorsDtstart->setTimezone($_event->originator_tz);
            $recurEvent->dtstart->sub($originatorsDtstart->get(Zend_Date::DAYLIGHT) ? 1 : 0, Zend_Date::HOUR);
            
            // we calculate dtend from the event length, as events during a dst boundary could get dtend less than dtstart otherwise 
            $recurEvent->dtend = clone $recurEvent->dtstart;
            $recurEvent->dtend->add($eventLength);
            
            $recurEvent->recurid = $recurEvent->uid . '-' . $recurEvent->dtstart->toString(Tinebase_Record_Abstract::ISO8601LONG);
            
            if (! in_array($recurEvent->recurid, $_exceptionRecurIds)) {
                $_recurSet->addRecord($recurEvent);
            }
        }
    }
    
    /**
     * gets next occourance of wday
     *
     * @param  Zend_Date  $_date
     * @param  int|string $_wday
     * @return Zend_Date
     */
    public static function getNextWday($_date, $_wday)
    {
        $wdayDigit = is_int($_wday) ? $_wday : self::$WEEKDAY_DIGIT_MAP[$_wday];
        
        $next = clone $_date;
        $offset = $_date->get(Zend_Date::WEEKDAY_DIGIT) - $wdayDigit;
        if ($offset >= 0) {
            $next->addDay(7 - $offset);
        } else {
            $next->addDay(abs($offset));
        }
        
        return $next;
    }
}