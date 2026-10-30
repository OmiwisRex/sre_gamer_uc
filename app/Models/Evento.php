<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evento extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'usuario_id',
        'inicio',
        'duracion_horas',
        'aforo',
        'auto_admin',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function asociaciones(): HasMany
    {
        return $this->hasMany(Asociacion::class);
    }

    public function eventoDispos(): HasMany
    {
        return $this->hasMany(EventoDispo::class);
    }

    public function dispositivos(): BelongsToMany
    {
        return $this->belongsToMany(Dispositivo::class, 'evento_dispos')->withTimestamps();
    }
}
