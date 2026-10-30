<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reserva extends Model
{
    protected $fillable = [
        'usuario_id',
        'tipo_dispo_id',
        'evento_id',
        'estado_id',
        'inicio',
        'duracion_min',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function tipoDispo(): BelongsTo
    {
        return $this->belongsTo(TipoDispo::class);
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    public function asociaciones(): HasMany
    {
        return $this->hasMany(Asociacion::class);
    }
}
