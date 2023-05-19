<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserAppointment;
use App\Models\UserFavorite;
use App\Models\Professional;
use App\Models\ProfessionalServices;

class UserController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function read()
    {
        $array = ['error' => ''];

        $info = $this->loggedUser;
        $info['avatar'] = url('media/avatars/'.$info['avatar']);

        $array['data'] = $info;

        return $array;
    }

    public function toggleFavorite(Request $request)
    {
        $array = ['error' => ''];

        $professional_id = $request->input('professional');

        // verifica se o profissional existe
        $professional = Professional::find($professional_id);

        if($professional)
        {
            $fav = UserFavorite::select()
                ->where('user_id', $this->loggedUser->id)
                ->where('professional_id', $professional_id)
            ->first();

            if($fav)
            {
                // Caso existir remove o favorito
                $fav->delete();
                $array['rave'] = false;
            } else {
                // se não existir adiciona aos favoritos
                $newFav = new UserFavorite();
                $newFav->user_id = $this->loggedUser->id;
                $newFav->professional_id = $professional_id;
                $newFav->save();
                $array['rave'] = true;
            }
        } else {
            $array['error'] = 'Profissional não existe';
        }
        return $array;
    }

    public function getFavorites()
    {
        $array = ['error' => '', 'list' => []];

        $favs = UserFavorite::select()
            ->where('user_id', $this->loggedUser->id)
        ->get();

        if($favs)
        {
            foreach($favs as $fav)
            {
                $professional = Professional::find($fav['professional_id']);
                $professional['avatar'] = url('media/avatars/'.$professional['avatar']);
                $array['list'][] = $professional;
            }
        }

        return $array;
    }

    public function getAppoitments()
    {
        $array = ['error' => '', 'list' => []];

        $apps = UserAppointment::select()
            ->where('user_id', $this->loggedUser->id)
            ->orderBy('ap_datetime', 'DESC')
        ->get();

        if($apps)
        {
            foreach($apps as $app)
            {
                $professional = Professional::find($app['professional_id']);
                $professional['avatar'] = url('media/avatars/'.$professional['avatar']);

                $service = ProfessionalServices::find($app['service_id']);

                $array['list'][] = [
                    'id' => $app['id'],
                    'datetime' => $app['ap_datetime'],
                    'professional' => $professional,
                    'service' => $service
                ];

            }
        }

        return $array;
    }
}
