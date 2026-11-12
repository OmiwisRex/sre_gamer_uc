<?php

use Illuminate\Support\Carbon;

if (! function_exists('formatDateTime')) {
    /**
     * Formatea una fecha en formato 'YYYY-MM-DD HH:MM:SS' al formato
     * 'DD/MM/YYYY - HH:MM Am/Pm'
     *
     * @param  mixed  $fecha
     * @return string
     */
    function formatDateTime($fecha)
    {
        if (! $fecha) {
            return 'Sin fecha';
        }

        try {
            $date = Carbon::parse($fecha);
            $formatted = $date->format('d/m/Y - h:i A');

            return str_replace(['AM', 'PM'], ['Am', 'Pm'], $formatted);
        } catch (Exception) {
            return 'Fecha inválida';
        }
    }
}

if (! function_exists('formatTimeOnly')) {
    /**
     * Formatea solo la hora de una fecha en formato 'HH:MM Am/Pm'
     *
     * @param  mixed  $fecha
     * @return string
     */
    function formatTimeOnly($fecha)
    {
        if (! $fecha) {
            return 'Sin hora';
        }

        try {
            $date = Carbon::parse($fecha);
            $formatted = $date->format('h:i A');

            return str_replace(['AM', 'PM'], ['Am', 'Pm'], $formatted);
        } catch (Exception) {
            return 'Hora inválida';
        }
    }
}

if (! function_exists('formatDateOnly')) {
    /**
     * Formatea solo la fecha en formato 'DD/MM/YYYY'
     *
     * @param  mixed  $fecha
     * @return string
     */
    function formatDateOnly($fecha)
    {
        if (! $fecha) {
            return 'Sin fecha';
        }

        try {
            $date = Carbon::parse($fecha);

            return $date->format('d/m/Y');
        } catch (Exception) {
            return 'Fecha inválida';
        }
    }
}

if (! function_exists('formatTimeCompact')) {
    /**
     * Formatea la hora en formato compacto sin espacios 'HH:iAm/Pm'
     *
     * @param  mixed  $fecha
     * @return string
     */
    function formatTimeCompact($fecha)
    {
        if (! $fecha) {
            return 'Sin hora';
        }

        try {
            $date = Carbon::parse($fecha);
            $formatted = $date->format('h:ia');

            return str_replace(['am', 'pm'], ['Am', 'Pm'], $formatted);
        } catch (Exception) {
            return 'Hora inválida';
        }
    }
}
