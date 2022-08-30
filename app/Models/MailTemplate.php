<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'mail_class',
        'description',
        'sending_frequency',
        'sending_date',
        'sending_hour',
        'active',
        'subject',
        'body',
    ];
}
