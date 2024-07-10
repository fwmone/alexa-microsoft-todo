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
    public function __construct(ResponseHelper $responseHelper, array $supportedApplicationIds, string $list, Request $request, Utopia\Locale\Locale $locale)
    {
        $this->responseHelper          = $responseHelper;
        $this->supportedApplicationIds = $supportedApplicationIds;
        $this->utils = new Utils();

        if ($this->utils->getAccountLinked($request)) {
            $this->output = $locale->getText('skill.help.intents', [ 'list' => $locale->getText('skill.list.'.$list) ]);
        } else {
            $this->output = $locale->getText('skill.help.accountLinking');
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