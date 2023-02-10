 
<?php

    class DB {
        public static $conn;
        
        function __construct() {
            
        }

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

        public function signUp($name, $email, $apiKey, $token, $visits)
        {
            $SQL = "INSERT INTO USERS (name, email, apiKey, token, visits) VALUES(:name, :email, :apiKey, :token, :visits);";
    
            try {
                $query = (self::$conn)->prepare($SQL);
                $query->bindParam('name', $name);
                $query->bindParam('email', $email);
                $query->bindParam('apiKey', $apiKey);
                $query->bindParam('token', $token);
                $query->bindParam('visits', $visits);
    
                //Realitzem nova entrada a la Base de Dades
                $queryLines = $query->execute();

                return "OK";
    
            } catch (PDOException $e) {
                echo "***Error at function signUp at SignUp.php***: " . "<br>" . $e->getMessage();
                return "ERROR";
            }
        }

        //If the email isn't associated with any user, the return value will be false.
        public function retrieveOneUser($emailInput, $nameInput) {
            $output = null;
            try {
                $query = (self::$conn)->prepare('
                    SELECT *
                    FROM USERS
                    WHERE userId = :userId;
                    ');
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

?>