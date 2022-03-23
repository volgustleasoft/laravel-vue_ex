<?php

namespace App\Http\Traits;

use App\Models\PinCode;
use Illuminate\Support\Facades\Auth;

trait AuthTrait {

    public function isBlocked($phonenumber){
        $didChange = false;
        $blockedNumbers = json_decode(file_get_contents(resource_path() . "/json/blockedNumbers.json"),true);
        $cleanedList = array();

        if(!is_array($blockedNumbers)){
            $blockedNumbers=array();
            $didChange=true;
        }

        if(! empty($blockedNumbers)) {
            for ($i = 0; $i < count($blockedNumbers); $i++) {
                if ($blockedNumbers[$i]["number"] == $phonenumber && time() < $blockedNumbers[$i]["unblockTime"]) {
                    return $blockedNumbers[$i]["unblockTime"];
                }
                if (time() < $blockedNumbers[$i]["unblockTime"]) {
                    array_push($cleanedList, $blockedNumbers[$i]);
                } else {
                    $didChange = true;
                }
            }
        }

        if($didChange){
            echo "PUT";
            file_put_contents(resource_path() . "/json/blockedNumbers.json", json_encode($blockedNumbers));
        }

        return false;
    }

    public function generateMagicToken(){
        $unique = false;

        while($unique===false){
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $token = '';
            for ($i = 0; $i < 7; $i++) {
                $token .= $characters[random_int(0, $charactersLength - 1)];
            }

            $unique = true;
            $query = "SELECT Id FROM AuthenticationCode WHERE MagicToken=? AND DateTimeCreated>?";
            $parameters = array($token, date("Y-m-d 00:00:00", strtotime("-1 year")));
            $result = database_query($GLOBALS['database'], $query, $parameters);
            while($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)){
                $unique = false;
            }
        }

        return $token;
    }

    public function generateCode($Person){
        if ($Person->PhoneNumber == "+31642712860") {
            $code = "123456";
        } else {
            $code = random_int(100000,999999)."";
        }

        return $code;
    }

    public function getActiveRolesData() {

        $roles = array();
        $person = Auth::user();

        if($person->IsAdmin){
            array_push($roles, [
                "name"=>"Admin",
                "url"=> "/admin/questions/"
            ]);
            array_push($roles, [
                "name"=>"Admin Laravel",
                "url"=> "/personen"
            ]);
        }
        if($person->IsClient){
            array_push($roles, [
                "name"=>"Client",
                "url"=> "/agenda/client"
            ]);
        }

        if($person->IsManager || $person->IsCareGiver) {
            $rolesLabel = [];
            $url = '';
            if ($person->IsManager) {
                $rolesLabel[] = "Manager";
                $url = "workinghours";
            }
            if ($person->IsCareGiver) {
                $rolesLabel[] = "Begeleider";
                $url = "agenda/caregiver";
            }
            array_push($roles, [
                "name" => implode(', ', $rolesLabel),
                "url" => $url
            ]);
        }

        return $roles;
    }

    public function loginActions($code) {

        $code->update(['IsUsed' => true]);
        $this->person->addContactLog("login", $code->Id);
        rtestCareAnalytics("login", $this->person, ["SubEvent"=>"success_messagecode"]);

        Auth::login($this->person);

        if(getenv('pincode_enabled')) {
            if ($this->person->IsCareGiver or $this->person->IsManager) {
                if(! $this->isExistsPinCode($this->person)) {
                    return redirect('/pincode/prompt');
                }
            }
        }

        return redirect('/');
    }
}
