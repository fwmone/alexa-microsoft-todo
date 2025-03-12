<?php

use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest;
use MaxBeckers\AmazonAlexa\RequestHandler\AbstractRequestHandler;
use MaxBeckers\AmazonAlexa\Response\Response;

class CustomRequestHandler extends AbstractRequestHandler { 
    /**
     * @var ResponseHelper
     */
    protected $responseHelper;

    /**
     * @var string
     */
    protected $list;

    /**
     * @var string
     */
    protected $listId;

    /**
     * @var Utils
     */
    protected $utils;

    /**
     * @var 
     */
    protected $client;

    /**
     * @var Utopia\Locale\Locale
     */
    protected $locale;


    public function __construct(ResponseHelper $responseHelper, array $supportedApplicationIds, $list, Utopia\Locale\Locale $locale) {
        $this->responseHelper = $responseHelper;
        $this->supportedApplicationIds = $supportedApplicationIds;
        $this->list = $list;
        $this->utils = new Utils();
        $this->locale = $locale;

        // Start a new Guzzle client
        $this->client = new \GuzzleHttp\Client();
    }

    public function supportsRequest(Request $request): bool {
        // empty
    }

    public function handleRequest(Request $request): Response {
        // empty
    }    

}

class AddToListIntentRequestHandler extends CustomRequestHandler {
    public function __construct(ResponseHelper $responseHelper, array $supportedApplicationIds, $list, $locale) {
        parent::__construct($responseHelper, $supportedApplicationIds, $list, $locale);
    }

    public function supportsRequest(Request $request): bool {
        return $request->request instanceof IntentRequest && 'AddToListIntent' === $request->request->intent->name;
    }

    public function handleRequest(Request $request): Response {
        $this->listId = $this->utils->getList($request, $this->list, $this->locale);
        $input = $this->utils->prepareInput($request->request->intent->slots[0]->value);


        // Wrap our HTTP request in a try/catch block so we can decode problems
        try {
            if (!$this->listId) {
                return $this->responseHelper->respond($this->locale->getText('skill.intent.error', [ 'error' => $this->locale->getText('skill.intent.error.nolist') ]), true);
            }
            
            
            // Set up headers
            $headers = [
                'Authorization' => 'Bearer ' . $request->session->user->accessToken,
            ];

            foreach ($input as $key => $value) {
                // Set up our request to the API
                $response = $this->client->post(
                    'https://graph.microsoft.com/v1.0/me/todo/lists/'.$this->listId.'/tasks',
                    array( 
                        'headers' => $headers,
                        GuzzleHttp\RequestOptions::JSON => ["title" => $value]
                    )
                );

            }

            return $this->responseHelper->respond($this->locale->getText('skill.intent.addItem', [ 'input' => implode(" ".$this->locale->getText("generic.and")." ", $input), 'list' => $this->locale->getText('skill.list.'.$this->list) ]), true);

        // Decode any exceptions Guzzle throws
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();

            return $this->responseHelper->respond($this->locale->getText('skill.intent.error', [ 'error' => $responseBodyAsString ]), true);
        }

    }
}

class RemoveFromListIntentRequestHandler extends CustomRequestHandler {
    public function __construct(ResponseHelper $responseHelper, array $supportedApplicationIds, $list, $locale) {
        parent::__construct($responseHelper, $supportedApplicationIds, $list, $locale);
    }

    public function supportsRequest(Request $request): bool {
        return $request->request instanceof IntentRequest && 'RemoveFromListIntent' === $request->request->intent->name;
    }

    public function handleRequest(Request $request): Response {
        $this->listId = $this->utils->getList($request, $this->list, $this->locale);
        $input = $this->utils->prepareInput($request->request->intent->slots[0]->value);


        // Wrap our HTTP request in a try/catch block so we can decode problems
        try {
            // Set up headers
            $headers = [
                'Authorization' => 'Bearer ' . $request->session->user->accessToken,
            ];

            $response = $this->client->request(
                'GET',
                'https://graph.microsoft.com/v1.0/me/todo/lists/'.$this->listId.'/tasks?$filter=status ne \'completed\'',
                array('headers' => $headers)
            );

            $listItems = json_decode( $response->getBody() );
            $removeIds = array();

            foreach ($input as $value) {
                foreach ($listItems->value as $item) {
                    if ($item->title == $value) {
                        $removeIds[] = $item->id;
                    }
                }
            }

            if (count($removeIds) > 0) {
                foreach ($removeIds as $id) {
                    // Set up our request to the API
                    $response = $this->client->delete(
                        'https://graph.microsoft.com/v1.0/me/todo/lists/'.$this->listId.'/tasks/'.$id,
                        array( 
                            'headers' => $headers
                        )
                    );
                }

                return $this->responseHelper->respond($this->locale->getText('skill.intent.removeItem', [ 'input' => implode(" ".$this->locale->getText("generic.and")." ", $input), 'list' => $this->locale->getText('skill.list.'.$this->list) ]), true);

            } else {
                return $this->responseHelper->respond($this->locale->getText('skill.intent.notOnList', [ 'input' => implode(" ".$this->locale->getText("generic.and")." ", $input), 'list' => $this->locale->getText('skill.list.'.$this->list) ]), true);
            }
            
        // Decode any exceptions Guzzle throws
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();

            return $this->responseHelper->respond($this->locale->getText('skill.intent.error', [ 'error' => $responseBodyAsString ]), true);

            echo $responseBodyAsString;
            exit();
        }        

    }
}

class getListIntentRequestHandler extends CustomRequestHandler {
    public function __construct(ResponseHelper $responseHelper, array $supportedApplicationIds, $list, $locale) {
        parent::__construct($responseHelper, $supportedApplicationIds, $list, $locale);
    }

    public function supportsRequest(Request $request): bool {
        return $request->request instanceof IntentRequest && 'GetListIntent' === $request->request->intent->name;
    }

    public function handleRequest(Request $request): Response {
        $this->listId = $this->utils->getList($request, $this->list, $this->locale);


        // Wrap our HTTP request in a try/catch block so we can decode problems
        try {
            // Set up headers
            $headers = [
                'Authorization' => 'Bearer ' . $request->session->user->accessToken,
            ];

            $response = $this->client->request(
                'GET',
                'https://graph.microsoft.com/v1.0/me/todo/lists/'.$this->listId.'/tasks?$filter=status ne \'completed\'',
                array('headers' => $headers)
            );

            $rawlistItems = json_decode( $response->getBody() );
            foreach ($rawlistItems->value as $item) {
                $listItems[] = $item->title;
            }
            $countListItems = count($listItems);

            if ($countListItems > 10) {
                $reducedListItems = array_slice($listItems, 0, 10);
                return $this->responseHelper->respond($this->locale->getText('skill.intent.getLongList', [ 'count' => $countListItems, 'items' => implode(" ".$this->locale->getText("generic.nextListItem")." ", $reducedListItems), 'list' => $this->locale->getText('skill.list.'.$this->list) ]), true);
            } elseif ($countListItems == 1) {
                return $this->responseHelper->respond($this->locale->getText('skill.intent.getListOneItem', [ 'count' => $countListItems, 'items' => implode(" ".$this->locale->getText("generic.nextListItem")." ", $listItems), 'list' => $this->locale->getText('skill.list.'.$this->list) ]), true);
            } elseif ($countListItems == 0) {
                return $this->responseHelper->respond($this->locale->getText('skill.intent.getListEmpty', [ 'list' => $this->locale->getText('skill.list.'.$this->list) ]), true);
            } else {
                return $this->responseHelper->respond($this->locale->getText('skill.intent.getList', [ 'count' => $countListItems, 'items' => implode(" ".$this->locale->getText("generic.nextListItem")." ", $listItems), 'list' => $this->locale->getText('skill.list.'.$this->list) ]), true);
            }

            
        // Decode any exceptions Guzzle throws
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();

            return $this->responseHelper->respond($this->locale->getText('skill.intent.error', [ 'error' => $responseBodyAsString ]), true);

            echo $responseBodyAsString;
            exit();
        }        

    }
}

