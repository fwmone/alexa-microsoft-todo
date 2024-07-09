<?php

use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest;
use MaxBeckers\AmazonAlexa\RequestHandler\AbstractRequestHandler;
use MaxBeckers\AmazonAlexa\Response\Response;

class Utils {
    public function prepareInput(string $input): array {
        if (stristr($input, " und ")) {
            $returnValue = array();
            array_walk(explode(" und ", $input), function ($value, $key) use (&$returnValue) {
                $returnValue[$key] = ucfirst($value);
            });

            return $returnValue;
        } else {
            return array(ucfirst($input));
        }
    }

    public function getList($request, $list) : string {
        require "_config.inc.php";

        $mysqli = new mysqli($config["database"]["host"], $config["database"]["user"], $config["database"]["password"], $config["database"]["database"]);
        $result = $mysqli->query("SELECT * FROM lists WHERE userId='".$request->session->user->userId."' AND list='".$list."'");
        while($obj = $result->fetch_object()) {
            $listId = $obj->listId;
        }

        if (!$listId) {
            // Start a new Guzzle client
            $client = new \GuzzleHttp\Client();

            // Set up headers
            $headers = [
                'Authorization' => 'Bearer ' . $request->session->user->accessToken,
            ];

            $response = $client->request(
                'GET',
                'https://graph.microsoft.com/v1.0/me/todo/lists',
                array('headers' => $headers)
            );

            $lists = json_decode( $response->getBody() );
            file_put_contents("output.txt", print_r($lists, true));
            foreach ($lists->value as $myList) {
                if ($list == "aufgabenliste") {
                    if ($myList->wellknownListName == "defaultList") {
                        $listId = $myList->id;
                    }
                } elseif ($list == "einkaufsliste") {
                    if (stristr($myList->displayName, "einkaufsliste") || stristr($myList->displayName, "einkaufszettel")) {
                        $listId = $myList->id;
                    }
                }
            }

            $result = $mysqli->query("INSERT INTO lists (userId, list, listId) VALUES ('".$request->session->user->userId."', '".$list."', '".$listId."') ON DUPLICATE KEY UPDATE listId='".$listId."'");
        }

        return $listId;
    }
}

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


    public function __construct(ResponseHelper $responseHelper, array $supportedApplicationIds, $list) {
        $this->responseHelper = $responseHelper;
        $this->supportedApplicationIds = $supportedApplicationIds;
        $this->list = $list;
        $this->utils = new Utils();

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
    public function __construct(ResponseHelper $responseHelper, array $supportedApplicationIds, $list) {
        parent::__construct($responseHelper, $supportedApplicationIds, $list);
    }

    public function supportsRequest(Request $request): bool {
        return $request->request instanceof IntentRequest && 'AddToListIntent' === $request->request->intent->name;
    }

    public function handleRequest(Request $request): Response {
        $this->listId = $this->utils->getList($request, $this->list);
        $input = $this->utils->prepareInput($request->request->intent->slots[0]->value);


        // Wrap our HTTP request in a try/catch block so we can decode problems
        try {
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

            return $this->responseHelper->respond("Ich habe ".implode(" und ", $input)." zur To Do ".ucfirst($this->list)." hinzugefügt.", true);

        // Decode any exceptions Guzzle throws
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();

            return $this->responseHelper->respond("Das hat leider nicht geklappt. Der Fehler lautet: " . $responseBodyAsString, true);

            echo $responseBodyAsString;
            exit();
        }

    }
}


class RemoveFromListIntentRequestHandler extends CustomRequestHandler {
    public function __construct(ResponseHelper $responseHelper, array $supportedApplicationIds, $list) {
        parent::__construct($responseHelper, $supportedApplicationIds, $list);
    }

    public function supportsRequest(Request $request): bool {
        return $request->request instanceof IntentRequest && 'RemoveFromListIntent' === $request->request->intent->name;
    }

    public function handleRequest(Request $request): Response {
        $this->listId = $this->utils->getList($request, $this->list);
        $input = $this->utils->prepareInput($request->request->intent->slots[0]->value);


        // Wrap our HTTP request in a try/catch block so we can decode problems
        try {
            // Set up headers
            $headers = [
                'Authorization' => 'Bearer ' . $request->session->user->accessToken,
            ];

            $response = $this->client->request(
                'GET',
                'https://graph.microsoft.com/v1.0/me/todo/lists/'.$this->listId.'/tasks',
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

                return $this->responseHelper->respond("Ich habe ".implode(" und ", $input)." von der To Do ".ucfirst($this->list)." entfernt.", true);

            } else {
                return $this->responseHelper->respond(implode(" und ", $input)." war nicht auf der To Do ".ucfirst($this->list), true);
            }
            
        // Decode any exceptions Guzzle throws
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();

            return $this->responseHelper->respond("Das hat leider nicht geklappt. Der Fehler lautet: " . $responseBodyAsString, true);

            echo $responseBodyAsString;
            exit();
        }        

    }
}
