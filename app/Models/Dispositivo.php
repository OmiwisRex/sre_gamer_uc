<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispositivo extends Model
{
    protected $fillable = [
        'nombre',
        'direccion',
        'password',
        'tipo_dispo_id',
        'estado_id',
        'descripcion',
    ];

    public function tipoDispo(): BelongsTo
    {
        return $this->belongsTo(TipoDispo::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    public function asociaciones(): HasMany
    {
        return $this->hasMany(Asociacion::class);
    }

    public function eventoDispos(): HasMany
    {
        return $this->hasMany(EventoDispo::class);
    }

    public function eventos(): BelongsToMany
    {
        return $this->belongsToMany(Evento::class, 'evento_dispos')->withTimestamps();
    }
}
