<?php
    require("../DB/database.php"); //DATABASE CLASS IMPORT
    
    class Server {
        private $authorised = false;
        private $db;
        
        function __construct($dbConnection) {
            $this->db = $dbConnection;
        }

        public function serve() {
            $uri = $_SERVER['REQUEST_URI'];
            $method = $_SERVER['REQUEST_METHOD'];

            $paths = explode("/", $this->paths($uri));
            array_shift($paths);

            $apiKey = $paths[4];
                //Here we check if the given apiKey belongs to a user on the DB. If so, we store their data on an array.
            $user = $this->db->retrieveOneUserFromApiKey($apiKey);
            
            //If the apiKey belongs to a user, we'll keep going.
            if ($user != null) {

                //Now we'll check the action the user wants to perform.
                //The action is stored on index 5. Right after the apiKey and right before the user token if needed.
                switch ($paths[5]) {
                    case 'get-token':
                        $token = generateToken($user["email"], $user["apiKey"]);
                        
                        header("Content-type: application/json");
                        echo json_encode($token);
                        break;
                    
                    //This action requires 3 extra parameters: 
                    // ** user token via header (put).
                    // ** paths[6] -> latitude of the weather to be checked.
                    // ** paths[7] -> longitude of the weather to be checked.
                    case 'get-weather':
                        header("Content-type: application/json");

                        //First, we'll retrieve the token from the header inputs and store it on a variable
                        $receivedToken = file_get_contents("php://input");
                        
                        if ( $this->validateToken($receivedToken, $paths[4]) ) {//$paths[6] is the token, and $paths[4] is the api key.
                                //Now we'll store the latitude and longitude we received via URL on separate variables.
                            $lat = $paths[6];
                            $lon = $paths[7];
                                // Now we'll get the weather info by adding the coordinates we received from the client on our openMeteoApi call.
                            $openMeteoApiCallResult = json_decode(file_get_contents("https://api.open-meteo.com/v1/forecast?latitude=$lat&longitude=$lon&hourly=temperature_2m&current_weather=true&hourly=relativehumidity_2m&daily=temperature_2m_max,temperature_2m_min,weathercode,precipitation_sum&timezone=CET&past_days=2"));

                                //And now we'll return the weather data back to the client.
                            echo json_encode($openMeteoApiCallResult);

                            $this->db->addOneApiVisit($paths[4]);
                            exit();
                        }
                        else { //If the token isn't a valid one, we'll return forbidden.
                            header('HTTP/1.1 403 Forbidden');
                        }
                        break;
                    
                    default:
                        header('HTTP/1.1 404 Not Found');
                        break;
                }
            }
            
            exit();
        }

        private function paths($url) {
            $uri = parse_url($url);
            return $uri["path"];
        }

        //This method checks if the token belonging to the user linked to the given api key corresponds to the token that has been given as a parameter.
        //It returns true if the token does correspond (aka, it is valid).
        //It returns false if it doesn't correspond.
        private function validateToken($tokenInput, $apiKeyInput)  {
            $user = $this->db->retrieveOneUserFromApiKey($apiKeyInput);
            $existingUserToken = $user["token"];
            return $existingUserToken == $tokenInput;
        }
    }

    $server = new Server($db);
    $server->serve();


    function generateToken($email, $apiKey) {
        $emailHash = hash("md5", $email);
        $apiKeyHash = hash("md5", $apiKey);
        $emailAndApiKeyHash = hash("md5", $email.$apiKey);
    
        $token = $emailHash.".".$apiKeyHash.".".$emailAndApiKeyHash;
        return $token;
    }
?>
