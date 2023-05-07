<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionalPhotos extends Model
{
    use HasFactory;

    protected $table = 'professionalphotos';
    public $timestamps = false;
}
