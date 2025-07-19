<?php

namespace App\Models\PlazaFest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class SubFacility extends Model
{
    use SoftDeletes;
    protected $table = 'sub_facility';
    protected $dates = ['deleted_at'];
}
