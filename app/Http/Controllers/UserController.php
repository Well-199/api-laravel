<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use Intervention\Image\Facades\Image;

use App\Models\User;
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

    public function update(Request $request)
    {
        $array = ['error' => ''];

        $rules = [
            'name' => 'min:2',
            'email' => 'email|unique:users',
            'password' => 'same:password_confirm',
            'password_confirm' => 'same:password'
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails())
        {
            $array['error'] = $validator->messages();
            return $array;
        }

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::find($this->loggedUser->id);

        if($name){
            $user->name = $name;
        }

        if($email){
            $user->email = $email;
        }

        if($password){
            $user->password = password_hash($password, PASSWORD_DEFAULT);
        }

        $user->save();

        return $array;
    }

    public function updadeAvatar(Request $request)
    {
        $array = ['error' => ''];

        $rules = ['avatar' => 'required|image|mimes:png,jpg,jpeg'];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails())
        {
            $array['error'] = $validator->messages();
            return $array;
        }

        // recebe o avatar com o parametro 'avatar'
        $avatar = $request->file('avatar');

        // caminho da pasta de destino da imagem
        $dest = public_path('/media/avatars');

        // gera um hash e concatena com a extensão .jpg para renomear a imagem
        $avatarName = md5(time().rand(0,9999)).'.jpg';

        // pega o caminho real do arquivo e armazena em $img
        $img = Image::make($avatar->getRealPath());

        // redimensiona a imagem para 300 x 300 e salva na pasta de destino ja renomeado com md5
        $img->fit(300, 300)->save($dest.'/'.$avatarName);
        //$array['data'] = $avatar->getRealPath();

        // Busca o usuario pelo id e altera o valor da propriedade avatar pra o novo avatar renomeado e salva no banco de dados
        $user = User::find($this->loggedUser->id);
        $user->avatar = $avatarName;
        $user->save();

        return $array;
    }
}
