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

    /**
     * @var Utopia\Locale\Locale
     */
    protected $locale;


    public function __construct(ResponseHelper $responseHelper, array $supportedApplicationIds, Utopia\Locale\Locale $locale) {
        $this->responseHelper = $responseHelper;
        $this->supportedApplicationIds = $supportedApplicationIds;
        $this->utils = new Utils();
        $this->locale = $locale;
    }

    public function supportsRequest(Request $request): bool {
        return 'LaunchRequest' === $request->request->type;
    }

    public function handleRequest(Request $request): Response {
        if ($this->utils->getAccountLinked($request)) {
            return $this->responseHelper->respond($this->locale->getText('skill.standard.launch.hello'), true);
        } else {
            return $this->responseHelper->respond($this->locale->getText('skill.standard.launch.noLinkedAccount'), true);
        }
    }    
}


class SessionEndedRequestHandler extends AbstractRequestHandler {
    /**
     * @var ResponseHelper
     */
    protected $responseHelper;

    /**
     * @var Utopia\Locale\Locale
     */
    protected $locale;

    
    public function __construct(ResponseHelper $responseHelper, array $supportedApplicationIds, Utopia\Locale\Locale $locale) {
        $this->responseHelper = $responseHelper;
        $this->supportedApplicationIds = $supportedApplicationIds;
        $this->locale = $locale;
    }

    public function supportsRequest(Request $request): bool {
        return 'SessionEndedRequest' === $request->request->type;
    }

    public function handleRequest(Request $request): Response {
        return $this->responseHelper->respond($this->locale->getText('skill.standard.sessionEnded'), true);
    }    
}