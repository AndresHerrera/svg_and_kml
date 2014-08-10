<?php

$name_mb="archivo.kml";

header("Content-type: application/octet-stream");
header("Content-Disposition: inline; filename=\"".$name_mb."\"");
header("Expires: ".gmdate("D, d M Y H:i:s", mktime(date("H")+2, date("i"), date("s"), date("m"), date("d"), date("Y")))." GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

header("Content-type: application/vnd.google-earth.kml+xml");

$hostname_p 	=	"localhost";
$dbname_p	=	"lms";
$user_p		= 	"postgres";
$pwd_p 		= 	"postgres";
$ymaxg 		= 	"";
	
$connection = pg_connect("host=$hostname_p dbname=$dbname_p user=$user_p password=$pwd_p"); 
			
			
if (!$connection) {
    print("<h1>No se conecto</h1>.");
    die();
}

//localhost/generaKML.php?tables_geom=lotes_2005,pluviometros,quebradas,zverdes,viasprinci

$tables_geom = $_GET['tables_geom'];
$tables=split(",",$tables_geom);
$main_bbox=($_GET['usebb']) ;


/*
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
}*/


function crearXML($conex,$tables)
{
        /*global $ymaxg;

 	$myresult = pg_exec($conex, crearExtentDinamico($tables) );
	$xmin = pg_result($myresult, 0, 0);
	$ymin = pg_result($myresult, 0, 1);
	$xmax = pg_result($myresult, 0, 2);
	$ymax = pg_result($myresult, 0, 3);

	$ymaxg = $ymax;*/
	
	//$valor= $xmin." 0 ".($xmax-$xmin)." ".($ymax-$ymin);	
     	print('<?xml version="1.0" encoding="utf-8" standalone="no"?>'. "\n");
        print('<kml xmlns="http://www.opengis.net/kml/2.2">'. "\n");
		echo '<Document>'."\n";
		echo ' <Style id="estilo_1">
      <LineStyle>
        <color>7f00ffff</color>
        <width>4</width>
      </LineStyle>
      <PolyStyle>
        <color>7f00ff00</color>
      </PolyStyle>
    </Style>
	
	<Style id="estilo_2">
       <LineStyle>
        <width>1.5</width>
      </LineStyle>
      <PolyStyle>
        <color>7dff0000</color>
      </PolyStyle>
    </Style>
	
	
	<Style id="estilo_3">
      <LineStyle>
        <color>5f00ffff</color>
        <width>4</width>
      </LineStyle>
      <PolyStyle>
        <color>7dff0000</color>
      </PolyStyle>
    </Style>
	
	
	
	<Style id="estilo_4">
      <LineStyle>
        <color>5f00ffff</color>
        <width>4</width>
      </LineStyle>
      <PolyStyle>
        <color>7dff0000</color>
      </PolyStyle>
    </Style>
	
	
	
	<Style id="estilo_5">
      <LineStyle>
        <color>5f00ffff</color>
        <width>2</width>
      </LineStyle>
      <PolyStyle>
        <color>ffffff</color>
      </PolyStyle>
    </Style>
	
	
	<Style id="estilo_6">
      <LineStyle>
        <color>5f00ffff</color>
        <width>4</width>
      </LineStyle>
      <PolyStyle>
        <color>fdff0f00</color>
      </PolyStyle>
    </Style>
	
	<Style id="punto_1">
      <IconStyle>
        <Icon>
          <href>http://maps.google.com/mapfiles/kml/paddle/red-stars.png</href>
        </Icon>
      </IconStyle>
    </Style>
    <Style id="punto_2">
      <IconStyle>
        <Icon>
          <href>http://maps.google.com/mapfiles/kml/paddle/wht-blank.png</href>
        </Icon>
      </IconStyle>
    </Style>
	
	 <Style id="punto_3">
      <IconStyle>
        <Icon>
          <href>http://maps.google.com/mapfiles/kml/paddle/orange-blank.png</href>
        </Icon>
      </IconStyle>
    </Style>
	
	'."\n";
	//print('<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN"  "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">');
	//echo '<svg id="base" width="1000" height="700" viewBox="'.$valor.'" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" >'."\n";

}


function crearLayers($conex,$layer)
{
	global $ymaxg;
	$sql2 = "select type from geometry_columns where f_table_name = '".$layer."';";
	$myresult = pg_exec($conex, $sql2);
	$type_geom = pg_result($myresult, 0, 0);

	$sql3 = "select askml(transform((setsrid(the_geom,21891)),4326)) from ".$layer.";";
	$myresult = pg_exec($conex, $sql3);
	
	switch ($type_geom) 
	{
		case "MULTIPOLYGON":
			poligon($myresult,$layer);
			break;
		case "MULTILINESTRING":
			lineas($myresult,$layer);
			break;		
		case "POINT":
			puntos($myresult,$layer);
			break;
	}
	
}


//Crea poligonos
function poligon($result,$layer)
{
	echo '<Folder id="'.$layer.'">'."\n";
	$colorid=rand(1,6);
	echo '<name>'.$layer.'</name>'."\n";
	for ($lt = 0; $lt < pg_numrows($result); $lt++) 
	{
		echo '<Placemark>'."\n";
		echo '<styleUrl>#estilo_'.$colorid.'</styleUrl>'."\n";
		echo "<name>$layer ".($lt+1)."</name>"."\n";
		echo pg_result($result,$lt,0)."\n";
		echo '</Placemark>'."\n";
   }
   echo '</Folder>'."\n";
}


//Crea lineas
function lineas($result,$layer){
	echo '<Folder id="'.$layer.'">'."\n";
	$colorid=rand(1,6);
	echo '<name>'.$layer.'</name>'."\n";
	for ($lt = 0; $lt < pg_numrows($result); $lt++) 
	{
		echo '<Placemark>'."\n";
		echo '<styleUrl>#estilo_'.$colorid.'</styleUrl>'."\n";
		echo "<name>$layer ".($lt+1)."</name>"."\n";
		echo pg_result($result,$lt,0)."\n";
		echo '</Placemark>'."\n";
   }
   echo '</Folder>'."\n";
}


//Crea puntos
function puntos($result,$layer)
{
	echo '<Folder id="'.$layer.'">'."\n";
	$colorid=rand(1,3);
	echo '<name>'.$layer.'</name>'."\n";
	for ($lt = 0; $lt < pg_numrows($result); $lt++) 
	{
		echo '<Placemark>'."\n";
		echo '<styleUrl>#punto_'.$colorid.'</styleUrl>'."\n";
		echo "<name>$layer ".($lt+1)."</name>"."\n";
		echo pg_result($result,$lt,0)."\n";
		echo '</Placemark>'."\n";
   }
   echo '</Folder>'."\n";
}



//CREAR MAPA

crearXML($connection,$tables);

for($i=0;$i<count($tables);$i++)
{
	crearLayers($connection,$tables[$i]);
}
echo '</Document>'."\n";
print('</kml>');



unset($myresult);
pg_close($connection);

?>
