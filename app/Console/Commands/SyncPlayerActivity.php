<?php

namespace App\Console\Commands;

use App\Models\Estado;
use App\Models\Reserva;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

#[Signature('administracion:sync-player-activity')]
#[Description('Resuelve reservas vencidas o conversiones abandonadas.')]
class SyncPlayerActivity extends Command
{
    public function handle(): int
    {
        $now = now(config('app.timezone'));
        $estadoActivo = Estado::query()->where('nombre', 'Activo')->firstOrFail();
        $estadoBloqueado = Estado::query()->where('nombre', 'Bloqueado')->firstOrFail();
        $estadoEliminado = Estado::query()->where('nombre', 'Eliminado')->firstOrFail();

        Reserva::query()
            ->where('estado_id', $estadoBloqueado->id)
            ->where('updated_at', '<=', $now->copy()->subMinutes(15))
            ->orderBy('id')
            ->chunkById(100, function (Collection $reservations) use ($now, $estadoActivo, $estadoEliminado): void {
                foreach ($reservations as $reservation) {
                    $reservationEnd = Carbon::parse($reservation->inicio, config('app.timezone'))
                        ->addMinutes(15);
                    $reservation->update([
                        'estado_id' => $now->gt($reservationEnd) ? $estadoEliminado->id : $estadoActivo->id,
                    ]);
                }
            });

        Reserva::query()
            ->where('estado_id', $estadoActivo->id)
            ->where('inicio', '<=', $now->toDateTimeString())
            ->orderBy('id')
            ->chunkById(100, function (Collection $reservations) use ($now, $estadoEliminado): void {
                foreach ($reservations as $reservation) {
                    $reservationEnd = Carbon::parse($reservation->inicio, config('app.timezone'))
                        ->addMinutes(15);

                    if ($now->gt($reservationEnd)) {
                        $reservation->update(['estado_id' => $estadoEliminado->id]);
                    }
                }
            });

        return self::SUCCESS;
    }
}
