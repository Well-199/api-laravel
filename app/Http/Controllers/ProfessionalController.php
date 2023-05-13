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




            $array['data'] = $professional;

        } else {
            $array['error'] = 'Professional não existe';
            return $array;
        }

        return $array;
    }
}
