 
<?php

    class DB {
        public static $conn;
        
        function __construct() {
            
        }

        //CONNECTION DATABASE
        public function connect($host, $user, $password, $database) {
            try {
                self::$conn = new PDO(
                    "mysql:host=$host;dbname=$database",
                    $user,
                    $password
                );
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } 
            catch(PDOException $e) {
                echo "Connection failed: " . $e->getMessage();
            }
        }

        //INSERT RANDOM TOKEN
        public function randomTokenInsert($token)
        {
            // var_dump($token);
            // exit();
            $SQL = "INSERT INTO TEMPORARY_TOKENS (tokenValue) VALUES(:token);";
    
            try {
                $query = (self::$conn)->prepare($SQL);
                $query->bindParam('token', $token);
    
                //Realitzem nova entrada a la Base de Dades
                $queryLines = $query->execute();

                return "OK";
    
            } catch (PDOException $e) {
                // echo "***Error at function signUp at SignUp.php***: " . "<br>" . $e->getMessage();
                return "ERROR";
            }
        }

        //CHECKS IF THE TOKEN EXISTS
        public function checkTemporaryToken($temporaryToken) {
            $output = null;
            try {
                $query = (self::$conn)->prepare('
                    SELECT *
                    FROM TEMPORARY_TOKENS
                    WHERE tokenValue = :temporaryToken');

                $query->bindParam('temporaryToken',$temporaryToken);
                $queryLines = $query->execute();

                if ($queryLines > 0) {
                    $query->setFetchMode(PDO::FETCH_ASSOC); 
                    $result = $query->fetchAll();
                    foreach($result as $line) {
                        $tokenId = $line["tokenId"];
                        $tokenValue = $line["tokenValue"];
                    }
                    $output = array("tokenId"=>$tokenId,"tokenValue"=>$tokenValue);
                    if ($output["tokenId"] == null) { return false; }
                    else { return true; }
                }
            } 
            catch(PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
            return $output;
        }

        //CHEK IF THE USER EXISTS
        public function checkUser($email, $password) {
            $output = null;
            try {
                $query = (self::$conn)->prepare('
                    SELECT *
                    FROM USERS
                    WHERE email = :email AND password = :password;' );

                $query->bindParam('email',$email);
                $query->bindParam('password',$password);
                $queryLines = $query->execute();

                if ($queryLines > 0) {
                    $query->setFetchMode(PDO::FETCH_ASSOC); 
                    $result = $query->fetchAll();
                    foreach($result as $line) {
                        $userId = $line["userId"];
                    }
                    var_dump($userId); exit();
                    $output = array("userId"=>$userId);
                    if ($output["userId"] == null) { return false; }
                    else { return true; }
                }
            } 
            catch(PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
            return $output;
        }

        //INSERT TOKEN IN USER'S TABLE
        public function linkTokenWithUser($token, $email)
        {
            try {
                $query = (self::$conn)->prepare("
                    UPDATE USERS 
                        SET accessToken = :token
                        WHERE  email = :email;
                ");
                $query->bindParam('token', $token);
                $query->bindParam('email', $email);
    
                //Realitzem nova entrada a la Base de Dades
                $queryLines = $query->execute();
                return true;
    
            } catch (PDOException $e) {
                // echo "***Error at function signUp at SignUp.php***: " . "<br>" . $e->getMessage();
                return false;
            }
        }

        //CHECKS IF THE TOKEN EXISTS
        public function checkAccessToken($accessToken) {
            $output = null;

            try {
                $query = (self::$conn)->prepare('
                    SELECT *
                    FROM USERS
                    WHERE accessToken = :token;');

                $query->bindParam('token',$accessToken);
                $queryLines = $query->execute();
                if ($queryLines > 0) {
                    $query->setFetchMode(PDO::FETCH_ASSOC); 
                    $result = $query->fetchAll();
                    // foreach($result as $line) {
                    //     $tokenId = $line["tokenId"];
                    //     $tokenValue = $line["tokenValue"];
                    // }

                    // $output = array("tokenId"=>$tokenId,"tokenValue"=>$tokenValue);
                    $output = $result;
                    return $output;
                }
                else 
                    return false;
            } 
            catch(PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
            return $output;
        }

        public function getRoutesDriver($userId)
        {
            $output = null;
            try {
                $query = (self::$conn)->prepare('
                    SELECT *
                    FROM ROUTES
                    WHERE driverId = :userId
                    ORDER BY date;');
                $query->bindParam('userId',$userId);
                $queryLines = $query->execute();

                if ($queryLines > 0) {
                    $query->setFetchMode(PDO::FETCH_ASSOC); 
                    $result = $query->fetchAll();
                    // foreach($result as $line) {
                    //     //The output will be an associative array with 2 keys: "linkInfo", which will store a link object, and "categories", which will store an array of category objects.
                    //     $output[] = array("linkInfo"=>new Link($line), "categories"=>$this->checkLinkagoriesDB($line["linkID"]));
                    // }
                    $output = $result;
                }
            } catch(PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
            return $output;
        }

        public function getRoutesManager($token, $userId)
        {

        }
        
        //El deixo perque en Miquel em fa pena perque sino ja estaría caput aquest codi
        // public function signUp($name, $email, $apiKey, $token, $visits)
        // {
        //     $SQL = "INSERT INTO USERS (name, email, apiKey, token, visits) VALUES(:name, :email, :apiKey, :token, :visits);";
    
        //     try {
        //         $query = (self::$conn)->prepare($SQL);
        //         $query->bindParam('name', $name);
        //         $query->bindParam('email', $email);
        //         $query->bindParam('apiKey', $apiKey);
        //         $query->bindParam('token', $token);
        //         $query->bindParam('visits', $visits);
    
        //         //Realitzem nova entrada a la Base de Dades
        //         $queryLines = $query->execute();

        //         return "OK";
    
        //     } catch (PDOException $e) {
        //         echo "***Error at function signUp at SignUp.php***: " . "<br>" . $e->getMessage();
        //         return "ERROR";
        //     }
        // }

        //If the email isn't associated with any user, the return value will be false.
        public function retrieveOneUser($emailInput, $nameInput) {
            $output = null;
            try {
                $query = (self::$conn)->prepare('
                    SELECT *
                    FROM USERS
                    WHERE email = :email AND name = :name;');
                $query->bindParam('email',$emailInput);
                $query->bindParam('name',$nameInput);
                $queryLines = $query->execute();

                if ($queryLines > 0) {
                    $query->setFetchMode(PDO::FETCH_ASSOC); 
                    $result = $query->fetchAll();
                    foreach($result as $line) {
                        $name = $line["name"];
                        $email = $line["email"];
                        $apiKey = $line["apiKey"];
                        $token = $line["token"];
                        $visits = $line["visits"];
                    }

                    $output = array("name"=>$name,"email"=>$email,"apiKey"=>$apiKey,"token"=>$token,"visits"=>$visits);
                    if ($output["email"] == null) return null;
                }
            } 
            catch(PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
            return $output;
        }

        public function retrieveOneUserFromApiKey($apiKeyInput) {
            $output = null;
            try {
                $query = (self::$conn)->prepare('
                    SELECT *
                    FROM USERS
                    WHERE apiKey = :apiKey');
                $query->bindParam('apiKey',$apiKeyInput);
                $queryLines = $query->execute();

                if ($queryLines > 0) {
                    $query->setFetchMode(PDO::FETCH_ASSOC); 
                    $result = $query->fetchAll();
                    foreach($result as $line) {
                        $name = $line["name"];
                        $email = $line["email"];
                        $apiKey = $line["apiKey"];
                        $token = $line["token"];
                        $visits = $line["visits"];
                    }
                    $output = array("name"=>$name,"email"=>$email,"apiKey"=>$apiKey,"token"=>$token,"visits"=>$visits);
                    if ($output["email"] == null) return null;
                }
            } 
            catch(PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
            return $output;
        }
        public function addOneApiVisit($apiKeyInput) {
            $SQL = "UPDATE USERS SET visits = visits + 1 WHERE apiKey = :apiKey";
    
            try {
                $query = (self::$conn)->prepare($SQL);
                $query->bindParam('apiKey', $apiKeyInput);
    
                //Realitzem nova entrada a la Base de Dades
                $queryLines = $query->execute();

                return "OK";
    
            } catch (PDOException $e) {
                echo "***Error at function signUp at SignUp.php***: " . "<br>" . $e->getMessage();
                return "ERROR";
            }
        }

    }	
    
    $db = new DB();
    $db->connect("127.0.0.1", "adminer", "@Grup01!!!", "onboard");
    // $db->connect("127.0.0.1", "adminer", "63Miquel63", "weatherForecastApi");

?>