<?php
//header("Content-type: image/svg-xml");

$hostname_p =	"localhost";
$dbname_p	=	"lms";
$user_p		= 	"postgres";
$pwd_p 		= 	"postgres";
	
$connection = pg_connect("host=$hostname_p dbname=$dbname_p user=$user_p password=$pwd_p"); 
			
			
if (!$connection) {
    print("<h1>No se conecto</h1>.");
    die();
}

$tabla_geom = "";

if (isset($_GET['tabla_geom']) && $_GET['tabla_geom'] != "")
	$tabla_geom = $_GET['tabla_geom'];
else
	$tabla_geom = "base_red_geodesica";

$sql1 = "select xmin(extent(the_geom)),ymin(extent(the_geom)),xmax(extent(the_geom)),ymax(extent(the_geom)) from ".$tabla_geom.";";//$_GET["sql1"];
$sql2 = "select type from geometry_columns where f_table_name = '".$tabla_geom."';";
$sql3 = "select assvg(the_geom) from ".$tabla_geom.";";


//Obtiene area de despliegue
$myresult = pg_exec($connection, $sql1);
$valor = "";
$ymax = "";
$ymin = "";
$xmin = "";
$xmax = "";
for ($lt = 0; $lt < pg_numrows($myresult); $lt++) {
	$xmin = pg_result($myresult, $lt, 0);
	$ymin = pg_result($myresult, $lt, 1);
	$xmax = pg_result($myresult, $lt, 2);
	$ymax = pg_result($myresult, $lt, 3);
	$valor= $xmin." 0 ".($xmax-$xmin)." ".($ymax-$ymin);
}

$myresult = pg_exec($connection, $sql2);
$type_geom = "";
for ($lt = 0; $lt < pg_numrows($myresult); $lt++) {
	$type_geom = pg_result($myresult, $lt, 0);
}

//Crea poligonos
function poligon($result,$ymax){
echo '<g transform="matrix(1 0 0 1 0 '.$ymax.')" >'."\n";
 for ($lt = 0; $lt < pg_numrows($result); $lt++) {
 	echo '<path id="'.$lt.'" fill="rgb(204,204,255)" stroke="rgb(0,0,0)" d="'. pg_result($result,$lt,0). '" />'."\n";
   }
echo '</g>'."\n";
}


//Crea lineas
function lineas($result,$ymax){
echo '<g transform="matrix(1 0 0 1 0 '.$ymax.')" >'."\n";
 for ($lt = 0; $lt < pg_numrows($result); $lt++) {
	echo '<path id="'.$lt.'" fill="none" stroke-width="5" stroke="rgb(0,0,250)" d="'. pg_result($result,$lt,0). '" />'."\n"; 
   }
echo '</g>'."\n";
}


//Crea puntos
function puntos($result,$ymax){
echo '<g transform="matrix(1 0 0 1 0 '.$ymax.')" >'."\n";
 for ($lt = 0; $lt < pg_numrows($result); $lt++) {	
	echo '<circle '.pg_result($result,$lt,0).' r="50" stroke="black" stroke-width="15" fill="red" />'."\n";
   }
echo '</g>'."\n";
}

//Consulta el SVG del the_geom.
$myresult = pg_exec($connection, $sql3);

if (pg_numrows($myresult) > 0) //Verifica que contenga resultados.
{
	//Crea el SVG
	print('<?xml version="1.0" encoding="utf-8" standalone="no"?>'. "\n");
	print('<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN"  "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">');
	echo '<svg id="base" width="1000" height="700" viewBox="'.$valor.'" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" >'."\n";

	switch ($type_geom) 
	{
		case "MULTIPOLYGON":
			poligon($myresult,$ymax);
			break;
		case "MULTILINESTRING":
			lineas($myresult,$ymax);
			break;		
		case "POINT":
			puntos($myresult,$ymax);
			break;
	}

	print('</svg>');
}

unset($myresult);
pg_close($connection);

?>
