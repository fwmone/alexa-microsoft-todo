<?

use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\RequestHandler\Basic\CancelRequestHandler;
use MaxBeckers\AmazonAlexa\RequestHandler\Basic\FallbackRequestHandler;
use MaxBeckers\AmazonAlexa\RequestHandler\Basic\NavigateHomeRequestHandler;
use MaxBeckers\AmazonAlexa\RequestHandler\Basic\StopRequestHandler;
use MaxBeckers\AmazonAlexa\RequestHandler\RequestHandlerRegistry;
use MaxBeckers\AmazonAlexa\Validation\RequestValidator;
use Utopia\Locale\Locale;

require './vendor/autoload.php';
require 'Handlers/StandardHandler.php';
require 'Handlers/BuiltInIntentHandler.php';
require 'Handlers/RequestHandler.php';
require 'Utils/Utils.php';

$utils = new Utils();

/**
 * Simple example for request handling workflow with help example
 * loading json
 * creating request
 * validating request
 * adding request handler to registry
 * handling request
 * returning json response
 */
$requestBody = file_get_contents('php://input');
if ($requestBody) {
    $alexaRequest = Request::fromAmazonRequest($requestBody, $_SERVER['HTTP_SIGNATURECERTCHAINURL'], $_SERVER['HTTP_SIGNATURE']);

    if (!$alexaRequest) {
        http_response_code(400);
        exit();
    }

    // Request validation
    $validator = new RequestValidator();
    $validator->validate($alexaRequest);

    // Localization
    Locale::setLanguageFromJSON($alexaRequest->request->locale, "Localizations/".$utils->getLanguageFromIsoCode($alexaRequest->request->locale).".json"); 
    $locale = new Locale($alexaRequest->request->locale);

    // create all needed handlers
    $responseHelper = new ResponseHelper();

    $addToListIntentRH = new AddToListIntentRequestHandler($responseHelper, $skillId, $list, $locale);
    $removeFromListIntentRH = new RemoveFromListIntentRequestHandler($responseHelper, $skillId, $list, $locale);
    $getListIntentRH = new getListIntentRequestHandler($responseHelper, $skillId, $list, $locale);
    $getCustomListIntentRH = new getCustomListIntentRequestHandler($responseHelper, $skillId, $list, $locale);

    $launchRH = new LaunchRequestHandler($responseHelper, $skillId, $locale);
    $sessionEndedRH = new SessionEndedRequestHandler($responseHelper, $skillId, $locale);

    $cancelRH = new CancelRequestHandler($responseHelper, 'cancel text', $skillId);
    $fallbackRH = new FallbackRequestHandler($responseHelper, 'fallback text', $skillId);
    $navigateHomeRH = new NavigateHomeRequestHandler($responseHelper, 'navigate home text', $skillId);
    $helpRH = new CustomHelpRequestHandler($responseHelper, $skillId, $list, $alexaRequest, $locale);
    $stopRH = new StopRequestHandler($responseHelper, 'stop text', $skillId);

    // add handlers to registry
    $requestHandlerRegistry = new RequestHandlerRegistry([
        $addToListIntentRH,
        $removeFromListIntentRH,
        $getListIntentRH,
        $getCustomListIntentRH,
        $launchRH,
        $sessionEndedRH,
        $cancelRH,
        $fallbackRH,
        $navigateHomeRH,
        $helpRH,
        $stopRH,
    ]);

    // handle request
    $requestHandler = $requestHandlerRegistry->getSupportingHandler($alexaRequest);
    $response = $requestHandler->handleRequest($alexaRequest);

    // render response
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    http_response_code(400);
}