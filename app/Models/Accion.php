<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Accion extends Model
{
    protected $table = 'acciones';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class);
    }
}
