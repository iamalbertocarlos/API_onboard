<?php
            // $uri = $_SERVER['REQUEST_URI'];
            // $method = $_SERVER['REQUEST_METHOD'];

            // $paths = explode("/", paths($uri));
            // array_shift($paths);
            // echo "uri";
            // var_dump($uri);
            // echo "methods";
            // var_dump($method);
            // echo "paths";
            // var_dump($paths);
            // echo "1";
            // function paths($url) {
            //     $uri = parse_url($url);
            //     return $uri["path"];
            // }
            // exit();  

    header('Access-Control-Allow-Origin: *');
    require("./database.php"); //DATABASE CLASS IMPORT
    
    class Server {
        private $authorised = false;
        private $db;
        
        function __construct($dbConnection) {
            $this->db = $dbConnection;
        }

        public function serve() {
            $uri = $_SERVER['REQUEST_URI'];
            $method = $_SERVER['REQUEST_METHOD'];

            // if ($method != "PUT") {
            //     header('HTTP/1.1 403 Forbidden');
            // }
            
            $paths = explode("/", $this->paths($uri));
            $action = $paths[2];

            $apiKey = $paths[1];

            //Now we'll check the action the user wants to perform.
            //The action is stored on index 5. Right after the apiKey and right befo    re the user token if needed.
            switch ($action) {
                case 'get-random-token':
                    $put = json_decode(file_get_contents('php://input'), true);

                    if($put['password-get-token'] == md5("ViscaElCassà"))
                    {
                        $token = generateRandomToken();
                        $this->db->randomTokenInsert($token);
                    }
                    else
                    {
                        header('HTTP/1 403 Forbidden');
                        exit();
                    }

                    
                    //Check if the password is correct from the get token 
                    header("Content-type: application/json");
                    // echo json_encode($token);
                    break;
                case 'login':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $token = $put['token'];
                    $email = $put['email'];
                    $password = $put['password'];


                    //Here we'll check if the token is in the TEMPORARY_TOKENS table on the database
                    if ($this->db->checkTemporaryToken($token))
                    {
                        if ($this->db->checkUser($email, $password))
                        {
                            $userToken = generateTokenHash($email, generateRandomToken());
                            if($this->db->linkTokenWithUser($userToken,$email)) {
                                //Here the signed data (App.vue) in front will be true!!!
                                header("Content-type: application/json");
                                $data = array(
                                    'token' => $userToken
                                  );
                                echo json_encode($data);

                            }
                            else {
                                header('HTTP/1 500 Database error');
                            }

                        }
                        else
                        {
                            header('HTTP/1 403 Wrong credentials');

                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbiden
                        header('HTTP/1 403 Forbidden');
                        exit();
                    }

                    break;
                case 'my-routes': 
                    $put = json_decode(file_get_contents('php://input'), true);
                    $token = $put['token'];
                    $userValues = $this->db->checkAccessToken($token);
                    if ($userValues[0] != false)
                    {
                        $routes = $this->db->getRoutesDriver($userValues[0]["userId"]);

                        if ($userValues[0]["role"] == "driver" || $userValues[0]["role"] == "manager") {
                            //Select to return my routes values for user
                            if($userValues[0]["role"] == "driver")
                            {
                                echo json_encode($routes);
                            }
                            //Select to return my routes values for manager
                            else
                            {
                                echo "2";
                            }

                            
                        }
                        else {
                            header('HTTP/1 403 Forbidden');
                            exit();
                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbiden
                        header('HTTP/1 403 Forbidden');
                        exit();
                    }
                    
                    
                    break;
                case 'show-route':
                
                    break;
                case 'modify-route':

                    break;
                case 'delete-route':
                
                    break;
                case 'create-route':

                    break;
                case 'create-user':

                    break;
                case 'show-all-users':

                    break;
                case 'show-user':

                    break;
                case 'modify-user':

                    break;
                case 'remove-user':

                    break;

                default:
                    header('HTTP/1.1 404 Not Found');
                    break;
            }
        }

        private function paths($url) {
            $uri = parse_url($url);
            return $uri["path"];
        }
        //It returns true if the token does correspond (aka, it is valid).

        //This method checks if the token belonging to the user linked to the given api key corresponds to the token that has been given as a parameter.
        //It returns false if it doesn't correspond.
        private function validateToken($tokenInput, $apiKeyInput)  {
            $user = $this->db->retrieveOneUserFromApiKey($apiKeyInput);
            $existingUserToken = $user["token"];
            return $existingUserToken == $tokenInput;
        }
    }

    $server = new Server($db);
    $server->serve();


    function generateTokenHash($email, $token) {
        $userToken = hash("md5", $email.$token);
        return $userToken;
    }

    function generateRandomToken() {
        $length=32;
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet);

        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[random_int(0, $max-1)];
        }
        return $token;
    }
?>
