<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class KuesionerMongo extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'kuesioner_raw';

    protected $guarded = [];
}