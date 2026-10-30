<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $fillable = [
        'usuario_id',
        'accion_id',
        'tabla',
        'indice',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function accion(): BelongsTo
    {
        return $this->belongsTo(Accion::class);
    }
}
