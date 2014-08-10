<?php
//header("Content-type: image/svg-xml");

$hostname_p 	=	"localhost";
$dbname_p	=	"practicaSVGKML";
$user_p		= 	"postgres";
$pwd_p 		= 	"postgres";
$ymaxg 		= 	"";
	
$connection = pg_connect("host=$hostname_p dbname=$dbname_p user=$user_p password=$pwd_p"); 
			
			
if (!$connection) {
    print("<h1>No se conecto</h1>.");
    die();
}

//localhost/despliegue_SVG.php?tables_geom=lotes_2005,pluviometros,quebradas,zverdes,viasprinci

$tables_geom = $_GET['tables_geom'];
$tables=split(",",$tables_geom);
$main_bbox=($_GET['usebb']) ;



function crearExtentDinamico($tables)
{
	$sql="";
        $sql.="select xmin(extent(g)) , ymin(extent(g)) , xmax(extent(g)), ymax(extent(g)) from ( ";

	for($i=0;$i<count($tables);$i++)
	{
		if($i==(count($tables)-1))
		{
		   $sql.=" select extent(the_geom) as g from ".$tables[$i]." ";	
		}
             else
		{
		  $sql.=" select extent(the_geom) as g from ".$tables[$i]."  union all";	
		}
	}
	$sql.=") as foo;";

	return $sql;
}


function crearXML($conex,$tables)
{
        global $ymaxg;

 	$myresult = pg_exec($conex, crearExtentDinamico($tables) );
	$xmin = pg_result($myresult, 0, 0);
	$ymin = pg_result($myresult, 0, 1);
	$xmax = pg_result($myresult, 0, 2);
	$ymax = pg_result($myresult, 0, 3);

	$ymaxg = $ymax;
	
	$valor= $xmin." 0 ".($xmax-$xmin)." ".($ymax-$ymin);	
     	print('<?xml version="1.0" encoding="utf-8" standalone="no"?>'. "\n");
	print('<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN"  "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">');
	echo '<svg id="base" width="1000" height="700" viewBox="'.$valor.'" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" >'."\n";

}


function crearLayers($conex,$layer)
{
	global $ymaxg;
	$sql2 = "select type from geometry_columns where f_table_name = '".$layer."';";
	$myresult = pg_exec($conex, $sql2);
	$type_geom = pg_result($myresult, 0, 0);

	$sql3 = "select assvg(the_geom) from ".$layer.";";
	$myresult = pg_exec($conex, $sql3);
	
	switch ($type_geom) 
	{
		case "MULTIPOLYGON":
			poligon($myresult,$ymaxg);
			break;
		case "MULTILINESTRING":
			lineas($myresult,$ymaxg);
			break;		
		case "POINT":
			puntos($myresult,$ymaxg);
			break;
	}
	
}


//Crea poligonos
function poligon($result,$ymax){
echo '<g transform="matrix(1 0 0 1 0 '.$ymax.')" >'."\n";
 for ($lt = 0; $lt < pg_numrows($result); $lt++) {
 	echo '<path id="'.$lt.'" fill="rgb(0,255,0)" stroke="rgb(0,0,0)" d="'. pg_result($result,$lt,0). '" />'."\n";
   }
echo '</g>'."\n";
}


//Crea lineas
function lineas($result,$ymax){
echo '<g transform="matrix(1 0 0 1 0 '.$ymax.')" >'."\n";
 for ($lt = 0; $lt < pg_numrows($result); $lt++) {
	echo '<path id="'.$lt.'" fill="none" stroke-width="5" stroke="rgb(255,0,0)" d="'. pg_result($result,$lt,0). '" />'."\n"; 
   }
echo '</g>'."\n";
}


//Crea puntos
function puntos($result,$ymax){
echo '<g transform="matrix(1 0 0 1 0 '.$ymax.')" >'."\n";
 for ($lt = 0; $lt < pg_numrows($result); $lt++) {	
	echo '<circle '.pg_result($result,$lt,0).' r="50" stroke="rgb(0,0,0)" stroke-width="15" fill="rgb(0,0,255)" />'."\n";
   }
echo '</g>'."\n";
}



//CREAR MAPA

crearXML($connection,$tables);

for($i=0;$i<count($tables);$i++)
{
	crearLayers($connection,$tables[$i]);
}
print('</svg>');



unset($myresult);
pg_close($connection);

?>
