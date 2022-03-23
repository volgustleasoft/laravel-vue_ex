<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Person;
use App\Models\AuthenticationCode;
use Hash;
use App\Http\Traits\AuthTrait;
use App\Http\Traits\TCTrait;
use Illuminate\Support\Facades\Session;
use libphonenumber\NumberParseException;

class AuthController extends Controller
{
    use AuthTrait, TCTrait;

    public function __construct(Request $request) {
        parent::__construct($request);

        if(! empty($this->person)) {
            redirect('/')->send();
        }
    }

    public function loginPhoneView()
    {
        $this->request->session()->forget('authentication');
        return view('auth/loginPhone');
    }

    public function loginPhoneAction()
    {
        $isValid = true;
        $country = "NL";
        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

        try {
            $NumberProto = $phoneUtil->parse($this->request->post('PhoneNumber'), $country);
        } catch (NumberParseException $e) {
            return view('auth/loginPhone');
        }

        if($isValid !== false){
            $formattedNumber = $phoneUtil->format($NumberProto, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);
            $formattedNumber = str_replace(" ", "", $formattedNumber);
            $isValid = $phoneUtil->isValidNumber($NumberProto);
        }

        if ( $isValid !== false and empty($this->isBlocked($formattedNumber))){
            if( !empty($person = Person::where('PhoneNumber', '=', $formattedNumber)->first()) and $person->IsActive) {
                $authCode = new AuthenticationCode();

                if(! empty($code = $authCode->getCodeMadeRecently($person->Id))) {
                    $authCode = $code;
                } else {
                    $authCode->fill([
                        'PersonId' => $person->Id,
                        'Code' => $authCode->generateCode($person),
                        'MagicToken' => $authCode->generateMagicToken(),
                    ]);
                    $authCode->save();
                }

                $messageTemplate = MessageTemplate::where('Name', '=', 'authentication_v1')->firstOrFail();
                $message = new Message([
                   "CreateNew" => true,
                   "MessageTemplateId" => $messageTemplate->Id,
                   "PersonId" => $person->Id,
                   "Variables" => json_encode([
                      "CODE"=>$authCode->Code,
                      "URL"=>route('loginTokenView', ['token'=> $authCode->MagicToken])
                  ]),
                   "Medium" => "SMS"
               ]);
                $message->send();
                rtestCareAnalytics("login", $person, ["SubEvent"=>"request"]);

            }
        }
        $this->request->session()->put('authentication.PhoneNumber', $formattedNumber);
        return redirect('/login/code');
    }

    public function loginCodeView()
    {
        $error = ! empty($this->request->session()->get('authentication.error')) ? $this->request->session()->get('authentication.error') : null;
        return view('auth/loginCode', ['error' => $error, 'PhoneNumber' => $this->request->session()->get('authentication.PhoneNumber')]);
    }

    public function loginCodeAction()
    {
        $attempts = ! is_null($this->request->session()->get('authentication.attempts')) ? $this->request->session()->get('authentication.attempts') : 0;
        $this->person = Person::where('PhoneNumber', '=', $this->request->post('PhoneNumber'))->get()->first();

        if(empty($this->request->post('PhoneNumber')) or empty($this->request->post('Code')) or empty($this->person)) {
            if($attempts >= 2){
                return redirect('/login');
            }
            $this->request->session()->put('authentication.attempts', $attempts+1);
            $error = 'Ongeldige code';
            return view('auth/loginCode', ['error' => $error, 'PhoneNumber' => $this->request->post('PhoneNumber')]);
        }

        if (! empty($code = $this->person->getExistsAuthCode($this->request->post('Code')))){
            if(! $this->termConditionValidation() ) {
                session(['AuthCodeId' => $code->Id]);
                return redirect('/tc/login');
            }
            return $this->loginActions($code);
        } else {
            $this->person->addFailedAttemptToOpenAuthenticationCodes();
            if($attempts >= 2){
                return redirect('/login');
            }
            $this->request->session()->put('authentication.attempts', $attempts+1);

            $error = "Ongeldige code";

            return view('auth/loginCode', ['error' => $error, 'PhoneNumber' => $this->request->post('PhoneNumber')]);
        }
    }

    public function logoutAction() {
        Session::flush();
        Auth::logout();

        return redirect('login');
    }

    public function loginTokenView($token) {
        return view('auth/loginToken', ['url' => route('loginTokenAction', ['token'=> $token])]);
    }

    public function loginTokenAction($token) {
        $authenticationCode = AuthenticationCode::where([
            'MagicToken' => $token,
            'IsUsed' => false,
        ])->where('DateTimeCreated', '>', date("Y-m-d H:i:s", (time()-60*intval(getenv("authentication_code_expiration_time_in_minutes")))))
        ->get()->first();

        if(empty($authenticationCode)){
            return redirect()->route('login');
        }else{
            $this->person = Person::find($authenticationCode->PersonId);
            if(! $this->termConditionValidation() ) {
                session(['AuthCodeId' => $authenticationCode->Id]);
                return redirect('/tc/login');
            }
            return $this->loginActions($authenticationCode);
        }
    }
}
