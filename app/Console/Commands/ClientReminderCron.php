<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Message;
use App\Models\MessageTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClientReminderCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientReminder:cron';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '2 apppointment reminders for clients';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if( ! empty($appointmentsThatNeedsFirstReminder = $this->getFirstReminderAppointments())) {
            $this->firstReminder($appointmentsThatNeedsFirstReminder);
        }

        if( ! empty($appointmentsThatNeedsSecondReminder = $this->getSecondReminderAppointments())) {
            $this->secondReminder($appointmentsThatNeedsSecondReminder);
        }
    }

    private function firstReminder($appointmentsThatNeedsFirstReminder) {
        foreach($appointmentsThatNeedsFirstReminder as $appointment){
            $startDateTime = $appointment->DateTimeAppointmentFrom;
            if(strtotime($startDateTime)-time() < 24*60*60){
                $t = getAmsterdamTimeZone();
                if(intval($t->format('H')) >= 10 && intval($t->format('H')) <= 21){
                    $startDateTime = getAmsterdamTimeZone($startDateTime);
                    $client = $appointment->getQuestion->Person;
                    $careGiver = $appointment->getAcceptedByPerson;
                    $messageTemplate = MessageTemplate::where('Name', '=', 'firstreminderclient_v1')->firstOrFail();

                    $message = Message::create([
                       "MessageTemplateId" => $messageTemplate->Id,
                       "PersonId" => $client->Id,
                       "Variables" => json_encode([
                          "CAREGIVER"=>$careGiver->getFirstname()." ".$careGiver->getLastname(),
                          "DATE"=>$startDateTime->format("d-m-Y"),
                          "TIME"=>$startDateTime->format("H:i")
                      ]),
                       "Medium" => "SMS"
                    ]);
                    $message->send();

                    postSlackMessage("Sent message *firstreminderclient_v1* to person *".$client->Id."* via *SMS*", ":envelope:");
                    rtestCareAnalytics("message_sms", $client, ['SubEvent'=>"firstreminderclient_v1"]);

                    $appointment->update(['FirstReminderSentDateTime' => date("Y-m-d H:i:s")]);
                }
            }
        }
    }

    private function secondReminder($appointmentsThatNeedsSecondReminder) {
        foreach($appointmentsThatNeedsSecondReminder as $appointment){
            $startDateTime = $appointment->DateTimeAppointmentFrom;
            if(strtotime($startDateTime)-time() < 3*60*60){
                $t = getAmsterdamTimeZone();
                if(intval($t->format('H')) >= 7){
                    $startDateTime = getAmsterdamTimeZone($startDateTime);
                    $client = $appointment->getQuestion->Person;
                    $careGiver = $appointment->getAcceptedByPerson;
                    $messageTemplate = MessageTemplate::where('Name', '=', 'secondreminderclient_v1')->firstOrFail();

                    $message = Message::create([
                       "MessageTemplateId" => $messageTemplate->Id,
                       "PersonId" => $client->Id,
                       "Variables" => json_encode([
                          "CAREGIVER"=>$careGiver->getFirstname()." ".$careGiver->getLastname(),
                          "TIME"=>$startDateTime->format("H:i")
                       ]),
                       "Medium" => "SMS"
                    ]);
                    $message->send();

                    postSlackMessage("Sent message *secondreminderclient_v1* to person *".$client->Id."* via *SMS*", ":envelope:");
                    rtestCareAnalytics("message_sms", $client, ['SubEvent'=>"secondreminderclient_v1"]);

                    $appointment->update(['SecondReminderSentDateTime' => date("Y-m-d H:i:s")]);
                }
            }
        }
    }

    private function getFirstReminderAppointments() {
        return Appointment::query()
             ->where('State', '=', 'created')
             ->whereNull('FirstReminderSentDateTime')
             ->get()->all();
    }

    private function getSecondReminderAppointments() {
        return Appointment::query()
              ->where('State', '=', 'created')
              ->whereNull('SecondReminderSentDateTime')
              ->get()->all();
    }
}
