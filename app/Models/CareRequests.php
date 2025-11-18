<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareRequests extends Model
{
    protected $table = 'care_requests';
    protected $fillable = [
        'entity_name',
        'name',
        'phone', 
        'entity_type',
        'message',
    ];
}
