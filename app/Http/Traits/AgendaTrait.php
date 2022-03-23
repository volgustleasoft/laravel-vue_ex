<?php

namespace App\Http\Traits;

use App\Models\Appointment;
use App\Models\InloopEventTimeSlot;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\Person;
use App\Models\Question;
use Carbon\Carbon;

trait AgendaTrait {

    /**
     * Video Call availability check for the Caregiver and the Client
     * @param $getDateFrom
     * @param $getDateTo
     * @return bool
     */
    public  function AvailableVideoCallLink($getDateFrom, $getDateTo)
    {
        $dateTime = Carbon::now();
        $dateTimeFrom = Carbon::createFromFormat("Y-m-d H:i:s.u", is_object($getDateFrom) ? $getDateFrom->format("Y-m-d H:i:s.u") : $getDateFrom );
        $dateTimeTo = Carbon::createFromFormat("Y-m-d H:i:s.u", is_object($getDateTo) ? $getDateTo->format("Y-m-d H:i:s.u") : $getDateTo);

        $intervalFrom = $dateTime->diff($dateTimeFrom);
        $intervalTo = $dateTime->diff($dateTimeTo);
        $TimeToShowLinkFrom = date_create('@0')->add($intervalFrom)->getTimestamp();
        $TimeToShowLinkTo = date_create('@0')->add($intervalTo)->getTimestamp();

        if ($TimeToShowLinkFrom - 15 * 60 <= 0 && $TimeToShowLinkTo >= 0) {
            return true;
        }
        return false;
    }

    /**
     * Manager current team getter
     * @param $managerTeams
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Session\SessionManager|\Illuminate\Session\Store|mixed|void
     */
    public function getManagerCurrentTeamId($managerTeams)
    {
        if(! empty($managerTeams)) {
            if ($this->request->has('group_id') and $managerTeams->contains('Id', $this->request->group_id)) {
                $this->request->session()->put('care_group_id', $this->request->group_id);
                return $this->request->group_id;
            } elseif(! empty(session('care_group_id'))) {
                return session('care_group_id');
            } else {
                return $managerTeams->first()->Id;
            }
        }
    }

    /**
     * Caregiver Appontment cancel action helper
     * @param Appointment $appointment
     * @param $reason
     * @return bool
     */
    public function cancelAppointment(Appointment $appointment, $reason)
    {
        $Question = $appointment->getQuestion;
        $appointment->State = "canceledByCaregiver";
        $appointment->CancelReason = $reason;
        $result = $appointment->save();
        if ($Question->AppointmentType == 'event') {
            $Question->State = 'completed';
            $result = $Question->save();
        } else {
            $Question->CreateQuestionApointment();
        }
        return $result;
    }

    /**
     * Client action for cancel Inloop Appointment
     * @param Question $question
     * @return bool
     * @throws \Exception
     */
    public function cancelInloopByClient(Question $question)
    {
        $timeSlot = InloopEventTimeslot::where(['QuestionId' => $question->Id])->first();
        $inloopEvent = $timeSlot->getInloopEvent;
        $timeSlot->State = 'cancelByClient';
        $result = $timeSlot->save();
        $client = $question->Person;
        $dateTime = new \DateTime($inloopEvent->Date.''.$timeSlot->StartTime);
        $dateTime->setTimezone(new \DateTimeZone('Europe/Amsterdam'));
        $careGiver = $inloopEvent->getPerson;
        $messageTemplate = MessageTemplate::where('Name', '=', 'cancelinloopcaregiver_v1')->firstOrFail();
        $message = Message::create([
            "MessageTemplateId" => $messageTemplate->Id,
            "PersonId" => $careGiver->Id,
            "Variables" => json_encode([
                "CLIENT" => $client->Firstname . " " . $client->Lastname,
                "DATE" => $dateTime->format("d-m"),
                "TIME" => $dateTime->format("H:i")
            ]),
            "Medium" => "SMS"
        ]);

        $message->send();
        $formattedDateTime = $dateTime->format("d-m-Y H:i");

        postSlackMessage("Appointment for question *" . $inloopEvent->Id . "* by client *" . $client->Id . "* at *" . $formattedDateTime . "* is canceled by *client*. Closed the question.", ":nope:");
        $timeSlot->CreateInloopTimeSlot();
        $question->State = 'completed';
        $result = $question->save();
        return $result;
    }

    /**
     * Report Appointment for Caregiver
     * @param Appointment $Appointment
     * @param $didHappen
     * @param $report
     * @param $failReason
     * @return bool
     */
    public function completeAppointment(Appointment $Appointment, $didHappen, $report, $failReason){
        $slackState = '';
        $Question = $Appointment->getQuestion;

        if($didHappen=='yes'){
            $Appointment->State = "completed";
            $Question->Report = $report;
            $slackState = "success";
        }else if($didHappen=='no_other'){
            $Appointment->State = "failedNoOther";
            $Appointment->CancelReason = $failReason;
            $slackState = "failed with other reason";
        }else if($didHappen=='no_notAvailable'){
            $Appointment->State = "failedNoShow";
            $slackState = "failed because of no show";
        }
        $result = $Appointment->save();
        $Question->State = "completed";
        $Question->save();
        postSlackMessage("Report submitted for question *".$Question->Id."* appointment *".$Appointment->Id."* by caregiver *".$Appointment->AcceptedByPersonId."*. Ended with status *".$slackState."*.", ":pencil:", null,false);

        return $result;
    }

    /**
     * Cancel question funtionality for Client and Caregiver
     * @param Question $question
     * @param $AppointmentId
     * @param $cancelBy
     * @return bool
     */
    public function cancelQuestion(Question $question, $AppointmentId, $cancelBy = false)
    {
        if ($cancelBy == 'client'){
            $Appointment = Appointment::where(['Id'=> $AppointmentId, 'QuestionId' => $question->Id])->first();
            $question->State = "Completed";
            $question->save();
            $Appointment->State = "canceledByClient";
            $Appointment->save();
        } elseif ($cancelBy == 'caregiver'){
            $Appointment = Appointment::where(['Id'=> $AppointmentId, 'QuestionId' => $question->Id])->first();
            $Appointment->State= 'canceledByCaregiver';
            $Appointment->save();
            $question->CreateQuestionApointment();
        }
        return true;
    }

    /**
     * Client-Caregiver report question
     * @param Question $question
     * @param $didHappen
     * @param $report
     * @param $failReason
     * @return void
     */
    public function reportQuestion(Question $question, $didHappen, $report, $failReason)
    {
        $Appointment = Appointment::where(['State'=>'created', 'QuestionId' => $question->Id ])->first();
        $slackState= '';

        if($didHappen=='yes'){
            $Appointment->State="completed";
            $question->Report = $report;
            $slackState = "success";
        }else if($didHappen=='no_other'){
            $Appointment->State = "failedNoOther";
            $Appointment->CancelReason =$failReason;
            $slackState = "failed with other reason";
        }else if($didHappen=='no_notAvailable'){
            $Appointment->State= "failedNoShow";
            $slackState = "failed because of no show";
        }
        $question->State= "completed";
        $question->save();
        $Appointment->save();
        postSlackMessage("Report submitted for question *".$question->Id."* appointment *".$Appointment->Id."* by caregiver *".$this->person->Id."*. Ended with status *".$slackState."*.", ":pencil:", null,false);
    }
}
