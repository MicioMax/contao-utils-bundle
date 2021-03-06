<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\UtilsBundle\Date;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\StringUtil;

class DateUtil
{
    /** @var ContaoFrameworkInterface */
    protected $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Get the timestamp based on input date, no matter input is timestamp or string date.
     *
     * @param string|int|null|\DateTime $date              The input date/timestamp/insertTag
     * @param bool                      $replaceInsertTags Disable/enable {{date::}} insertTag support
     * @param string|null               $timezone          A valid timezone from DateTimeZone::ALL, if provided the timezone offset will be added to the timestamp
     *
     * @return int The integer timestamp presentation of the input date with added timezone offset
     */
    public function getTimeStamp($date = null, $replaceInsertTags = true, $timezone = null)
    {
        if (null === $date) {
            return 0;
        }

        if ($date instanceof \DateTime) {
            $timezone ? $date->setTimezone(new \DateTimeZone($timezone)) : null;

            return $date->getTimestamp();
        }

        if (true === $replaceInsertTags) {
            $date = Controller::replaceInsertTags($date, false);
        }

        if (is_numeric($date)) {
            $dateTime = new \DateTime(null, $timezone ? new \DateTimeZone($timezone) : null);
            $dateTime->setTimestamp($date);

            return $dateTime->getTimestamp();
        }

        if (false !== ($dateTime = strtotime($date))) {
            $dateTime = new \DateTime($date, $timezone ? new \DateTimeZone($timezone) : null);

            return $dateTime->getTimestamp();
        }

        return 0;
    }

    public function getTimePeriodInSeconds($timePeriod)
    {
        $timePeriod = StringUtil::deserialize($timePeriod, true);

        if (!isset($timePeriod['unit']) || !isset($timePeriod['value'])) {
            return null;
        }

        $factor = 1;

        switch ($timePeriod['unit']) {
            case 'm':
                $factor = 60;

                break;

            case 'h':
                $factor = 60 * 60;

                break;

            case 'd':
                $factor = 24 * 60 * 60;

                break;
        }

        return $timePeriod['value'] * $factor;
    }

    /**
     * Format a php date formate pattern to an RFC3339 compliant format.
     *
     * @param string $format The php date format (see: http://php.net/manual/de/function.date.php#refsect1-function.date-parameters)
     *
     * @return string The RFC3339 compliant format (see: http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Time-Format-Syntax or http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Field_Symbol_Table)
     */
    public function transformPhpDateFormatToRFC3339(string $format): string
    {
        $mapping = [
            'd' => 'dd',  //Day of the month, 2 digits with leading zeros (01 to 31)
            'D' => 'E', // A textual representation of a day, three letters (Mon through Sun)
            'j' => 'd', // Day of the month without leading zeros (1 to 31)
            'l' => 'EEEE', // A full textual representation of the day of the week (Sunday through Saturday)
            'N' => 'd', // ISO-8601 numeric representation of the day of the week (added in PHP 5.1.0) (1 (for Monday) through 7 (for Sunday))
            'S' => '', // Not supported yet: English ordinal suffix for the day of the month, 2 characters (st, nd, rd or th. Works well with j)
            'w' => 'e', // Numeric representation of the day of the week (0 (for Sunday) through 6 (for Saturday))
            'z' => 'D', // The day of the year (starting from 0) (0 through 365)
            'W' => 'w', // ISO-8601 week number of year, weeks starting on Monday (Example: 42 (the 42nd week in the year))
            'F' => 'MMMM', // A full textual representation of a month, such as January or March (January through December)
            'm' => 'MM', // Numeric representation of a month, with leading zeros (01 through 12)
            'M' => 'MMM', // A short textual representation of a month, three letters (Jan through Dec)
            'n' => 'M', // Numeric representation of a month, without leading zeros (1 through 12)
            't' => '', // Not supported yet: Number of days in the given month (28 through 31)
            'L' => '', // Not supported yet: Whether it's a leap year (1 if it is a leap year, 0 otherwise.)
            'o' => 'Y', // ISO-8601 week-numbering year. This has the same value as Y, except that if the ISO week number (W) belongs to the previous or next year, that year is used instead. (added in PHP 5.1.0) (Examples: 1999 or 2003)
            'Y' => 'yyyy', // A full numeric representation of a year, 4 digits (Examples: 1999 or 2003)
            'y' => 'yy', // A two digit representation of a year (Examples: 99 or 03)
            'a' => '', // Not supported yet: Lowercase Ante meridiem and Post meridiem (am or pm)
            'A' => 'a', // Uppercase Ante meridiem and Post meridiem (AM or PM)
            'B' => '', // Not supported yet: Swatch Internet time (000 through 999)
            'g' => 'h', // 12-hour format of an hour without leading zeros (1 through 12)
            'G' => 'H', // 24-hour format of an hour without leading zeros (0 through 23)
            'h' => 'hh', // 12-hour format of an hour with leading zeros (01 through 12)
            'H' => 'HH', // 24-hour format of an hour with leading zeros (00 through 23)
            'i' => 'mm', // Minutes with leading zeros (00 to 59)
            's' => 'ss', // Seconds, with leading zeros (00 to 59)
            'u' => '', // Not supported yet: Microseconds (added in PHP 5.2.2). Note that date() will always generate 000000 since it takes an integer parameter, whereas DateTime::format() does support microseconds if DateTime was created with microseconds. (Example: 654321)
            'v' => '', // Not supported yet: Milliseconds (added in PHP 7.0.0). Same note applies as for u. (Example: 654)
            'e' => 'VV', // Timezone identifier (added in PHP 5.1.0) (Examples: UTC, GMT, Atlantic/Azores)
            'I' => '', // Not supported yet: Whether or not the date is in daylight saving time (1 if Daylight Saving Time, 0 otherwise.)
            'O' => 'xx', // Difference to Greenwich time (GMT) in hours (Example: +0200)
            'P' => 'xxx', // Difference to Greenwich time (GMT) with colon between hours and minutes (added in PHP 5.1.3) (Example: +02:00)
            'T' => '',  // Not supported yet: Timezone abbreviation	(Examples: EST, MDT)
            'Z' => '', // Not supported yet: Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive. (-43200 through 50400)
            'c' => "yyyy-MM-dd'T'HH:mm:ssxxx", // ISO 8601 date (added in PHP 5) (2004-02-12T15:19:21+00:00)
            'r' => '', // Not supported yet: » RFC 2822 formatted date (Example: Thu, 21 Dec 2000 16:01:07 +0200)
            'U' => '', // Not supported yet: Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
        ];

        $chunks = str_split($format);

        foreach ($chunks as $k => $v) {
            if (!isset($mapping[$v])) {
                continue;
            }

            $chunks[$k] = $mapping[$v];
        }

        return preg_replace('/([a-zA-Z])/', '$1', implode('', $chunks));
    }

    /**
     * Format a php date formate pattern to an ISO8601 compliant format.
     *
     * @param string $format The date format (e.g. "d.m.y H:i")
     *
     * @return string The ISO8601 compliant format (see: https://de.wikipedia.org/wiki/ISO_8601)
     */
    public function transformPhpDateFormatToISO8601(string $format): string
    {
        $mapping = [
            'd' => 'DD',  //Day of the month, 2 digits with leading zeros (01 to 31)
            'D' => 'D', // A textual representation of a day, three letters (Mon through Sun)
            'j' => 'd', // Day of the month without leading zeros (1 to 31)
            'l' => 'DD', // A full textual representation of the day of the week (Sunday through Saturday)
            'N' => '', // ISO-8601 numeric representation of the day of the week (added in PHP 5.1.0) (1 (for Monday) through 7 (for Sunday))
            'S' => '', // Not supported yet: English ordinal suffix for the day of the month, 2 characters (st, nd, rd or th. Works well with j)
            'w' => '', // Numeric representation of the day of the week (0 (for Sunday) through 6 (for Saturday))
            'z' => 'o', // The day of the year (starting from 0) (0 through 365)
            'W' => '', // ISO-8601 week number of year, weeks starting on Monday (Example: 42 (the 42nd week in the year))
            'F' => 'MM', // A full textual representation of a month, such as January or March (January through December)
            'm' => 'MM', // Numeric representation of a month, with leading zeros (01 through 12)
            'M' => 'M', // A short textual representation of a month, three letters (Jan through Dec)
            'n' => 'm', // Numeric representation of a month, without leading zeros (1 through 12)
            't' => '', // Not supported yet: Number of days in the given month (28 through 31)
            'L' => '', // Not supported yet: Whether it's a leap year (1 if it is a leap year, 0 otherwise.)
            'o' => '', // ISO-8601 week-numbering year. This has the same value as Y, except that if the ISO week number (W) belongs to the previous or next year, that year is used instead. (added in PHP 5.1.0) (Examples: 1999 or 2003)
            'Y' => 'YYYY', // A full numeric representation of a year, 4 digits (Examples: 1999 or 2003)
            'y' => 'y', // A two digit representation of a year (Examples: 99 or 03)
            'a' => '', // Not supported yet: Lowercase Ante meridiem and Post meridiem (am or pm)
            'A' => '', // Uppercase Ante meridiem and Post meridiem (AM or PM)
            'B' => '', // Not supported yet: Swatch Internet time (000 through 999)
            'g' => '', // 12-hour format of an hour without leading zeros (1 through 12)
            'G' => '', // 24-hour format of an hour without leading zeros (0 through 23)
            'h' => '', // 12-hour format of an hour with leading zeros (01 through 12)
            'H' => 'HH', // 24-hour format of an hour with leading zeros (00 through 23)
            'i' => 'mm', // Minutes with leading zeros (00 to 59)
            's' => 'ss', // Seconds, with leading zeros (00 to 59)
            'u' => '', // Not supported yet: Microseconds (added in PHP 5.2.2). Note that date() will always generate 000000 since it takes an integer parameter, whereas DateTime::format() does support microseconds if DateTime was created with microseconds. (Example: 654321)
            'v' => '', // Not supported yet: Milliseconds (added in PHP 7.0.0). Same note applies as for u. (Example: 654)
            'e' => '', // Timezone identifier (added in PHP 5.1.0) (Examples: UTC, GMT, Atlantic/Azores)
            'I' => '', // Not supported yet: Whether or not the date is in daylight saving time (1 if Daylight Saving Time, 0 otherwise.)
            'O' => '', // Difference to Greenwich time (GMT) in hours (Example: +0200)
            'P' => 'z', // Difference to Greenwich time (GMT) with colon between hours and minutes (added in PHP 5.1.3) (Example: +02:00)
            'T' => '',  // Not supported yet: Timezone abbreviation	(Examples: EST, MDT)
            'Z' => '', // Not supported yet: Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive. (-43200 through 50400)
            'c' => "YYYY-MM-DD'T'HH:mm:ssz", // ISO 8601 date (added in PHP 5) (2004-02-12T15:19:21+00:00)
            'r' => '', // Not supported yet: » RFC 2822 formatted date (Example: Thu, 21 Dec 2000 16:01:07 +0200)
            'U' => '', // Not supported yet: Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
        ];

        $chunks = str_split($format);

        foreach ($chunks as $k => $v) {
            if (!isset($mapping[$v])) {
                continue;
            }

            $chunks[$k] = $mapping[$v];
        }

        return preg_replace('/([a-zA-Z])/', '$1', implode('', $chunks));
    }

    /**
     * transfer a given timestamp to a gmt timestamp at midnight.
     *
     * @param int $tstamp
     *
     * @return int
     */
    public function getGMTMidnightTstamp(int $tstamp)
    {
        $date = new \DateTime(date('Y-m-d', $tstamp));
        $date->setTimezone(new \DateTimeZone('GMT'));
        $date->setTime(0, 0, 0);

        return $date->getTimestamp();
    }
}
