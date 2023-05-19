<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\UserAppointment;
use App\Models\UserFavorite;
use App\Models\Professional;
use App\Models\ProfessionalPhotos;
use App\Models\ProfessionalServices;
use App\Models\ProfessionalTestimonial;
use App\Models\ProfessionalAvailability;

class ProfessionalController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    private function searchGeo($address)
    {
        $key = env('MAPS_KEY', null);

        $address = urlencode($address);

        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&key='.$key;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }

    public function list(Request $request)
    {
        $array = ['error' => ''];

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $city = $request->input('city');
        $offset = $request->input('offset');

        if(!$offset)
        {
            $offset = 0;
        }

        if(!empty($city))
        {
            $res = $this->searchGeo($city);

            if(count($res['results']) > 0)
            {
                $lat = $res['results'][0]['geometry']['location']['lat'];
                $lng = $res['results'][0]['geometry']['location']['lng'];
            }
        } elseif(!empty($lat) && !empty($lng)) {

            $res = $this->searchGeo($lat.','.$lng);

            if(count($res['results']) > 0)
            {
                $city = $res['results'][0]['formatted_address'];
            }
        } else {
            $lat = '-23.5930907';
            $lng = '-46.6182795';
            $city = 'São Paulo';
        }

        $professional = Professional::select('*', Professional::raw('SQRT(
            POW(69.1 * (latitude - '.$lat.'), 2) +
            POW(69.1 * ('.$lng.' - longitude) * COS(latitude / 57.3), 2)) AS distance'))
            ->whereRaw('SQRT(
            POW(69.1 * (latitude - '.$lat.'), 2) +
            POW(69.1 * ('.$lng.' - longitude) * COS(latitude / 57.3), 2)) < ?', [10])
            ->orderBy('distance', 'ASC')
            ->offset($offset)
            ->limit(5)
            ->get();

        foreach($professional as $bkey => $bvalue)
        {
            $professional[$bkey]['avatar'] = url('media/avatars/'.$professional[$bkey]['avatar']);
        }

        $array['data'] = $professional;
        $array['loc'] = 'São Paulo';

        return $array;
    }

    public function one($id)
    {
        $array = ['error' => ''];

        $professional = Professional::find($id);

        if($professional)
        {

            $professional['avatar'] = url('media/avatars/'.$professional['avatar']);
            $professional['favorited'] = false;
            $professional['photos'] = [];
            $professional['services'] = [];
            $professional['testimonials'] = [];
            $professional['available'] = [];

            // Verificando favorito
            $cFavorite = UserFavorite::where('user_id', $this->loggedUser->id)
                ->where('professional_id', $professional->id)
                ->count();

            if($cFavorite > 0)
            {
                $professional['favorited'] = true;
            }

            // Pegando as fotos do Profissional
            $professional['photos'] = ProfessionalPhotos::select(['id', 'url'])
                ->where('professional_id', $professional->id)
                ->get();

            foreach($professional['photos'] as $pfkey => $pfvalue)
            {
                $professional['photos'][$pfkey]['url'] = url('media/uploads/'.$professional['photos'][$pfkey]['url']);
            }

            // Pegando os serviços do Profissional
            $professional['services'] = ProfessionalServices::select(['id', 'name', 'price'])
                ->where('professional_id', $professional->id)
                ->get();

            // Pegando os depoimentos
            $professional['testimonials'] = ProfessionalTestimonial::select(['id', 'name', 'rate', 'body'])
                ->where('professional_id', $professional->id)
                ->get();

            // Pegando disponibilidade do Professional
            $availability = [];

            // Pega todos os agendamentos de um professional
            $avails = ProfessionalAvailability::where('professional_id', $professional->id)->get();

            $availWeekdays = [];

            foreach($avails as $item)
            {
                $availWeekdays[$item['weekday']] = explode(',', $item['hours']);
            }

            // Pega os agendamentos dos proximos 20 dias
            $appointments = [];

            $appQuery = UserAppointment::where('professional_id', $professional->id)
                ->whereBetween('ap_datetime', [
                    date('Y-m-d').' 00:00:00',
                    date('Y-m-d', strtotime('+20 days')).' 23:59:59'
                ])
                ->get();

            foreach($appQuery as $appItem)
            {
                $appointments[] = $appItem['ap_datetime'];
            }

            // Gerar disponibilidade real
            for($q=0; $q<20; $q++)
            {
                $timeItem = strtotime('+'.$q.' days');
                $weekday = date('w', $timeItem);

                if(in_array($weekday, array_keys($availWeekdays)))
                {
                    $hours = [];

                    $dayItem = date('Y-m-d', $timeItem);

                    foreach($availWeekdays[$weekday] as $hourItem)
                    {
                        $dayFormated = $dayItem.' '.$hourItem.':00';

                        if(!in_array($dayFormated, $appointments))
                        {
                            $hours[] = $hourItem;
                        }
                    }

                    if(count($hours) > 0)
                    {
                        $availability[] = [
                            'date' => $dayItem,
                            'hours' => $hours
                        ];
                    }
                }

            }

            $professional['available'] = $availability;

            $array['data'] = $professional;

        } else {
            $array['error'] = 'Professional não existe';
            return $array;
        }

        return $array;
    }

    public function setAppointment($id, Request $request)
    {
        $array = ['error'=>''];

        $service = $request->input('service');
        $year = intval($request->input('year'));
        $month = intval($request->input('month'));
        $day = intval($request->input('day'));
        $hour = intval($request->input('hour'));

        $month = ($month < 10) ? '0'.$month : $month;
        $day = ($day < 10) ? '0'.$day : $day;
        $hour = ($hour < 10) ? '0'.$hour : $hour;

        // 1. verificar se o serviço do profissional existe
        $professionalservice = ProfessionalServices::select()
            ->where('id', $service)
            ->where('professional_id', $id)
            ->first();

        if($professionalservice)
        {
            // 2. verificar se a data é uma data valida
            $apDate = $year.'-'.$month.'-'.$day.' '.$hour.':00:00';

            if(strtotime($apDate) > 0)
            {
                // 3. verificar se o profissional ja possui agendamento nesse dia e hora
                $apps = UserAppointment::select()
                    ->where('professional_id', $id)
                    ->where('ap_datetime', $apDate)
                ->count();

                if($apps === 0)
                {
                    // 4. verificar se o profissional atende nesta data/hora
                    $weekday = date('w', strtotime($apDate));
                    $avail = ProfessionalAvailability::select()
                        ->where('professional_id', $id)
                        ->where('weekday', $weekday)
                    ->first();

                    if($avail)
                    {
                        // 5. Verificar se o professional atende nessa hora
                        $hours = explode(',', $avail['hours']);

                        if(in_array($hour.':00', $hours))
                        {
                            // 6. fazer o agendamento
                            $newApp = new UserAppointment();
                            $newApp->user_id = $this->loggedUser->id;
                            $newApp->professional_id = $id;
                            $newApp->service_id = $service;
                            $newApp->ap_datetime = $apDate;
                            $newApp->save();

                        } else {
                            $array['error'] = 'Professional não atende nesta hora';
                        }
                    } else {
                        $array['error'] = 'Profissional não atende neste dia';
                    }
                } else {
                    $array['error'] = 'Já possui agendamento neste dia/hora';
                }
            } else {
                $array['error'] = 'Data inválida';
            }
        } else {
            $array['error'] = 'Serviço inexistente';
        }
        return $array;
    }

    public function search(Request $request)
    {
        $array = ['error' => '', 'list' => []];

        $q = $request->input('q');

        if($q)
        {

            $professional = Professional::select()
                ->where('name', 'LIKE', '%'.$q.'%')
            ->get();

            foreach($professional as $index => $item)
            {
                $professional[$index]['avatar'] = url('media/avatars/'.$professional[$index]['avatars']);
            }

            $array['list'] = $professional;

        } else {
            $array['error'] = 'Digite algo para buscar';
        }


        return $array;
    }
}
