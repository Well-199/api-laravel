<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
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

    public function createRandom()
    {
        $array = ['error'=>''];

        for($q=0; $q < 15; $q++)
        {
            $names = ['Jeny', 'Paulo', 'Amanda', 'Leticia', 'Gabriel', 'Ronaldo'];
            $lastnames = ['Silva', 'Kaoru', 'Diniz', 'Alvaro', 'Souza', 'Gomes'];

            $servicos = ['Corte', 'Pintura', 'Aparação', 'Luzes'];
            $servicos2 = ['Cabelo', 'Unha', 'Unha de gel', 'Sobrancelhas'];

            $depos = [
                'Eu tive uma experiência incrível na clínica de estética! Desde o momento em que entrei,
                fui recebida com um sorriso caloroso e uma atenção impecável dos funcionários.
                Eles foram muito atenciosos e prestativos em responder todas as minhas perguntas e me ajudaram a escolher
                o melhor tratamento para as minhas necessidades.'
            ];

            $newProfessional = new Professional();
            $newProfessional->name = $names[rand(0, count($names)-1)].' '.$lastnames[rand(0, count($lastnames)-1)];
            $newProfessional->avatar = rand(1, 4).'.png';
            $newProfessional->stars = rand(2, 4).'.'.rand(0, 9);
            $newProfessional->latitude = '-23.5'.rand(0, 9).'30907';
            $newProfessional->longitude = '-46.6'.rand(0, 9).'82795';
            $newProfessional->save();

            $ns = rand(3, 6);

            for($w=0; $w < 4; $w++)
            {
                $newProfessionalPhoto = new ProfessionalPhotos();
                $newProfessionalPhoto->professional_id = $newProfessional->id;
                $newProfessionalPhoto->url = rand(1, 5).'.png';
                $newProfessionalPhoto->save();
            }

            for($w=0; $w < $ns; $w++)
            {
                $newProfessionalService = new ProfessionalServices();
                $newProfessionalService->professional_id = $newProfessional->id;
                $newProfessionalService->name = $servicos[rand(0, count($servicos)-1)].' de '.$servicos2[rand(0, count($servicos2)-1)];
                $newProfessionalService->price = rand(1, 99).'.'.rand(0, 100);
                $newProfessionalService->save();
            }

            for($w=0; $w < 3; $w++)
            {
                $newProfessionalTestimonial = new ProfessionalTestimonial();
                $newProfessionalTestimonial->professional_id = $newProfessional->id;
                $newProfessionalTestimonial->name = $names[rand(0, count($names)-1)].' '.$lastnames[rand(0, count($lastnames)-1)];
                $newProfessionalTestimonial->rate = rand(2, 4).'.'.rand(0, 9);
                $newProfessionalTestimonial->body = $depos[rand(0, count($depos)-1)];//.' '.$depos[rand(0, count($depos)-1)]
                $newProfessionalTestimonial->save();
            }

            for($e=0; $e < 4; $e++)
            {
                $rAdd = rand(7, 10);
                $hours = [];
                for($r=0; $r < 8; $r++)
                {
                    $time = $r + $rAdd;
                    if($time < 10)
                    {
                        $time = '0'.$time;
                    }
                    $hours[] = $time.':00';
                }
                $newProfessionalAvail = new ProfessionalAvailability();
                $newProfessionalAvail->professional_id = $newProfessional->id;
                $newProfessionalAvail->weekday = $e;
                $newProfessionalAvail->hours = implode(',', $hours);
                $newProfessionalAvail->save();
            }

        }

        return $array;
    }
}
