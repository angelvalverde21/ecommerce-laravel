
<?php


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


