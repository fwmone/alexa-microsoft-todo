<?

use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\RequestHandler\AbstractRequestHandler;
use MaxBeckers\AmazonAlexa\Response\Response;

class LaunchRequestHandler extends AbstractRequestHandler {
    /**
     * @var ResponseHelper
     */
    protected $responseHelper;

    /**
     * @var Utils
     */
    protected $utils;


    public function __construct(ResponseHelper $responseHelper, array $supportedApplicationIds) {
        $this->responseHelper = $responseHelper;
        $this->supportedApplicationIds = $supportedApplicationIds;

        $this->utils = new Utils();
    }

    public function supportsRequest(Request $request): bool {
        return 'LaunchRequest' === $request->request->type;
    }

    public function handleRequest(Request $request): Response {
        if ($this->utils->getAccountLinked($request)) {
            return $this->responseHelper->respond("Hallo! Du kannst mir jederzeit sagen, was ich zu deiner To Do-Liste hinzufügen soll.", true);
        } else {
            return $this->responseHelper->respond("Hallo! Bitte verknüpfe deinen Microsoft-Account mit diesem Skill über die Alexa-App oder die Amazon-Seite dieses Skills, um Einträge zu deiner To Do-Liste hinzufügen zu können.", true);
        }
    }    
}


class SessionEndedRequestHandler extends AbstractRequestHandler {
    /**
     * @var ResponseHelper
     */
    protected $responseHelper;

    public function __construct(ResponseHelper $responseHelper, array $supportedApplicationIds) {
        $this->responseHelper = $responseHelper;
        $this->supportedApplicationIds = $supportedApplicationIds;

    }

    public function supportsRequest(Request $request): bool {
        return 'SessionEndedRequest' === $request->request->type;
    }

    public function handleRequest(Request $request): Response {
        return $this->responseHelper->respond("Tschüss, bis zum nächsten Mal!", true);
    }    
}