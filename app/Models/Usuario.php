<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Usuario extends Model
{
    protected $fillable = [
        'nombres',
        'apellidos',
        'cedula',
        'codigo',
        'year_nace',
        'genero_id',
        'estado_id',
        'rol_id',
    ];

    public function genero(): BelongsTo
    {
        return $this->belongsTo(Genero::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    public function sesion(): HasOne
    {
        return $this->hasOne(Sesion::class);
    }

    public function asociaciones(): HasMany
    {
        return $this->hasMany(Asociacion::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class);
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(Observacion::class);
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class);
    }
}
