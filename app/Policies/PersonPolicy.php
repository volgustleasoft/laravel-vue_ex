<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PersonPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function listAll(Person $person)
    {
        return intval($person->IsAdmin) === 1;
    }

    public function ShowCaregiverManagerview(Person $person)
    {
        return intval($person->IsCareGiver) === 1 || intval($person->IsManager) === 1;
    }

    public function createAction(Person $person)
    {
        return intval($person->IsCareGiver) === 1;
    }

    public function editAction(Person $person)
    {
        return intval($person->IsCareGiver) === 1;
    }

    public function cancelAction(Person $person)
    {
        return intval($person->IsCareGiver) === 1;
    }

    public function joinEvent(Person $person)
    {
        return intval($person->IsClient) === 1;
    }

    public function cancelParticipant(Person $person)
    {
        return intval($person->IsClient) === 1;
    }

    public function showClientView(Person $person)
    {
        return intval($person->IsClient) === 1;
    }

    public function showManagerView(Person $person)
    {
        return intval($person->IsManager) === 1;
    }

    public function showCaregiverView(Person $person)
    {
        return intval($person->IsCareGiver) === 1;
    }

    public function connectOns(Person $person)
    {
        //CHECK IF CLIENT IS IN TEAM
        return intval($person->IsManager) === 1;
    }
}
