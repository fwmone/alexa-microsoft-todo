<?

use MaxBeckers\AmazonAlexa\RequestHandler\Basic\HelpRequestHandler;
use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Response\Response;


class CustomHelpRequestHandler extends HelpRequestHandler {
    /**
     * @var ResponseHelper
     */
    protected $responseHelper;

    /**
     * @var string
     */
    protected $output;

    /**
     * @var 
     */
    private $utils;

    /**
     * @param ResponseHelper $responseHelper
     * @param string         $output
     * @param array          $supportedApplicationIds
     */
    public function __construct(ResponseHelper $responseHelper, array $supportedApplicationIds, string $list, Request $request)
    {
        $this->responseHelper          = $responseHelper;
        $this->supportedApplicationIds = $supportedApplicationIds;
        $this->utils = new Utils();

        if ($this->utils->getAccountLinked($request)) {
            $this->output = "Die Befehle lauten: Sag To Do ".ucfirst($list)." Milch und Butter hinzufügen oder Sag To Do ".ucfirst($list)." Milch entfernen.";
        } else {
            $this->output = "Bitte verknüpfe deinen Microsoft-Account mit diesem Skill über die Alexa-App oder die Amazon-Seite dieses Skills, um Einträge zu deiner To Do-Liste hinzufügen zu können.";
        }
    }

    /**
     * @inheritdoc
     */
    public function handleRequest(Request $request): Response
    {
        return $this->responseHelper->respond($this->output);
    }    
}