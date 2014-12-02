<?php
// ---------------------------------------------------------------------------------- 
// Microsoft Developer & Platform Evangelism 
//  
// Copyright (c) Microsoft Corporation. All rights reserved. 
//  
// THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY KIND,  
// EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE IMPLIED WARRANTIES  
// OF MERCHANTABILITY AND/OR FITNESS FOR A PARTICULAR PURPOSE. 
// ---------------------------------------------------------------------------------- 
// The example companies, organizations, products, domain names, 
// e-mail addresses, logos, people, places, and events depicted 
// herein are fictitious.  No association with any real company, 
// organization, product, domain name, email address, logo, person, 
// places, or events is intended or should be inferred. 
// ---------------------------------------------------------------------------------- 


namespace Geo;

class GeoCode {

    private $db_server = 'localhost';
    private $db_user   = 'phptestuser';
    private $db_password = 'phptestuser';
    private $db_name     = 'shorty';

    /** Loads the URLs from the DB */
    public function __construct() {
    }

    /**  Gets all points of interest.  This wouldn't be a good method to use if you had tons of POIs **/
    public function getAllPointsOfInterest($latitude, $longitude, $radiusInMeters) {
        $db_url_list = array();

        $con = mysql_connect($this->db_server,$this->db_user,$this->db_password);
        if (!$con)
          {
          die('Could not connect: ' . mysql_error());
          }
        mysql_select_db($this->db_name, $con);
        $result = mysql_query("SELECT Id, Type, Description, Url, AsText(Location) FROM geodata");
        echo $result;
        $poi_list = array();
        while($row = mysql_fetch_array($result)){
            $poi_list[$row['Id']] = $row;
        }
        mysql_close($con);
        return $poi_list;
    }

    /** Gets the points of interest for a specific area **/
    public function getPointsOfInterestInArea($latitude, $longitude, $radiusInMeters) {
        $db_url_list = array();

        $con = mysql_connect($this->db_server,$this->db_user,$this->db_password);
        if (!$con)
          {
          die('Could not connect: ' . mysql_error());
          }

        mysql_select_db($this->db_name, $con);

      //code from http://www.movable-type.co.uk/scripts/latlong-db.html
      $lat = $latitude;
      $lon = $longitude;
      $rad = $radiusInMeters;

      $R = 6371;

      // first-cut bounding box (in degrees)
      $maxLat = $lat + rad2deg($rad/$R);
      $minLat = $lat - rad2deg($rad/$R);
      // compensate for degrees longitude getting smaller with increasing latitude
      $maxLon = $lon + rad2deg($rad/$R/cos(deg2rad($lat)));
      $minLon = $lon - rad2deg($rad/$R/cos(deg2rad($lat)));


      // convert origin of filter circle to radians
      $lat = deg2rad($lat);
      $lon = deg2rad($lon);

      $result = mysql_query("
        Select Id, Type, Description,Url, X(Location) as Lat, Y(Location) as Lon, 
               acos(sin($lat)*sin(radians(x(location))) + cos($lat)*cos(radians(x(location)))*cos(radians(y(location))-$lon))*$R As D
        From (
          Select Id, Type, Description, Url, x(location), y(location), location
          From geodata
          Where x(location)>$minLat And x(location)<$maxLat
            And y(location)>$minLon And y(location)<$maxLon
          ) As FirstCut 
        Where acos(sin($lat)*sin(radians(x(location))) + cos($lat)*cos(radians(x(location)))*cos(radians(y(location))-$lon))*$R < $rad
        Order by D");


      $poi_list = array();
      $count = 0;

      //Read all of our POIs into an array
      while($row = mysql_fetch_array($result))
        {
          $item_array = array();
          $item_array['Id'] = $row['Id'];
          $item_array['Type'] = intval($row['Type']);
          $item_array['Description'] = $row['Description'];
          $item_array['Url'] = $row['Url'];
          $item_array['Latitude'] = floatval($row['Lat']);
          $item_array['Longitude'] = floatval($row['Lon']);
          //$poi_list[$row['Id']] = $row;//$row['Url'];
          $poi_list[$count] = $item_array;

          $count++;
        }

        mysql_close($con);
        return $poi_list;
    }


    /** Adds a new SLUG to the DB (and file) */
    public function addPOI($description, $type, $url, $latitude, $longitude, $id) {
        //Add to DB
        $con = mysql_connect($this->db_server,$this->db_user,$this->db_password);
        if (!$con)
        {
          die('Could not connect: ' . mysql_error());
        }

        mysql_select_db($this->db_name, $con);

        $sqlInsert = "INSERT INTO geodata (id, type, description, url, location) values ('$id', $type, '$description', '$url', GeomFromText( ' POINT($latitude $longitude) '))";

        if (!mysql_query($sqlInsert,$con))
        {
          die('Error: ' . mysql_error());
        }
        mysql_close($con);
    }   
}
