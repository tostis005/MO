<?php
/**
 * Entrega de jQuery en Home 0.10.148.
 *
 * jQuery se mantiene como recurso externo del núcleo de WordPress. Incrustarlo
 * completo dentro del HTML hacía crecer mucho el documento inicial y retrasaba
 * el momento en que el parser alcanzaba el hero/LCP bajo conexiones limitadas.
 *
 * No se aplica defer aquí: varios inicializadores históricos siguen esperando
 * jQuery durante el parseo y la prioridad es conservar compatibilidad.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Esta capa queda intencionadamente sin acciones. WordPress conserva los
 * handles jquery-core/jquery-migrate y sus URLs externas nativas, permitiendo
 * compresión y caché de navegador sin duplicar el código dentro del HTML.
 */
