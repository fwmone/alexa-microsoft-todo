<?

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

    public function getAccountLinked($request) : bool {
        if (isset($request->session->user->accessToken) && !empty($request->session->user->accessToken)) {
            return true;
        }

        return false;
    }

    public function getList($request, $list, $locale) : string {
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
            
            foreach ($lists->value as $myList) {
                if ($list == "aufgabenliste") {
                    if ($myList->wellknownListName == "defaultList") {
                        $listId = $myList->id;
                    }
                } elseif ($list == "einkaufsliste") {
                    $possibilities = explode("|", $locale->getText('skill.list.einkaufsliste.possibilities'));
                    
                    foreach($possibilities as $possibility) {
                        if (stristr($myList->displayName, $possibility)) {
                            $listId = $myList->id;
                            break;
                        }
                    }
                }
            }

            $result = $mysqli->query("INSERT INTO lists (userId, list, listId) VALUES ('".$request->session->user->userId."', '".$list."', '".$listId."') ON DUPLICATE KEY UPDATE listId='".$listId."'");
        }

        return $listId;
    }

    public function getLanguageFromIsoCode(string $isoCode) {
        return substr($isoCode, 0, 2);
    }
}

