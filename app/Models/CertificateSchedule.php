<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_start_date',
        'submission_end_date',
        'submission_note',
        'submission_end_date_only',
        'submission_end_note',
        'evaluation_start_date',
        'evaluation_end_date',
        'evaluation_note',
        'announcement_date',
        'announcement_note',
        'awarding_start_date',
        'awarding_end_date',
        'awarding_note',
    ];
}