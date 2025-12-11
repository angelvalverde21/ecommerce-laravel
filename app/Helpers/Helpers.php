<?php

use Illuminate\Support\Str;

function pluralToSingular($word) {
    if (preg_match('/(ces)$/', $word)) {
        return preg_replace('/ces$/', 'z', $word); // peces → pez, luces → luz
    }
    if (preg_match('/(iones)$/', $word)) {
        return preg_replace('/iones$/', 'ión', $word); // acciones → acción
    }
    if (preg_match('/(es)$/', $word) && strlen($word) > 3) {
        return preg_replace('/es$/', '', $word); // canciones → canción, mares → mar
    }
    if (preg_match('/(os|as)$/', $word)) {
        return substr($word, 0, -1); // gatos → gato, casas → casa
    }
    return $word; // Si no cumple ninguna regla, devolver igual
}


if (! function_exists('match_courier')) {

    function match_courier(string $text, $patterns): ?string
    {

        // Ordenamos para priorizar coincidencias más largas
        usort($patterns, fn($a, $b) => strlen($b) <=> strlen($a));

        $normalized = Str::lower($text);

        foreach ($patterns as $pattern) {
            if (Str::contains($normalized, Str::lower($pattern))) {
                return $pattern;
            }
        }

        return null;
    }
    
}