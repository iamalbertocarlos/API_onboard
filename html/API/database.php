 
<?php

class DB {
    public static $conn;
    
    function __construct() {
        
    }

    /**
     * Method: connect
     * 
     *      Instanciates a connection to a database using the given parameters.
     * 
     * Parameters: 
     * 
     * 		$host - IP of the database the class will connect to.
     *      $user - MySQL username.
     *      $password - MySQL password.
     *      $database - Name of the MySQL database.
     * 
     * Returns: 
     * 
     * 		The instance of the LinkManager object from which the method has been called.
     * 
     */
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

    /**
     * Method: randomTokenInsert
     * 
     * 		Inserts a randomly generated token in the TEMPORARY_TOKENS table from the database.
     * 
     * Parameters: 
     * 
     * 		$token - the token that is to be stored.
     * 
     * Returns: 
     * 
     * 		true - if the query was performed successfully.
     *      false - if there was an error from the database side.
     * 
     */
    public function randomTokenInsert($token) {
        $SQL = "INSERT INTO TEMPORARY_TOKENS (tokenValue) VALUES(:token);";
        try {
            $query = (self::$conn)->prepare($SQL);
            $query->bindParam('token', $token);

            //Realitzem nova entrada a la Base de Dades
            $queryLines = $query->execute();

            return true;

        } catch (PDOException $e) {
            // echo "***Error at function signUp at SignUp.php***: " . "<br>" . $e->getMessage();
            return false;
        }
    }

    /**
     * Method: checkTemporaryToken
     * 
     * 		Checks if a token exists in the TEMPORARY_TOKENS.
     * 
     * Parameters: 
     * 
     * 		$temporaryToken - the token that is to be stored.
     * 
     * Returns: 
     * 
     * 		true - if the token exists in the TEMPORARY_TOKENS table.
     *      false - if the token doesn't exist in the TEMPORARY_TOKENS table.
     * 
     */
    public function checkTemporaryToken($temporaryToken) {
        $output = null;
        try {
            $query = (self::$conn)->prepare("
                SELECT *
                FROM TEMPORARY_TOKENS
                WHERE tokenValue = :temporaryToken;");

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
                if ($output["tokenId"] == "") { return false; }
                else { return true; }
            }
        } 
        catch(PDOException $e) {
            // echo "Error: " . $e->getMessage();
            $output = false;
        }
        return $output;
    }

    /**
     * Method: checkUser
     * 
     * 		Checks if an email and password correspond to those of an existing user on the USERS table in the database.
     * 
     * Parameters: 
     * 
     * 		$email - user's email.
     *      $password -  user's password already md5 encrypted.
     * 
     * Returns: 
     * 
     * 		true - if the user credentials match an entry on the USERS table.
     *      false - if the user credentials don't match an existing entry on the USERS table.
     * 
     */
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

    /**
     * Method: linkTokenWithUser
     * 
     * 		Updates the accessToken value of a given user on their database entry..
     * 
     * Parameters: 
     * 
     * 		$token - accessToken that will be placed on the user's database entry.
     *      $email - email of the user that works as an identifier.
     * 
     * Returns: 
     * 
     * 		true - if the operation was performed successfully.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function linkTokenWithUser($token, $email) {
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

    /**
     * Method: checkAccessToken
     * 
     * 		Checks if a token belongs exists in any of the entries on the USERS table in the database and, if so, returns the user's information.
     * 
     * Parameters: 
     * 
     * 		$temporaryToken - the token that is to be stored.
     * 
     * Returns: 
     * 
     * 		[] - Data of the user related to the given access token.
     *      false - If the token doesn't match any user from the USERS table.
     * 
     */
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

    /**
     * Method: getRoutesDriver
     * 
     * 		Gets all the routes linked to a driver.
     * 
     * Parameters: 
     * 
     * 		$userId - ID of the driver whose routes are to be returned.
     * 
     * Returns: 
     * 
     *      "0" - if the operation was successful but there aren't any lines associated with the query.
     * 		[] - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function getRoutesDriver($userId) {
        $output = array();
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
                $output = $result;

                if ($output == []) $output = "0";
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            $output = false;
        }
        return $output;
    }

    /**
     * Method: getRoutesManager
     * 
     * 		Gets all the routes linked to a manager.
     * 
     * Parameters: 
     * 
     * 		$userId - ID of the manager whose routes are to be returned.
     * 
     * Returns: 
     * 
     *      "0" - if the operation was successful but there aren't any lines associated with the query.
     * 		[] - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function getRoutesManager($userId) {
        $output = array();
        try {
            $query = (self::$conn)->prepare('
            SELECT r.routeId, r.managerId, r.totalKm, r.currentMapUrl, r.originalMapUrl, r.progress, r.vehiclePlate, r.date, r.origin, r.destination, r.driverId, u.name "driverName", u.surnames "driverSurname", u.email "driverEmail" 
                FROM ROUTES r
                JOIN USERS u 
                ON u.userId = r.driverId AND r.managerId = :userId
            ORDER BY date;');
            $query->bindParam('userId',$userId);
            $queryLines = $query->execute();

            if ($queryLines > 0) {
                $query->setFetchMode(PDO::FETCH_ASSOC); 
                $result = $query->fetchAll();
                $output = $result;

                if ($output == []) $output = "0";
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            $output = false;
        }
        return $output;
    }

    /**
     * Method: getRoutePoints
     * 
     * 		Gets all the route points linked to a route.
     * 
     * Parameters: 
     * 
     * 		$routeId - ID of the route whose route points are to be returned.
     * 
     * Returns: 
     * 
     *      "0" - if the operation was successful but there aren't any lines associated with the query.
     * 		[] - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function getRoutePoints($routeId) {
        $output = array();
        try {
            $query = (self::$conn)->prepare('
                SELECT *
                FROM ROUTE_POINTS
                WHERE routeId = :routeId
                ORDER BY sortingPosition;');
            $query->bindParam('routeId',$routeId);
            $queryLines = $query->execute();

            if ($queryLines > 0) {
                $query->setFetchMode(PDO::FETCH_ASSOC); 
                $result = $query->fetchAll();
                $output = $result;

                if ($output == []) $output = "0";
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            $output = false;
        }
        return $output;
    }

    /**
     * Method: insertRoutePoint
     * 
     * 		Inserts a route point to the ROUTE_POINTS table in the database.
     * 
     * Parameters: 
     * 
     * 		$routePoint:[] - Associative array containing the information of the route point. keys: "address", "isCompleted", "sortingPosition".
     * 		$routeId - ID of the route the route point belongs to.
     * 
     * Returns: 
     * 
     * 		true - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function insertRoutePoint($routePoint, $routeId) {
        $SQL = "INSERT INTO ROUTE_POINTS (routeId, address, isCompleted, sortingPosition) 
        VALUES(:routeId, :address, :isCompleted, :sortingPosition);";
        try {
            $query = (self::$conn)->prepare($SQL);
            $query->bindParam('routeId', $routeId);
            $query->bindParam('address', $routePoint["address"]);
            $query->bindParam('isCompleted', $routePoint["isCompleted"]);
            $query->bindParam('sortingPosition', $routePoint["sortingPosition"]);

            //Realitzem nova entrada a la Base de Dades
            $queryLines = $query->execute();

            return true;

        } catch (PDOException $e) {
            echo "***Error at function signUp at SignUp.php***: " . "<br>" . $e->getMessage();
            return false;
        }
    }

    /**
     * Method: removeRoutePoints
     * 
     * 		Removes all the route points associated with a route in specific from the database.
     * 
     * Parameters: 
     * 
     * 		$userId - ID of the driver whose routes are to be returned.
     * 
     * Returns: 
     * 
     * 		true - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function removeRoutePoints($routeId) {
        $SQL = "DELETE FROM ROUTE_POINTS WHERE routeId = :routeId;";
        try {
            $query = (self::$conn)->prepare($SQL);
            $query->bindParam('routeId', $routeId);

            $queryLines = $query->execute();

            return true;

        } catch (PDOException $e) {
            echo "***Error at function signUp at SignUp.php***: " . "<br>" . $e->getMessage();
            return false;
        }
    }

    /**
     * Method: getRouteDriver
     * 
     * 		Gets the detailed information related to a route in specific. This method is meant to be used in the MyRoutes view (only for drivers)
     * 
     * Parameters: 
     * 
     * 		$routeId - ID of the route whose information is to be retrieved.
     * 		$userId - userId of the driver whose route will be retrieved.
     * 
     * Returns: 
     * 
     *      "0" - if the operation was successful but there aren't any lines associated with the query.
     * 		[] - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function getRouteDriver($routeId, $userId) {
        $output = null;
        try {
            $query = (self::$conn)->prepare('
                SELECT *
                FROM ROUTES
                WHERE routeId = :routeId AND driverId = :userId');
            $query->bindParam('routeId',$routeId);
            $query->bindParam('userId',$userId);
            $queryLines = $query->execute();

            if ($queryLines > 0) {
                $query->setFetchMode(PDO::FETCH_ASSOC); 
                $result = $query->fetchAll();
                // foreach($result as $line) {
                //     //The output will be an associative array with 2 keys: "linkInfo", which will store a link object, and "categories", which will store an array of category objects.
                //     $output[] = array("linkInfo"=>new Link($line), "categories"=>$this->checkLinkagoriesDB($line["linkID"]));
                // }
                $output = $result[0];
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
        //If the route isn't found, we'll return false, else the route values will be given back to the server.
        if ( $output["routeId"] == null) return false;
        else return $output;
    }

    /**
     * Method: getRoutesManager
     * 
     * 		Gets the details of a route in specific. This method is meant to be used in the Routes Manager view (only for managers)
     * 
     * Parameters: 
     * 
     * 		$routeId - routeId of the route that is to be retrieved.
     * 		$userId - userId of the manager who is in charge of the route.
     * 
     * Returns: 
     * 
     *      "0" - if the operation was successful but there aren't any lines associated with the query.
     * 		[] - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function getRouteManager($routeId, $userId) {
        $output = null;
        try {
            $query = (self::$conn)->prepare('
                SELECT *
                FROM ROUTES
                WHERE routeId = :routeId AND managerId = :userId');
            $query->bindParam('routeId',$routeId);
            $query->bindParam('userId',$userId);
            $queryLines = $query->execute();

            if ($queryLines > 0) {
                $query->setFetchMode(PDO::FETCH_ASSOC); 
                $result = $query->fetchAll();
                $output = $result[0];
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        //If the route isn't found, we'll return false, else the route values will be given back to the server.
        if ( $output["routeId"] == null) return false;
        else return $output;
    }

    /**
     * Method: getLastAddedRouteId
     * 
     * 		Gets the routeId of the route that was last added to the ROUTES table on the database. This is useful for user or route creation methods that need to perform inserts on two different tables for an instance to be added. 
     * 
     *      For example, upon creating a new route, one or many route points need to be added to the database. Since the primary key of ROUTES is autoassigned, you need to retrieve it from the added entry in order to use it on the second instruction.
     * 
     * Returns: 
     * 
     * 		lastAddedRouteId:Integer - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function getLastAddedRouteId() {
        $output = null;
        try {
            $query = (self::$conn)->prepare('
                SELECT MAX(routeId) "routeId"
                FROM ROUTES;');
            $queryLines = $query->execute();

            if ($queryLines > 0) {
                $query->setFetchMode(PDO::FETCH_ASSOC); 
                $result = $query->fetchAll();
                $output = $result[0]["routeId"];
            }
        } catch(PDOException $e) {
            return false;
        }
        if ( $output == null) return false;
        else return $output;
    }

    /**
     * Method: getLinkedDrivers
     * 
     * 		Gets all the drivers linked to a specific manager.
     * 
     * Parameters: 
     * 
     * 		$managerId - userId of the manager whose linked drivers will be returned.
     * 
     * Returns: 
     * 
     *      "0" - if the operation was successful but there aren't any lines associated with the query.
     * 		linkedDrivers:[] - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function getLinkedDrivers($managerId) {
        $output = array();
        try {
            $query = (self::$conn)->prepare('
                SELECT d.driverId, d.defaultVehiclePlate, u.name, u.surnames, u.nationalId, u.socSecNum, u.phone, u.email 
                FROM DRIVERS d
                JOIN USERS u 
                    ON u.userId = d.driverId 
                    AND u.role = "driver"
                    AND u.isActive = "true"
                    AND managerId = :managerId;
                ');
                // FROM DRIVERS
            $query->bindParam('managerId',$managerId);
            $queryLines = $query->execute();

            if ($queryLines > 0) {
                $query->setFetchMode(PDO::FETCH_ASSOC); 
                $result = $query->fetchAll();
                foreach($result as $line) {
                    //The output will be an associative array with 2 keys: "linkInfo", which will store a link object, and "categories", which will store an array of category objects.
                    $output[] = $line;
                }
            }
            if ($output == []) return "0";
        } catch(PDOException $e) {
            // echo "Error: " . $e->getMessage();
            $output = false;
        }
        //The output will be "0" if the query was successful but no linked drivers were found, false if there was an SQL error, and an array if there are linked drivers.
        return $output;
    }

    /**
     * Method: updateRoute
     * 
     * 		Updates the fields of a route in specific.
     * 
     * Parameters: 
     * 
     * 		$modifiedRoute - Associative array containing the new values of the route. Keys: driverId, "managerId", "totalKm", "currentMapUrl", "originalMapUrl", "progress", "vehiclePlate", "date", "origin", "destination".
     * 
     * Returns: 
     * 
     *      "0" - if the operation was successful but there aren't any lines associated with the query.
     * 		[] - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function updateRoute($modifiedRoute) {
        $output = true;
        try {
            $query = (self::$conn)->prepare('
                UPDATE ROUTES 
                SET driverId = :driverId, managerId = :managerId, totalKm = :totalKm, currentMapUrl = :currentMapUrl, originalMapUrl = :originalMapUrl, progress = :progress, vehiclePlate = :vehiclePlate, date = :date, origin = :origin, destination = :destination
                WHERE routeId = :routeId;');

            $query->bindParam('routeId', $modifiedRoute["routeId"]);

            $query->bindParam('driverId', $modifiedRoute["driverId"]);
            $query->bindParam('managerId', $modifiedRoute["managerId"]);
            $query->bindParam('totalKm', $modifiedRoute["totalKm"]);
            $query->bindParam('currentMapUrl', $modifiedRoute["currentMapUrl"]);
            $query->bindParam('originalMapUrl', $modifiedRoute["originalMapUrl"]);
            $query->bindParam('progress', $modifiedRoute["progress"]);
            $query->bindParam('vehiclePlate', $modifiedRoute["vehiclePlate"]);
            $query->bindParam('date', $modifiedRoute["date"]);
            $query->bindParam('origin', $modifiedRoute["origin"]);
            $query->bindParam('destination', $modifiedRoute["destination"]);

            $queryLines = $query->execute();

            //If there were no lines modifies on the database, we'll send false back to the server so that the client can be notified that no changes have been made.
            if ( $query->rowCount() == 0) {
                $output = false;
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            $output = false;
        }
        //If the route isn't found, we'll return false, else the route values will be given back to the server.
        return $output;
    }

    /**
     * Method: updateRoute
     * 
     * 		Updates the progress value of an entry from the ROUTES table.
     * 
     * Parameters: 
     * 
     * 		$routeId:int - routeId of the route that will be modified.
     *      $progress:int - new progress value.
     * 
     * Returns: 
     * 
     *      "0" - if the operation was successful but there aren't any lines associated with the query.
     * 		[] - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function updateRouteProgress($routeId, $progress) {
        $output = true;
        try {
            $query = (self::$conn)->prepare('
                UPDATE ROUTES 
                SET progress = :progress
                WHERE routeId = :routeId;');

            $query->bindParam('routeId', $routeId);
            $query->bindParam('progress', $progress);

            $queryLines = $query->execute();

            //If there were no lines modifies on the database, we'll send false back to the server so that the client can be notified that no changes have been made.
            if ( $query->rowCount() == 0) {
                $output = false;
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            $output = false;
        }
        //If the route isn't found, we'll return false, else the route values will be given back to the server.
        return $output;
    }

    /**
     * Method: updateRoutePoint
     * 
     * 		Assigns a new value to the column isCompleted on the ROUTE_POINTS table for a specific route.
     * 
     * Parameters: 
     * 
     * 		$pointId:int - Primary key of the routePoint that will be modified.
     * 		$isCompleted:string - Can be "true" or "false".
     * 
     * Returns: 
     * 
     *      "0" - if the operation was successful but there aren't any lines associated with the query.
     * 		[] - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function updateRoutePoint($pointId, $isCompleted) {
        $output = true;
        try {
            $query = (self::$conn)->prepare('
                UPDATE ROUTE_POINTS 
                SET isCompleted = :isCompleted
                WHERE pointId = :pointId;');

            $query->bindParam('pointId', $pointId);
            $query->bindParam('isCompleted', $isCompleted);

            $queryLines = $query->execute();

            //If there were no lines modifies on the database, we'll send false back to the server so that the client can be notified that no changes have been made.
            if ( $query->rowCount() == 0) {
                $output = false;
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            $output = false;
        }
        //If the route isn't found, we'll return false, else the route values will be given back to the server.
        return $output;
    }

    /**
     * Method: removeRoute
     * 
     * 		Removes a route entry from the ROUTES table on the database.
     * 
     * Parameters: 
     * 
     * 		$routeId:int - Primary key of the route that will be removed.
     * 		$managerId:int - userId of the manager linked to the route that will be removed.
     * 
     * Returns: 
     * 
     * 		true - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function removeRoute($routeId, $managerId) {
        $output = true;
        try {
            $query = (self::$conn)->prepare('
                DELETE 
                FROM ROUTES
                WHERE routeId = :routeId AND managerId = :managerId;');
            $query->bindParam('routeId',$routeId);
            $query->bindParam('managerId',$managerId);
            $queryLines = $query->execute();

            //If there were no lines modifies on the database, we'll send false back to the server so that the client can be notified that no changes have been made.
            if ( $query->rowCount() == 0) {
                $output = false;
            }
        } catch(PDOException $e) {
            // echo "Error: " . $e->getMessage();
            $output = false;
        }
        //If the route isn't found, we'll return false, else the route values will be given back to the server.
        return $output;
    }

    /**
     * Method: createRoute
     * 
     * 		Inserts a new entry in the ROUTES table on the database.
     * 
     * Parameters: 
     * 
     * 		$newRoute:[''] - Associative array with the information of the new route. Keys: "driverId", "managerId", "totalKm", "currentMapUrl", "originalMapUrl", "progress", "vehiclePlate", "date", "origin", "destination".
     * 
     * Returns: 
     * 
     * 		true - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function createRoute($newRoute) {
        $output = true;
        // var_dump($newRoute); exit();
        if ($newRoute["totalKm"]=="") $newRoute["totalKm"] = 0;
        try {
            $query = (self::$conn)->prepare('
                INSERT INTO ROUTES (driverId, managerId, totalKm, currentMapUrl, originalMapUrl, progress, vehiclePlate, date, origin, destination)
                VALUES(:driverId,:managerId,:totalKm,:currentMapUrl,:originalMapUrl,:progress, :vehiclePlate, :date,:origin,:destination);
                ');
            $query->bindParam('driverId', $newRoute["driverId"]);
            $query->bindParam('managerId', $newRoute["managerId"]);
            $query->bindParam('totalKm', $newRoute["totalKm"]);
            $query->bindParam('currentMapUrl', $newRoute["currentMapUrl"]);
            $query->bindParam('originalMapUrl', $newRoute["originalMapUrl"]);
            $query->bindParam('progress', $newRoute["progress"]);
            $query->bindParam('vehiclePlate', $newRoute["vehiclePlate"]);
            $query->bindParam('date', $newRoute["date"]);
            $query->bindParam('origin', $newRoute["origin"]);
            $query->bindParam('destination', $newRoute["destination"]);
            $queryLines = $query->execute();

            //If there were no lines modifies on the database, we'll send false back to the server so that the client can be notified that no changes have been made.
            if ( $query->rowCount() == 0) {
                $output = false;
            }
        } catch(PDOException $e) {
            // echo "Error: " . $e->getMessage();
            $output = false;
        }
        //If the route isn't found, we'll return false, else the route values will be given back to the server.
        return $output;
    }

    /**
     * Method: createUser
     * 
     * 		Inserts a new entry in the USERS table on the database.
     * 
     * Parameters: 
     * 
     * 		$newUser:[''] - Associative array with the information of the new user. Keys: "name", "surnames", "password", "nationalId", "socSecNum", "phone", "email", "role", "isActive".
     * 
     * Returns: 
     * 
     * 		true - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function createUser($newUser) {
        $output = true;
        try {
            $query = (self::$conn)->prepare('
                INSERT INTO USERS (name, surnames, password, nationalId, socSecNum, phone, email, role, isActive)
                VALUES(:name,:surnames,:password,:nationalId,:socSecNum,:phone,:email,:role,:isActive);
                ');
            $query->bindParam('name', $newUser["name"]);
            $query->bindParam('surnames', $newUser["surnames"]);
            $query->bindParam('password', $newUser["password"]);
            $query->bindParam('nationalId', $newUser["nationalId"]);
            $query->bindParam('socSecNum', $newUser["socSecNum"]);
            $query->bindParam('phone', $newUser["phone"]);
            $query->bindParam('email', $newUser["email"]);
            $query->bindParam('role', $newUser["role"]);
            $query->bindParam('isActive', $newUser["isActive"]);
            $queryLines = $query->execute();

            //If there were no lines modifies on the database, we'll send false back to the server so that the client can be notified that no changes have been made.
            if ( $query->rowCount() == 0) {
                $output = false;
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            $output = false;
        }
        //If the route isn't found, we'll return false, else the route values will be given back to the server.
        return $output;
    }
    
    /**
     * Method: createDriver
     * 
     * 		Inserts a new entry in the DRIVERS table on the database.
     * 
     * Parameters: 
     * 
     * 		$newDriver:[''] - Associative array with the information of the new driver. Keys: "driverId", "managerId", "defaultVehiclePlate".
     * 
     * Returns: 
     * 
     * 		true - if the operation was successful.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function createDriver($newDriver) {
        $output = true;
        try {
            $query = (self::$conn)->prepare('
                INSERT INTO DRIVERS (driverId, managerId, defaultVehiclePlate)
                VALUES(:driverId,:managerId,:defaultVehiclePlate);
                ');
            $query->bindParam('driverId', $newDriver["driverId"]);
            $query->bindParam('managerId', $newDriver["managerId"]);
            $query->bindParam('defaultVehiclePlate', $newDriver["defaultVehiclePlate"]);

            $queryLines = $query->execute();

            //If there were no lines modifies on the database, we'll send false back to the server so that the client can be notified that no changes have been made.
            if ( $query->rowCount() == 0) {
                $output = false;
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            $output = false;
        }
        //If the route isn't found, we'll return false, else the route values will be given back to the server.
        return $output;
    }

    /**
     * Method: getUsers
     * 
     * 		Retrieves all the entries from the USERS table on the database.
     * 
     * Returns: 
     * 
     * 		[] - Associative array with all the user entries.
     *      "0" - If there aren't any user entries on the database.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function getUsers() {
        $output = null;
        try {
            $query = (self::$conn)->prepare('
                SELECT *
                FROM USERS;');
            $queryLines = $query->execute();

            if ($queryLines > 0) {
                $query->setFetchMode(PDO::FETCH_ASSOC); 
                $result = $query->fetchAll();
                foreach($result as $line) {                        
                    $output[] = $line;
                }
            }
            if ($output == null) return "0";
        } 
        catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            $output = false;
        }
        return $output;
    }

    /**
     * Method: getUser
     * 
     * 		Retrieves all the details related to a user on the USERS table.
     * 
     * Parameters: 
     * 
     * 		$userId:int - userId of the user whose information will be retrieved.
     * 
     * Returns: 
     * 
     *      [] - Associative array with the user's information if the operation worked. Keys: "userId", "name", "surnames", "nationalId", "socSecNum", "phone", "email", "role".
     *      false - if the operation couldn't be completed.
     * 
     */
    public function getUser($userId) {
        $output = null;
        try {
            $query = (self::$conn)->prepare('
                SELECT *
                FROM USERS
                WHERE userId = :userId;');
            $query->bindParam('userId', $userId);
            $queryLines = $query->execute();

            if ($queryLines > 0) {
                $query->setFetchMode(PDO::FETCH_ASSOC); 
                $result = $query->fetchAll();
                foreach($result as $line) {
                    $userId = $line["userId"];
                    $name = $line["name"];
                    $surnames = $line["surnames"];
                    $nationalId = $line["nationalId"];
                    $socSecNum = $line["socSecNum"];
                    $phone = $line["phone"];
                    $email = $line["email"];
                    $role = $line["role"];                        
                }
                $output = array("userId"=>$userId,"name"=>$name,"surnames"=>$surnames,"nationalId"=>$nationalId,"socSecNum"=>$socSecNum,"phone"=>$phone,"email"=>$email,"role"=>$role);
                if ($output["email"] == null) return null;
            }
        } 
        catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        return $output;
    }

    /**
     * Method: getUser
     * 
     * 		Retrieves all the details related to a driver on the DRIVERS table, together with some information of their linked manager.
     * 
     * Parameters: 
     * 
     * 		$userId:int - userId of the driver whose information will be retrieved.
     * 
     * Returns: 
     * 
     *      [] - Associative array with the driver's information if the operation worked. Keys: "driverId", "managerId", "defaultVehiclePlate", "managerName", "managerSurnames".
     *      "0" - if the operation was successful but there aren't any lines associated to the launched queries.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function getDriver($userId) {
        $output = null;
        try {
            $query = (self::$conn)->prepare('
                SELECT d.driverId, d.managerId, d.defaultVehiclePlate, m.name "managerName", m.surnames "managerSurnames"
                FROM DRIVERS d
                JOIN USERS m
                    ON m.userId = d.managerId
                WHERE driverId = :userId;');
            $query->bindParam('userId', $userId);
            $queryLines = $query->execute();

            if ($queryLines > 0) {
                $query->setFetchMode(PDO::FETCH_ASSOC); 
                $result = $query->fetchAll();
                //In case the result is 0 lines, we'll try again without the join.
                //This is to solve the case in which the driver isn't assigned to any manager.
                if ($result == []) {
                    $query = (self::$conn)->prepare('
                    SELECT driverId, managerId, defaultVehiclePlate
                    FROM DRIVERS
                    WHERE driverId = :userId;');
                    $query->bindParam('userId', $userId);
                    $queryLines = $query->execute();

                    $query->setFetchMode(PDO::FETCH_ASSOC); 
                    $result = $query->fetchAll();
                    
                    if( isset($result[0]["driverId"]) ) return $result[0];
                };

                foreach($result as $line) {
                    $driverId = $line["driverId"];
                    $managerId = $line["managerId"];
                    $defaultVehiclePlate = $line["defaultVehiclePlate"];     
                    $managerName = $line["managerName"];         
                    $managerSurnames = $line["managerSurnames"];         
                }
                $output = array(
                    "managerId"=>$managerId,
                    "defaultVehiclePlate"=>$defaultVehiclePlate,
                    "managerName"=>$managerName,
                    "managerSurnames"=>$managerSurnames
                );
                if ($driverId == null) return "0";
            }
        } 
        catch(PDOException $e) {
            // echo "Error: " . $e->getMessage();
            return false;
        }
        return $output;
    }

    /**
     * Method: getIdOfLastUser
     * 
     * 		Retrieves the userId of the last user that was added to the USERS table on the database.
     * 
     * Returns: 
     * 
     *      int - userId of the last user that was added to the USERS table.
     *      false - if the operation couldn't be completed.
     * 
     */
    public function getIdOfLastAddedUser() {
        $output = null;
        try {
            //Since the ID is auto incremented, sorting them descendingly and choosing the first result should always return the last one that was added.
            $query = (self::$conn)->prepare('
                SELECT *
                FROM USERS
                ORDER BY userId DESC
                LIMIT 1;'); 
            $queryLines = $query->execute();

            if ($queryLines > 0) {
                $query->setFetchMode(PDO::FETCH_ASSOC); 
                $result = $query->fetchAll();
                foreach($result as $line) {
                    $userId = $line["userId"];                      
                }
                $output = $userId;
            }
        } 
        catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        if ($output == null) return null;
        return $output;
    }

    /**
     * Method: linkManagerToDriver
     * 
     *      Updates the DRIVERS table to set up a specific manager's userId to a given driver's entry.
     * 
     * Parameters: 
     * 
     * 		$managerId:int - userId of the manager that will be linked to the given driver.
     * 		$linkedDriverId:int - userId of the driver that will be linked to the given manager.
     * 
     * Returns: 
     * 
     *      true - If the operation was successful.
     *      false - If the operation couldn't be completed.
     * 
     */
    public function linkManagerToDriver($managerId, $linkedDriverId) {
        $output = true;
        try {
            $query = (self::$conn)->prepare('
                UPDATE DRIVERS SET managerId = :managerId
                WHERE driverId = :linkedDriverId;
                ');
            $query->bindParam('linkedDriverId', $linkedDriverId);
            $query->bindParam('managerId', $managerId);

            $queryLines = $query->execute();

            //If there were no lines modifies on the database, we'll send false back to the server so that the client can be notified that no changes have been made.
            if ( $query->rowCount() == 0) {
                $output = false;
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            $output = false;
        }
        //If the route isn't found, we'll return false, else the route values will be given back to the server.
        return $output;
    }

    /**
     * Method: unlinkDriversFromManager
     * 
     * 		Sets the managerId to NULL on all drivers that contain a specific managerId.
     *      If the parameter $driversToOmmit is passed, the drivers whose ID appears on the array will be ommitted from the process. Therefore, they'll keep the specified managerId on their DB entry.
     * 
     * Parameters: 
     * 
     * 		managerId:int - userId of the manager that will be unlinked from all drivers.
     *      driversToOmmit:[] - Indexed array that contains the id of each driver that doesn't have to be unlinked.
     * 
     * Returns: 
     * 
     *      true - If the operation was successful.
     *      false - If the operation couldn't be completed.
     * 
     */
    public function unlinkDriversFromManager($managerId, $driversToOmmit=[]) {
        $output = true;
        try {
            //The variable $driversToOmmitBind will store the ID of the drivers we don't want to unlink the manager from.
            $driversToOmmitBind = "";
            foreach($driversToOmmit as $driverToOmmit) {
                $driversToOmmitBind = $driversToOmmitBind.$driverToOmmit.",";
            }
            $query = (self::$conn)->prepare('
                UPDATE DRIVERS SET managerId = NULL
                WHERE managerId = :managerId AND driverId NOT IN ('.$driversToOmmitBind.'-1);
                ');
            $query->bindParam('managerId', $managerId);

            $queryLines = $query->execute();

            //If there were no lines modifies on the database, we'll send false back to the server so that the client can be notified that no changes have been made.
            if ( $query->rowCount() == 0) {
                $output = false;
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            $output = false;
        }
        //If the route isn't found, we'll return false, else the route values will be given back to the server.
        return $output;
    }

    /**
     * Method: updateUser
     * 
     * 		Modifies a user's information from the USERS table on the database.
     * 
     * Parameters: 
     * 
     *      $temporaryToken - the token that is to be stored.
     * 		$modifiedUser (associative array)- required keys: name, surnames, password, nationalId, socSecNum, phone, email, role, isActive, userId.
     * 
     * Returns: 
     * 
     * 		true - if the update was performed successfully and some data was altered.
     *      "0" - if the update was performed successfully but no data was altered.
     *      false - if there was an error during the operation.
     * 
     */
    public function updateUser($modifiedUser) {
        $output = true;
        try {
            $query = (self::$conn)->prepare('
                UPDATE USERS 
                SET name = :name, surnames = :surnames, password = :password, nationalId = :nationalId, socSecNum = :socSecNum, phone = :phone, email = :email, role = :role, isActive = :isActive
                WHERE userId = :userId;');

            $query->bindParam('userId', $modifiedUser["userId"]);

            $query->bindParam('name', $modifiedUser["name"]);
            $query->bindParam('surnames', $modifiedUser["surnames"]);
            $query->bindParam('password', $modifiedUser["password"]);
            $query->bindParam('nationalId', $modifiedUser["nationalId"]);
            $query->bindParam('socSecNum', $modifiedUser["socSecNum"]);
            $query->bindParam('phone', $modifiedUser["phone"]);
            $query->bindParam('email', $modifiedUser["email"]);
            $query->bindParam('role', $modifiedUser["role"]);
            $query->bindParam('isActive', $modifiedUser["isActive"]);

            $queryLines = $query->execute();

            //If there were no lines modifies on the database, we'll send false back to the server so that the client can be notified that no changes have been made.
            if ( $query->rowCount() == 0) {
                $output = "0";
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            $output = false;
        }
        //If the user isn't found, we'll return false, else the users values will be given back to the server.
        return $output;
    }

    /**
     * Method: updateDriver
     * 
     * 		Modifies a driver's information from the DRIVERS table on the database.
     * 
     * Parameters: 
     * 
     * 		$temporaryToken - the token that is to be stored.
     *      $modifiedDriver (associative array) - keys: driverId, managerId, defaultVehiclePlate
     * 
     * Returns: 
     * 
     * 		true - If the operation was successful and some data was altered.
     *      "0" - If the operation was successful but no data was altered.
     *      false - If there was a database error.
     * 
     */
    public function updateDriver($modifiedDriver) {
        $output = true;
        try {
            $query = (self::$conn)->prepare('
                UPDATE DRIVERS
                    SET managerId = :managerId, defaultVehiclePlate = :defaultVehiclePlate
                WHERE driverId = :driverId;
                ');
            $query->bindParam('driverId', $modifiedDriver["driverId"]);
            $query->bindParam('managerId', $modifiedDriver["managerId"]);
            $query->bindParam('defaultVehiclePlate', $modifiedDriver["defaultVehiclePlate"]);

            $queryLines = $query->execute();
            //If there were no lines modifies on the database, we'll send false back to the server so that the client can be notified that no changes have been made.
            if ( $query->rowCount() == 0) {
                $output = "0";
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            $output = false;
        }
        //If the route isn't found, we'll return false, else the route values will be given back to the server.
        return $output;
    }

    /**
     * Method: getAvailableDrivers
     * 
     * 		Retrieves detailed information on each active driver on the database that isn't yet linked to any manager.
     * 
     * Parameters: 
     * 
     * 		$managerId:int - (optional) userId of a manager. If set, the output of the method will also include the drivers linked to the manager specified in this parameter.
     * 
     * Returns: 
     * 
     *      "0" - If the operation was successful but no lines are associated to the query.
     *      [] - Indexed array containing an associative array for each available driver. Keys: "driverId", "defaultVehiclePlate", "name", "surnames", "nationalId", "socSecNum", "phone", "email".
     *      false - if the operation couldn't be completed.
     * 
     */
    public function getAvailableDrivers($managerId="") {
        $output = null;
        try {

            $query = (self::$conn)->prepare('
            SELECT d.driverId, d.defaultVehiclePlate, u.name, u.surnames, u.nationalId, u.socSecNum, u.phone, u.email 
            FROM DRIVERS d
            JOIN USERS u 
                ON u.userId = d.driverId 
                AND u.role = "driver"
                AND u.isActive = "true"
                AND d.managerId IS NULL OR d.managerId = "" OR d.managerId = :managerId;
            ');
            $query->bindParam('managerId', $managerId);
            $queryLines = $query->execute();

            if ($queryLines > 0) {
                $query->setFetchMode(PDO::FETCH_ASSOC); 
                $result = $query->fetchAll();
                $output = $result;
                
                if ( $output == [] ) return "0";
                else return $output;
            }
        } 
        catch(PDOException $e) {
            //In case there was any error given by the database, we'll return false to the server side.
            echo "Error: " . $e->getMessage();
            return false;
        }
        return $output;
    }

    /**
     * Method: getAvailableManagers
     * 
     * 		Retrieves detailed information on each active manager on the database.
     * 
     * Returns: 
     * 
     *      "0" - If the operation was successful but there aren't any available managers.
     *      [] - Indexed array containing an associative array for each available manager. Keys: "userId", "name", "surnames", "nationalId", "socSecNum", "phone", "email".
     *      false - if the operation couldn't be completed.
     * 
     */
    public function getAvailableManagers() {
        $output = null;
        try {
            // The following SQL sentence 
            $query = (self::$conn)->prepare('
            SELECT userId, name, surnames, nationalId, socSecNum, phone, email 
            FROM USERS
            WHERE role = "manager" AND isActive = "true"
            ORDER BY userId;
            ');
            $queryLines = $query->execute();

            if ($queryLines > 0) {
                $query->setFetchMode(PDO::FETCH_ASSOC); 
                $result = $query->fetchAll();
                $output = $result;

                if ( $output == [] ) return "0";
                else return $output;
            }
        } 
        catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
        return $output;
    }

}	

$db = new DB();
$db->connect("127.0.0.1", "adminer", "@Grup01!!!", "onboard");
// $db->connect("127.0.0.1", "adminer", "63Miquel63", "onboard");

?>
