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


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

/**  Bootstraping */
require_once __DIR__.'/../vendor/Silex/silex.phar';
require_once '/../OldSdk/Microsoft/WindowsAzure/Storage/Blob.php';

$app = new Silex\Application();
$app['autoloader']->registerNamespaces(array('Geo' => __DIR__,));
$app->register(new Geo\GeoCodeExtension());

/** Decodes JSON Requests */
$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request = new ParameterBag(is_array($data) ? $data : array());
    }
});

//Define our storage account name and keys
define("STORAGE_ACCOUNT_NAME", "accountname");
define("STORAGE_ACCOUNT_KEY", "accountkey");

/** App Definition */

//Uncomment to allow errors to only be seen in localhost
/*$app->error(function(Exception $e) use ($app){
	if (!in_array($app['request']->server->get('REMOTE_ADDR'), array('127.0.0.1', '::1'))) {
		return $app->redirect('/');
	}
});*/

/** Shows the home page */
$app->get('/', function() use ($app){
    echo 'Hello geo home';
}); 


/** Gets a Shared Access Signature.  This can be used by our clients to upload a blob **/
$app->match('/api/blobsas/get', function () use ($app){
    $container = $app['request']->get('container');
    $blobname = $app['request']->get('blobname');
    $storageClient = new Microsoft_WindowsAzure_Storage_Blob('blob.core.windows.net', STORAGE_ACCOUNT_NAME, STORAGE_ACCOUNT_KEY);
    $sharedAccessUrl = $storageClient->generateSharedAccessUrl(
                      $container,
                      $blobname,
                      'b', 
                      'w',
                     $storageClient ->isoDate(time()),
                      $storageClient ->isoDate(time() + 3000)
                     );
    return new Response('"'.$sharedAccessUrl.'"', 201);
});

/** API Method to fetch all URLs */
$app->match('/api/Location/FindPointsOfInterestWithinRadius', function () use ($app){
    $latitude = $app['request']->get('latitude');
    $longitude = $app['request']->get('longitude');
    $radiusInMeters = $app['request']->get('radiusInMeters');
    $resultArray = $app['geo']->getPointsOfInterestInArea($latitude, $longitude, $radiusInMeters);
    return $app->json($resultArray, 200);
});

/** API new POI */
$app->match('/api/location/postpointofinterest/', function (Request $request) use ($app){
    $description = $request->get('Description');
    $type = $request->get('Type');
    $url = $request->get('Url');
    $latitude = $request->get('Latitude');
    $longitude = $request->get('Longitude');
    $id = $request->get('Id');

    //We should do validation on passed in data here

    //Try adding the POI and either return a successful 201 or a horrible 500
    try {
        $app['geo']->addPOI($description, $type, $url, $latitude, $longitude, $id);        
    } catch (Exception $e) {
        return new Response('', 500);
    }
    return new Response('', 201);
});

/**  This method will create a test container for use in our client side code **/
/**  Code from http://blogs.msdn.com/b/brian_swan/archive/2010/07/08/accessing-windows-azure-blob-storage-from-php.aspx **/
/**  Note that we're defaulting the container name to 'test' **/
$app->match('/api/Location/AddTestContainer', function () use ($app){    

    $storageClient = new Microsoft_WindowsAzure_Storage_Blob('blob.core.windows.net', STORAGE_ACCOUNT_NAME, STORAGE_ACCOUNT_KEY);
    try {
        if($storageClient->isValidContainerName('test')) { 
            if(!$storageClient->containerExists('test')) { 
                $result = $storageClient->createContainer('test'); 
                echo "<h2>Container created.</h2>"; 
                //Set container to public (remove this to make it private)
                $storageClient->setContainerAcl('test', 
                                                Microsoft_WindowsAzure_Storage_Blob::ACL_PUBLIC); 
            } 
            else { 
                echo "<h2>That container already exists.</h2>"; 
            } 
        } 
        else { 
            echo "<h2>That is not a valid container name.</h2>"; 
        }
    } catch (Exception $e) {
        return new Response('', 500);
    }
    return new Response('', 201);
});

return $app;
