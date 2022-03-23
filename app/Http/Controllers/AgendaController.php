<?php

namespace App\Http\Controllers;

use App\Http\Traits\AgendaTrait;
use App\Http\Traits\DCTrait;
use App\Http\Traits\WorkingHoursTrait;
use App\Http\Traits\AppointmentTrait;
use App\Http\Traits\InloopTrait;
use App\Models\Appointment;
use App\Models\CareGroup;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\Person;
use App\Models\Question;
use App\Models\QuestionEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class AgendaController extends Controller
{
    use AppointmentTrait, InloopTrait, DCTrait, WorkingHoursTrait, AgendaTrait;

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function ajaxPostHandler(Request $request) {
        return response()->json(call_user_func(array($this, $request->post('action')), $request));
    }

    public function ajaxHandler(Request $request) {
        return response()->json(call_user_func(array($this, $request->input('action'))));
    }


    /**
     * Client cancel Event action
     * @return array
     */
    public function cancelEvent() {

        $questionEvent = QuestionEvent::where('QuestionId', $this->request->post('id'))->first();

        $result = $questionEvent->cancelParticipant($this->person->Id);

        return ['success' => $result];
    }

    /**
     * Client-Caregiver report action
     * @param Request $request
     * @return void
     */
    public function reportQuestionAction(Request $request)
    {
        $Question = Question::find($request->questionId);
        $Appointment = Appointment::find($request->appointmentId);
        if ($Appointment->AcceptedByPersonId == $this->person->Id){
            $reason = $request->didHappen;
            $failReason = $report = "";
            if ($reason === "no_other") {
                $failReason = $request->report;
            } else {
                $report = $request->report;
            }

            $this->reportQuestion($Question, $reason, $report, $failReason);
            return redirect('/agenda/client');
        }
    }

    /**
     *
     * Client Cansel Question action
     * @param Request $request
     * @return false[]
     */
    public function cancelQuestionAction(Request $request) {

        $question = Question::find($this->request->post('questionId'));
        $AskedByPerson = Person::find($this->request->post('personId'));
        $Appointment = Appointment::find($this->request->post('appointmentId'));
        $result = false;

        if ($question->PersonId == $AskedByPerson->Id && $this->person->Id == $Appointment->AcceptedByPersonId){
            $result = $this->cancelQuestion($question, $Appointment->Id, 'caregiver');
            $messageTemplate = MessageTemplate::where('Name', '=', 'canceledappointmentclient_v1')->firstOrFail();
            $dateTime = getAmsterdamTimeZone();
            $dateTime->setTimestamp(strtotime($question->Appointments()->first()->DateTimeAppointmentFrom));

            $message = Message::create([
                "MessageTemplateId" => $messageTemplate->Id,
                "PersonId" => $AskedByPerson->Id,
                "Variables" => json_encode([
                    "CAREGIVER"=>$this->person->Firstname." ".$this->person->Lastname,
                    "DATETIME"=>$dateTime->format("d-m H:i")
                ]),
                "Medium" => "SMS"
            ]);

            $message->send();

            postSlackMessage("Sent message *".$messageTemplate->Name."* to person *".$AskedByPerson->Id."* via *SMS*", ":envelope:");
            postSlackMessage("Appointment for question *".$question->Id."* by client *".$this->person->Id."* at *".$dateTime->format("d-m-Y H:i")."* is canceled by *caregiver*. Rescheduling now.", ":nope:");
            rtestCareAnalytics("message_sms", $AskedByPerson->Id, ['SubEvent'=>"canceledappointmentclient_v1"]);

        } elseif ($question->PersonId == $AskedByPerson->Id) {
          $result =$this->cancelQuestion($question, $Appointment->Id, 'client');

            // Stop DC flow
            $this->stopDirectContactFlow($question->Id);

            if(!empty($Appointment->AcceptedByPersonId)){
                $Caregiver = Person::find($Appointment->AcceptedByPersonId);
                $messageTemplate = MessageTemplate::where('Name', '=', 'canceledappointmentcaregiver_v1')->firstOrFail();
                $dateTime = getAmsterdamTimeZone();
                $dateTime->setTimestamp(strtotime($question->Appointments()->first()->DateTimeAppointmentFrom));

                $message = Message::create([
                    "MessageTemplateId" => $messageTemplate->Id,
                    "PersonId" => $Caregiver->Id,
                    "Variables" => json_encode([
                        "CLIENT"=>$AskedByPerson->Firstname." ".$AskedByPerson->Lastname,
                        "DATETIME"=>$dateTime->format("d-m H:i")
                    ]),
                    "Medium" => "SMS"
                ]);

                $message->send();
                postSlackMessage("Sent message *".$messageTemplate->Name."* to person *".$Caregiver->Id."* via *SMS*", ":envelope:");
            }
            $dateTime = !empty($dateTime) ? $dateTime->format("d-m-Y H:i") : "No time";

            postSlackMessage("Appointment for question *".$question->Id."* by client *".$AskedByPerson->Id."* at *".$dateTime."* is canceled by *client*. Closed the question.", ":nope:");
            rtestCareAnalytics("message_sms", $AskedByPerson->Id, ['SubEvent'=>"canceledappointmentcaregiver_v1"]);
        }

        if ($result){
            $request->session()->put('message', [
                "text" => __("The Question was canceled"),
                "type" => "success"
            ]);
        }

        return ['success' => $result];
    }

    /**
     * Caregiver cancel Appointment action
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelAppointmentAction(Request $request) {

        $Appointment = Appointment::find($this->request->post('appointmentId'));
        $Question = $Appointment->getQuestion;
        $result = false;

        if ($this->person->Id == $Appointment->AcceptedByPersonId){
            $result = $this->cancelAppointment($Appointment, null);
            $dateTime = getAmsterdamTimeZone();
            $dateTime->setTimestamp(strtotime($Appointment->DateTimeAppointmentFrom));

            if ($Question->AppointmentType == 'event'){
                $messageTemplate = MessageTemplate::where('Name', '=', 'eventcancel_v1')->firstOrFail();
                $questionEvent = QuestionEvent::where('QuestionId', $Question->Id)->first();
                $eventClients = $questionEvent->getVisitors();
                foreach ($eventClients as $eventClient) {
                    $message = Message::create([
                        "MessageTemplateId" => $messageTemplate->Id,
                        "PersonId" => $eventClient->Id,
                        "Variables" => json_encode([
                            "EVENT_TITLE" => $questionEvent->EventTitle,
                            "EVENT_DATE" => $dateTime->format("d-m H:i")
                        ]),
                        "Medium" => "SMS"
                    ]);
                    $message->send();
                    postSlackMessage("Appointment for question *".$Question->Id."* by client *".$eventClient->Id."* at *".$dateTime->format("d-m-Y H:i")."* is canceled by *caregiver*. Rescheduling now.", ":nope:");
                }
            } else {
                $messageTemplate = MessageTemplate::where('Name', '=', 'canceledappointmentclient_v1')->firstOrFail();
                $Client = $Question->Person;
                $message = new Message([
                    "MessageTemplateId" => $messageTemplate->Id,
                    "PersonId" => $Client->Id,
                    "Variables" => json_encode([
                        "CAREGIVER" => $this->person->Firstname . " " . $this->person->Lastname,
                        "DATETIME" => $dateTime->format("d-m H:i")
                    ]),
                    "Medium" => "SMS"
                ]);
                $message->send();
                postSlackMessage("Appointment for question *".$Question->Id."* by client *".$Client->Id."* at *".$dateTime->format("d-m-Y H:i")."* is canceled by *caregiver*. Rescheduling now.", ":nope:");
            }
        }

        return response()->json(['success' => $result]);
    }

    /**
     * Core Caregiver Agenda loading page
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function showCaregiverPage()
    {
        $this->authorize('showCaregiverView', $this->person);

        return view('/agenda/caregiver', [
            "error" => $this->error,
            'currentFilterChoice' => $this->request->type,
            'message' => ! empty(session('message')) ? json_encode(session('message')) : '{}',
            'assetUrl' => asset('/img'),
        ]);
    }

    /**
     * Appointments data for Caregiver Agenda page
     * @return array
     */
    public function getCaregiverAppointmentForDate()
    {
        $date = $this->request->date ? $this->request->date : date("Y-m-d");

        $appointmentsData = new Collection();
        $inloopData = new Collection();
        $appointments = new Collection();

        $appointments = $appointments
            ->merge($this->person->getAppointmentsForDate($date, ['AcceptedByPersonId' => $this->person->Id]))
            ->merge($this->person->getEventsForDate($date, ['AcceptedByPersonId' => $this->person->Id]));

        foreach ($appointments as $appointment) {
            $appointmentsData = $appointmentsData->push($this->prepare_appointment_data($appointment, true));
        }

        $inloopEvents = $this->person->getInloops(['date' => $date, 'forPerson' => true]);
        foreach ($inloopEvents as $inloop) {
            $inloopData = $inloopData->push($this->prepare_inloop_data($inloop));
        }

        return [
            "appointments" => $appointmentsData,
            "inloopEvents" => $inloopData,
            "activeDate" => $date
        ];
    }
}
