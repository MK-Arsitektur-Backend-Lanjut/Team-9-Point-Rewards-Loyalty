<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PointRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_name',
        'description',
        'base_points',
        'multiplier_rules',
        'validity_days',
        'is_active'
    ];

    protected $casts = [
        'base_points' => 'integer',
        'multiplier_rules' => 'json',
        'validity_days' => 'integer',
        'is_active' => 'boolean'
    ];

    public $timestamps = true;
}
