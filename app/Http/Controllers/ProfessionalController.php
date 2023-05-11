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

    public function list(Request $request)
    {
        $array = ['error' => ''];

        $professional = Professional::all();

        foreach($professional as $bkey => $bvalue)
        {
            $professional[$bkey]['avatar'] = url('media/avatars/'.$professional[$bkey]['avatar']);
        }

        $array['data'] = $professional;
        $array['loc'] = 'São Paulo';

        return $array;
    }
}
