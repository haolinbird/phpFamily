<?php
class JMTimezone
{
    const INVALID_DATETIME = '0000-00-00 00:00:00';
    
    static public function DifferenceToSeconds($v)
    {
        $sign = $v >= 0 ? 1 : -1;
        $v = abs($v);
        return $sign * ( ((int) ($v/100)) * 60 + ($v % 100) ) * 60;
    }

    static public function GmtTimestamp()
    {
        return time();
    }
    static public function ServerDatetimeToTimestamp($s, $now = null)
    {
        if($now === null)
            return strtotime($s);
        return strtotime($s, $now);
    }

    static public function ServerDatetimeToGmt($s)
    {
        return date('Y-m-d H:i:s', self::ServerDatetimeToTimestamp($s) - self::DifferenceToSeconds(TIMEZONE_SERVER_DIFFERENCE));
    }

    static public function ServerDatetimeFormat($format, $time = null, $relative = null)
    {
        if($time === null)
            $time = strtotime(date('Y-m-d H:i:s') . ' ' . $relative);
        else if(is_numeric($time))
            $time = strtotime(date('Y-m-d H:i:s', $time) . ' ' . $relative);
        else
            $time = strtotime($time . ' ' . $relative);

        if($time <= 0)
            return self::INVALID_DATETIME;

        return date($format, $time);
    }
    /**
     * @static
     * @param mixed $time datetime string or timestamp
     * @param string $relative (('sec' | 'second' | 'min' | 'minute' | 'hour' | 'day' | 'fortnight' | 'forthnight' | 'month' | 'year') 's'?) | 'weeks' | daytext
     * @return string Y-m-d H:i:s (YYYY-MM-DD hh:mm:ss)
     */
    static public function ServerDatetime($time = null, $relative = null)
    {
        return self::ServerDatetimeFormat('Y-m-d H:i:s', $time, $relative);
    }
	
	protected $tzdiff;
	public function __construct($tzdiff = 0) 
	{
		$this->tzdiff = $tzdiff;
	}
	
	public function setDifference($tzdiff)
	{
		$this->tzdiff = $tzdiff;
	}
	
	public function fromServerDatetime($s, $format = null)
	{
		$t = self::ServerDatetimeToTimestamp($s) - self::DifferenceToSeconds(TIMEZONE_SERVER_DIFFERENCE) + self::DifferenceToSeconds($this->tzdiff);

		if($format == 'date') $format = 'Y-m-d';
		if($format == 'time') $format = 'H:i:s';
		if(empty($format) || $format == 'datetime') $format = 'Y-m-d H:i:s';

		return date($format, $t);
	}
}
