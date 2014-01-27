<?php

/**
 * @file
 * This class isn't strictly part of the REST exercise project, included here
 * for simplicity.
 */

namespace ChinthakaGodawita;

interface TimeHelperInterface {
    // $timezone = "Australia/Sydney"
    // $startOfWeek = 0 => sunday, 1 => monday etc
    public function __construct($timezone, $startOfWeek);
    public function getDayBucket($bias = 0);
    public function getMonthBucket($bias = 0);
    public function getYearBucket($bias = 0);
    public function getWeekBucket($bias = 0);
    public function getFiveMinutesBucket($bias = 0);
    public function getFifteenMinutesBucket($bias = 0);
    public function getThirdyMinutesBucket($bias = 0);
}

class TimeHelper implements TimeHelperInterface {
    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var \DateTimeZone
     */
    private $tz;

    /**
     * @var int
     */
    private $startOfWeek;

    /**
     * Constructor
     *
     * @param string $timezone
     *   The timezone to use, e.g. 'Australia/Sydney'.
     *
     * @param int $startOfWeek
     *   The day the week starts on, e.g. 0 = Sunday, 1 = Monday, etc.
     */
    public function __construct($timezone, $startOfWeek) {
        $this->tz = new \DateTimeZone($timezone);
        $this->date = new \DateTime('now', $this->tz);
        $this->startOfWeek = $startOfWeek;
    }

    /**
     * Returns the current day.
     *
     * @param integer $bias
     *   Whether or not to bias the return. To return days in the past, pass in
     *   a negative bias. Use a positive bias for days in the future.
     *
     * @return int
     *   Timestamp of the current day.
     */
    public function getDayBucket($bias = 0) {
        // Get a local copy of the date we can modify (no need to do a deep
        // copy, a shallow one will more than suffice).
        $date = clone $this->date;

        // Change date to midnight today.
        $date->modify("$bias days")
            ->modify('midnight');

        return $date->getTimestamp();
    }

    /**
     * Returns the first of the current month.
     *
     * @param integer $bias
     *   Whether or not to bias the return. To return months in the past, pass
     *   in a negative bias. Use a positive bias for months in the future.
     *
     * @return int
     *   Timestamp of the first of the current month.
     */
    public function getMonthBucket($bias = 0) {
        // Get a local copy of the date we can modify.
        $date = clone $this->date;
        $date->modify('first day of this month')
            ->modify("$bias months")
            ->modify('midnight');

        return $date->getTimestamp();
    }

    /**
     * Returns the first of the current year.
     *
     * @param integer $bias
     *   Whether or not to bias the return. To return years in the past, pass
     *   in a negative bias. Use a positive bias for years in the future.
     *
     * @return int
     *   Timestamp of the first of the current year.
     */
    public function getYearBucket($bias = 0) {
        // Get a local copy of the date we can modify.
        $date = clone $this->date;
        $date->modify('first day of this year')
            ->modify("$bias years")
            ->modify('midnight');

        return $date->getTimestamp();
    }

    /**
     * Returns the first of the current week.
     *
     * @param integer $bias
     *   Whether or not to bias the return. To return weeks in the past, pass
     *   in a negative bias. Use a positive bias for weeks in the future.
     *
     * @return int
     *   Timestamp of the first of the current week.
     */
    public function getWeekBucket($bias = 0) {
        // Get a local copy of the date we can modify.
        $date = clone $this->date;

        // Get the number of days till the start of the week.
        $diff = ($this->startOfWeek - $date->format('N'));
        if ($diff > 0) {
            $diff -= 7;
        }

        $date->modify("$diff days")
            ->modify("$bias weeks")
            ->modify('midnight');

        return $date->getTimestamp();
    }

    /**
     * Gets the closest five minute bucket to the current time, with bias if
     * required.
     *
     * @param integer $bias
     *   Whether or not to bias the return. To return weeks in the past, pass
     *   in a negative bias. Use a positive bias for weeks in the future.
     *
     * @return int
     *   The timestamp of the closest bucket.
     */
    public function getFiveMinutesBucket($bias = 0) {
        return getClosestMinutesBucket(5, $bias);
    }

    /**
     * Gets the closest fifteen minute bucket to the current time, with bias if
     * required.
     *
     * @param integer $bias
     *   Whether or not to bias the return. To return weeks in the past, pass
     *   in a negative bias. Use a positive bias for weeks in the future.
     *
     * @return int
     *   The timestamp of the closest bucket.
     */
    public function getFifteenMinutesBucket($bias = 0) {
        return getClosestMinutesBucket(15, $bias);
    }

    /**
     * Gets the closest thirty minute bucket to the current time, with bias if
     * required.
     *
     * @param integer $bias
     *   Whether or not to bias the return. To return weeks in the past, pass
     *   in a negative bias. Use a positive bias for weeks in the future.
     *
     * @return int
     *   The timestamp of the closest bucket.
     */
    public function getThirdyMinutesBucket($bias =  0) {
        return getClosestMinutesBucket(30, $bias);
    }

    /**
     * Get the closest bucket given a minute boundary.
     *
     * @param int $mins
     *   The minute boundary to use.
     * @param int $bias
     *   The value to bias the return by (if any).
     *
     * @return int
     *   The timestamp of the closest bucket.
     */
    private function getClosestMinutesBucket($mins, $bias = 0) {
        $seconds = $mins * 60;
        $timestamp = $this->date->getTimestamp();

        // Convert to fiften minutes since the epoch.
        $closest = floor($timestamp / $seconds) * $seconds;

        // Add bias on if required.
        $closest += $bias * $seconds;

        return $closest;
    }
}
