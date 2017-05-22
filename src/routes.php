<?php
// Routes

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\Interfaces\Http\EnvironmentInterface as Environment;
use \Slim\Interfaces\RouterInterface as RouterInterface;

$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->get('/vehicles/{model_year}/{manufacturer}/{model}/', function (Request $request, Response $response, $args) {
    
	$object			= new stdClass();
	
	$model_year		= $request->getAttribute('route')->getArgument('model_year');
	$manufacturer	= $request->getAttribute('route')->getArgument('manufacturer');
	$model			= $request->getAttribute('route')->getArgument('model');
	$url			= "https://one.nhtsa.gov/webapi/api/SafetyRatings/modelyear/$model_year/make/$manufacturer/model/$model?format=json";
	$res			= file_get_contents($url);
	$resp			= json_decode($res);
	
	if( $resp->{'Count'} > 0 ){
		
		$object->{'Count'}	= $resp->{'Count'};
		$object->Results	= [];
		foreach( $resp->Results as $results ){
			$results_obj = new stdClass();
			$VehicleDescription			= $results->VehicleDescription;
			$VehicleId					= $results->VehicleId;
			$results_obj->Description	= $VehicleDescription;
			$results_obj->VehicleId		= $VehicleId;
			
			if(isset( $_GET['withRating'] ) && $_GET['withRating'] == 'true'){
				$url			= "https://one.nhtsa.gov/webapi/api/SafetyRatings/VehicleId/$VehicleId?format=json";
				$resV			= file_get_contents($url);
				$respV			= json_decode($resV);
				if( $respV->{'Count'} > 0 ){
					$resultsV	= $respV->Results;
					foreach($resultsV as $rv){
						$results_obj->CrashRating = $rv->OverallRating;
					}
				}
			}
			
			$object->Results[]	= $results_obj;
		}
	}else{
		
		$object->{'Count'}	= 0;
		$object->Results	= [];
		
	}
	
	print_r( json_encode($object) );
	
});

$app->post('/vehicles', function (Request $request, Response $response, $args) {
    $params	= $request->getParsedBody('manufacturer');
	
	if( isset( $params['modelYear'] ) && isset( $params['manufacturer'] ) && isset( $params['model'] ) ){
		
		$object			= new stdClass();
		
		$model_year		= $params['modelYear'];
		$manufacturer	= $params['manufacturer'];
		$model			= $params['model'];
		$url			= "https://one.nhtsa.gov/webapi/api/SafetyRatings/modelyear/$model_year/make/$manufacturer/model/$model?format=json";
		$res			= file_get_contents($url);
		$resp			= json_decode($res);
		if( $resp->{'Count'} > 0 ){
			
			$object->{'Count'}	= $resp->{'Count'};
			$object->Results	= [];
			foreach( $resp->Results as $results ){
				$results_obj				= new stdClass();
				$VehicleDescription			= $results->VehicleDescription;
				$VehicleId					= $results->VehicleId;
				$results_obj->Description	= $VehicleDescription;
				$results_obj->VehicleId		= $VehicleId;
				
				if(isset( $_GET['withRating'] ) && $_GET['withRating'] == 'true'){
					$url			= "https://one.nhtsa.gov/webapi/api/SafetyRatings/VehicleId/$VehicleId?format=json";
					$resV			= file_get_contents($url);
					// $res			= CallAPI('GET', $url);
					$respV			= json_decode($resV);
					if( $respV->{'Count'} > 0 ){
						$resultsV	= $respV->Results;
						foreach($resultsV as $rv){
							$results_obj->CrashRating = $rv->OverallRating;
						}
					}
				}
				
				$object->Results[]	= $results_obj;
			}
		}else{
			
			$object->{'Count'} = 0;
			$object->Results = [];
			
		}
		
		print_r( json_encode($object) );
		
	}else{
		echo json_encode('Params Mismatch');
	}
});


// Method: POST, PUT, GET etc
// Data: array("param" => "value") ==> index.php?param=value

function CallAPI($method, $url, $data = false)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // Optional Authentication:
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    // curl_setopt($curl, CURLOPT_USERPWD, "username:password");

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}