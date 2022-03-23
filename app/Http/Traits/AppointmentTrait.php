<?php

namespace App\Http\Traits;

use App\Models\Appointment;
use App\Models\CareGroup;
use App\Models\Person;
use App\Models\QuestionCategory;
use Exception;

trait AppointmentTrait {

    use AgendaTrait;

    /**
     * @param Appointment $appointment
     * @param bool $isCaregiver
     * @return array
     * @throws Exception
     */
    public function prepare_appointment_data(Appointment $appointment, $isCaregiver= false) {

        $question = $appointment->getQuestion;
        $from = new \DateTime($appointment->DateTimeAppointmentFrom);
        $to = new \DateTime($appointment->DateTimeAppointmentTo);
        $cg = Person::find($appointment->AcceptedByPersonId);

        $visitors = [];

        if ($event = $question->getQuestionEvent) {
            $visitors = $event->getVisitors();
            $image = $event->getImage();
        }
        return [
            "type" => $question->AppointmentType,
            "careGiver"=>[
                'id'=>$cg->Id,
                "firstname"=>$cg->Firstname,
                "lastname"=>$cg->Lastname,
                "AvailableVideoCallLink" => $this->AvailableVideoCallLink($from, $to),
                "VideoCallURLForCG" => $appointment->VideoCallUrlCareGiver,
                "IsCareCaregiver" => $isCaregiver,
                "careGiverTeam" => CareGroup::find($event ? $event->TeamCareGroupId : $cg->TeamCareGroupId)->Name,
            ],
            "report" => $question->Report,
            "cancelReason" => $appointment->CancelReason,
            "datetimeFrom"=> strftime($from->format('r')),
            "datetimeTo"=> strftime($to->format('r')),
            "shouldReport" => $appointment->shouldReport($this->person),
            "id"=>$appointment->Id,
            "state"=> ($event && $to->getTimeStamp() < time()) ? "completed" : $appointment->State,
            "date" => $from->format("Y-m-d"),
            "question" => [
                "state" => ($event && $to->getTimeStamp() < time()) ?"Afgerond": $this->appointmentStateText($appointment),
                "isDC" => $question->IsDirectContact == 1 ? true : false,
                "id" => $question->Id,
                "remarks" => $question->Report,
                "category" => !empty(QuestionCategory::where('Id', $question->QuestionCategory->ParentId)->first()) ? QuestionCategory::where('Id', $question->QuestionCategory->ParentId)->first()->Name." > ".$question->QuestionCategory->Name : $question->QuestionCategory->Name,
                "type" => $question->AppointmentType,
                "event" => $event?[
                    "info" => $event,
                    "image" => ($image ? $event->getImage() : ''),
                    "visitors" => $visitors
                ]:false,
                "question" => $question->Description,
                "client" => [
                    "firstname" => $question->Person->Firstname,
                    "lastname" => $question->Person->Lastname,
                    "phone" => $question->Person->PhoneNumber,
                    "email" => $question->Person->Mail,
                    "team" => $question->Person->ClientTeam->Name,
                    "address" => [
                        "street" => $question->Person->AddressStreet,
                        "number" => $question->Person->AddressNumber,
                        "zipcode" => $question->Person->AddressZipcode,
                        "city" => $question->Person->AddressCity
                    ]
                ],
            ],
            "isDC" => $question->IsDirectContact == 1 ? true : false,
        ];
    }

    /**
     * Function appointmentStateText
     *
     * @param Appointment $appointment
     * @return string
     */
    public function appointmentStateText(Appointment $appointment): string
    {
        if ($appointment->shouldReport($this->person)) {
            if ($appointment->getQuestion->AppointmentType == 'event') {
                return __("states.appointment_completed");
            } else {
                return __("states.appointment_completed_and_should_report");
            }
        }
        return __("states.appointment_$appointment->State");
    }
}
