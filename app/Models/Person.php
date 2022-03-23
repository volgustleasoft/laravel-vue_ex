<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class Person extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'Person';
    protected $primaryKey = 'Id';
    protected $fillable = ['IsClientCareGiver'];
    public $timestamps = false;

    public function PersonalCareGiverCareGroup()
    {
        return $this->hasOne(CareGroup::class, "Id", "PersonalCareGroupId");
    }

    public function ClientTeam()
    {
        return $this->hasOne(CareGroup::class, "Id", "TeamCareGroupId");
    }

    public function CareGroupsManager()
    {
        return $this->belongsToMany(CareGroup::class, 'CareGroupManager', 'PersonId', 'CareGroupId');
    }

    public function CareGroupsCaregiver($PersonalAndRegularCaregiver = false)
    {
        return $this->belongsToMany(CareGroup::class, 'CareGroupPerson', 'PersonId', 'CareGroupId')
            ->whereIn('CareGroupLabelId', $PersonalAndRegularCaregiver ? [1, 2] : [1]);
    }

    public function ClientCaregiver()
    {
       return $this->hasOne(CareGroupPerson::class, 'CareGroupId', 'PersonalCareGroupId');
    }

    public function getHumanCheckups()
    {
        return $this->hasManyThrough( HumanCheckup::class,ContactRequest::class,'PersonId','ContactRequestId',"Id","Id");
    }

    public function IsPersonalClient($Caregiver)
    {
        return CareGroupPerson::
            where('CareGroupId', $this->PersonalCareGroupId)
            ->where('PersonId', $Caregiver->Id)
            ->exists();
    }

    public function getClientsForPerson($caregiverTeams)
    {
       return $this
           ->whereIn("TeamCareGroupId", $caregiverTeams)
           ->where('IsClient', true)
           ->where("IsActive", true)
           ->get()
           ->keyBy('Firstname');
    }

    public function getAppointmentsForDate($date, $args = [])
    {
        return $this->getAppointments([
              "datetimeMinimum" => $date . " 00:00:00",
              "datetimeMaximum" => $date . " 23:59:59",
              "AcceptedByPersonId" => $args['AcceptedByPersonId'] ?? null,
              "State" => ['completed', 'created', 'failedNoShow', 'failedNoOther'],
              "teamId" => $args['teamId'] ?? null,
              "appointmentsType" => ['call', 'visit', 'videocall']
          ]);
    }

    public function getAppointmentsForMonth($date, $args = [])
    {
        return $this->getAppointments([
              "datetimeMinimum" => date('Y-m-01 00:00:00', strtotime($date)),
              "datetimeMaximum" => date('Y-m-t 23:59:59', strtotime($date)),
              "AcceptedByPersonId" => $args['AcceptedByPersonId'] ?? null,
              "State" => ['completed', 'created', 'failedNoShow', 'failedNoOther'],
              "teamId" => $args['teamId'] ?? null,
              "appointmentsType" => ['call', 'visit', 'videocall']
        ]);
    }

    public function getAppointmentsForCaregiver($args = [])
    {
        return $this->getAppointments([
            "datetimeMaximum" => Carbon::now(),
            "AcceptedByPersonId" => $args['AcceptedByPersonId'] ?? null,
            "teamIds" => $args['teamIds'] ?? null,
            "State" => ['created'],
            "appointmentsType" => ['call', 'visit', 'videocall']
        ]);
    }

    public function getAppointments($args)
    {
        $query = Appointment::query()
            ->select('Question.*', 'Appointment.*')
            ->join('Question', 'Question.Id', '=', 'Appointment.QuestionId')
            ->join('Person', 'Person.Id', '=', 'Question.PersonId')
            ->when(!empty($args['appointmentsType']) && in_array('event', $args['appointmentsType']), function ($query){
                $query->join('QuestionEvent',  'Question.Id',  '=', 'QuestionEvent.QuestionId');
            })
            ->when(!empty($args['cross']), function ($query) use ($args){
                $query->where('Appointment.DateTimeAppointmentFrom', '<', $args['cross']['dateTo'])
                      ->where('Appointment.DateTimeAppointmentTo', '>', $args['cross']['dateFrom']);
            })
            ->when(!empty($args['datetimeMinimum']), function ($query) use ($args) {
                $query->where('Appointment.DateTimeAppointmentFrom', '>', $args['datetimeMinimum']);
            })
            ->when(!empty($args['datetimeMaximum']), function ($query) use ($args) {
                $query->where('Appointment.DateTimeAppointmentTo', '<', $args['datetimeMaximum']);
            })
            ->when(!empty($args['AcceptedByPersonId']), function ($query) use ($args) {
                $query->where('Appointment.AcceptedByPersonId', '=', $args['AcceptedByPersonId']);
            })
            ->when(!empty($args['teamIds']), function ($query) use ($args){
                $query->whereIn('Person.TeamCareGroupId', $args['teamIds']);
            });
            if (!empty($args['teamId']) && !empty($args['appointmentsType']) && in_array('event', $args['appointmentsType'])){
                $query->where('QuestionEvent.TeamCareGroupId', '=', $args['teamId']);
            }else{
                $query->when(!empty($args['teamId']), function ($query) use ($args) {
                    $query->where('Person.TeamCareGroupId', '=', $args['teamId']);
                });
            }
            $query->when(!empty($args['appointmentsType']), function ($query) use ($args){
                $query->whereIn('Question.AppointmentType', $args['appointmentsType']);
            })
                  ->whereNotNull('AcceptedByPersonId')
                  ->whereIn('Appointment.State', !empty($args['State']) ? $args['State'] : ['new', 'completed']);

        return $query->orderBy('Appointment.DateTimeAppointmentFrom', 'asc')->get()->all();
    }
}
