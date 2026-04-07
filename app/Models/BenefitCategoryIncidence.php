<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class BenefitCategoryIncidence extends Model
{

    // use SoftDeletes;

    protected $table = 'benefit_category_incidence';

    protected $fillable = [
        'quantity',
        'active',
        'benefit_id',
        'category_incidence_id',
    ];

}
