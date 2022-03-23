<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class DateTimeValidate implements Rule
{

    private $timeStart = 0;
    private $timeEnd = 0;
    private $minimumLength = 30;
    private $meetingLimitMonthsAhead = 6;
    private $typeError = '';

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($timeStart, $timeEnd)
    {
        $this->timeStart = getUTC($timeStart)->getTimestamp();
        $this->timeEnd = getUTC($timeEnd)->getTimestamp();
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if(! $this->isInvalidDate()) {
            return false;
        }
        if(! $this->isEndsBeforeStarted()) {
            return false;
        }
        if($this->isLessMinimumLength()) {
            return false;
        }
        if(! $this->limitMeetingMonthsDate()) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->getErrors()[$this->typeError];
    }

    private function isInvalidDate() {
        if($this->timeStart < 0 or
            ! checkdate(date('m', $this->timeStart), date('d', $this->timeStart), date('Y', $this->timeStart))
                                               or $this->timeStart <= time() ) {
            $this->typeError = 'invalidDate';
            return false;
        }
        return true;
    }

    private function isEndsBeforeStarted() {
        if($this->timeEnd - $this->timeStart < 0) {
            $this->typeError = 'endBeforeStart';
            return false;
        }
        return true;
    }

    private function isLessMinimumLength() {
        if($this->timeEnd - $this->timeStart < $this->minimumLength * 60) {
            $this->typeError = 'lessMinimumLength';
            return true;
        }
        return false;
    }

    private function limitMeetingMonthsDate() {
        $limitMeetingMonthsDate = date("Y-m-d", strtotime("+" . $this->meetingLimitMonthsAhead. " months"));
        if ($this->timeStart > strtotime($limitMeetingMonthsDate)) {
            $this->typeError = 'maximumFutureDate';
            return false;
        }
        return true;
    }

    private function getErrors() {
        return [
            'invalidDate' => 'Je hebt een ongeldige datum geselecteerd',
            'endBeforeStart' => 'Aanvangstijd is later dan eindtijd geselecteerd',
            'lessMinimumLength' => 'De bijeenkomst heeft een minimum duur van ' . $this->minimumLength . ' minuten',
            'maximumFutureDate' => 'De afspraak moet uiterlijk ' . $this->meetingLimitMonthsAhead .' maanden later plaatsvinden',
        ];
    }
}
