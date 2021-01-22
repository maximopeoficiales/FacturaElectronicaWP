<?php
/* 
     Plugin Name: Max Factura Electronica 
     Plugin URI:
     Description: Añade funcionalidad para emitir facturas electronicas
     Version: 1.0.0
     Author Uri: Maximo Junior Apaza Chirhuana
     Text Domain: Max Factura Electronica
*/

if (!defined('ABSPATH')) die();

function maxFEInit()
{
}
add_action('init', 'maxFEInit', 0);



require 'inc/functions.php';
