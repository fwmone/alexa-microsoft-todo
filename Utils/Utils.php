<?

class Utils {
    public function extractItemAndList($locale, $defaultList, string $input) : array {
        $rawInput = trim($input);

        // Liste und Task trennen, wenn Listenmuster erkannt wird
        $pattern = '/^(.*?)\s+(?:' . $locale->getText('skill.intent.addItem.list.possibilities') . ')\s+(.+?)\s+' . $locale->getText('skill.intent.addItem.list') . '$/i';
    
        if (preg_match($pattern, $rawInput, $matches)) {
            $item = trim($matches[1]);
            $list = preg_replace('/\s*' . $locale->getText('skill.intent.addItem.list') . '$/i', '', trim($matches[2]));  // " Liste" entfernen
            return [
                'item' => $item,
                'list' => $list,
                'customList' => true
            ];
        }
    
        // Kein Listenmuster erkannt → alles ist der Task, Standardliste
        return [
            'item' => $rawInput,
            'list' => $defaultList,
            'customList' => false
        ];        
    }

    public function prepareInput($locale, string $input): array {
        if (stristr($input, " " . $locale->getText("generic.and") . " ")) {
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
                } else {
                    if (stristr($myList->displayName, $list)) {
                        $listId = $myList->id;
                        break;
                    }
                }
            }

            if ($listId != "") {
                $result = $mysqli->query("INSERT INTO lists (userId, list, listId) VALUES ('".$request->session->user->userId."', '".$list."', '".$listId."') ON DUPLICATE KEY UPDATE listId='".$listId."'");
            }
        }

        return $listId;
    }

    public function getLanguageFromIsoCode(string $isoCode) {
        return substr($isoCode, 0, 2);
    }
}

