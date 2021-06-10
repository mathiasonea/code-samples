<?php

namespace SpOTTCommon\Content;

use Database\Factories\DistributionTypeFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributionType extends Model
{
    use HasFactory;

    const AVCMP_LIVE = 1;
    const AVCMP_RECORDING = 2;
    const AVCMP_VOD = 3;
    const LIVE = 1;
    const VOD = 2;

    public $timestamps = false;

    protected $table = 'distribution_types';

    protected $fillable = [
        'avcmp_id',
        'display_name'
    ];

    /** @return \Illuminate\Database\Eloquent\Factories\Factory */
    protected static function newFactory(): Factory
    {
        return DistributionTypeFactory::new();
    }

    public function contents(): HasMany
    {
        return $this->hasMany(EventContent::class, 'distribution_type_id');
    }
}
