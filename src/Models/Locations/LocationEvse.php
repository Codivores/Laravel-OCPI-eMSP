<?php

namespace Ocpi\Models\Locations;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ocpi\Support\Models\Model;

class LocationEvse extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'composite_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'party_role_id',
        'location_id',
        'composite_id',
        'uid',
        'object',
    ];

    protected function casts(): array
    {
        return [
            'object' => AsArrayObject::class,
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->composite_id = "{$model->location_id}_{$model->uid}";
        });
    }

    /***
     * Relations.
     ***/

    public function connectors(): HasMany
    {
        return $this->hasMany(LocationConnector::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function withTrashedConnectors(): HasMany
    {
        return $this->hasMany(LocationConnector::class)
            ->withTrashed();
    }

    public function withTrashedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id')
            ->withTrashed();
    }
}
