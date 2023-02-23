<?php
    header('Access-Control-Allow-Origin: *');
    require("./database.php"); //DATABASE CLASS IMPORT
    
    class Server {
        private $authorised = false;
        private $db;
        
        function __construct($dbConnection) {
            $this->db = $dbConnection;
        }

        /**
         * Method: serve
         * 
         * 		Deploys the api functionalities. This method will be read each time the API is accessed. Its purpose is to break down the request details into uri, method and paths, to then determine an action and launch it.
         * 
         */
        public function serve() {
            $uri = $_SERVER['REQUEST_URI'];
            $method = $_SERVER['REQUEST_METHOD'];

            // if ($method != "PUT") {
            //     header('HTTP/1.1 403 Forbidden');
            // }
            
            $paths = explode("/", $this->paths($uri));
            $action = $paths[2];

            if ($method=="OPTIONS") {
                header("HTTP/1.1 200 OK");
                exit();
            }

            //Now we'll check the action the user wants to perform:
            switch ($action) {
                /** 
                *
                * Method: get-random-token
                *
                *       Checks if the received password matches the one we have stored on the server-side, and if so, generates a temporary token, which will be sent back to the user and stored on our database.
                *
                * Parameters:
                *
                *       password-get-token - encrypted password stored on the client side application that enables us to make the first request token-based as well.
                * 
                * Returns:
                *
                *       randomAccessToken - #.
                */  
                case 'get-random-token':
                    $put = json_decode(file_get_contents('php://input'), true);

                    if($put['password-get-token'] == md5("ViscaElCassà")) {
                        $temporaryToken = generateRandomToken();
                        if ($this->db->randomTokenInsert($temporaryToken))
                            echo json_encode($temporaryToken);
                        else 
                            echo json_encode(false);
                    }
                    else {
                        echo md5("ViscaElCassà");
                        header('HTTP/1 403 Forbidden');
                    }
                    
                    //Check if the password is correct from the get token 
                    header("Content-type: application/json");
                    break;

                /** 
                *
                * Method: login
                *
                *       Checks if a temporary token and user credentials match any entries on our database and returns an access token if they do.
                *
                * Parameters:
                *
                *       temporaryToken - #.
                *       email - #.
                *       password - #.
                *
                * Returns:
                *
                *       accessToken -
                */
                case 'login':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $temporaryToken = $put['temporaryToken'];

                    //in case the PUT parameters aren't right, we'll return a 422 error.
                    if ( !isset($put["email"]) || !isset($put["password"]) ) {
                        echo json_encode("Unprocessable Entity: you may be missing the a parameter.");
                        header('HTTP/1 422 Unprocessable Entity');
                        exit();
                    }

                    $email = $put['email'];
                    $password = $put['password'];

                    //Here we'll check if the temporaryToken is in the TEMPORARY_TOKENS table on the database
                    if ($this->db->checkTemporaryToken($temporaryToken)) {
                        //Next, we'll check if the given user data matches any entry on our database.
                        if ($this->db->checkUser($email, $password)) {
                            $accessToken = generateTokenHash($email, generateRandomToken());
                            //Now we'll insert the current token in the user's database table.

                            if($this->db->linkTokenWithUser($accessToken,$email)) {
                                //If the insert was done correctly, we'll send the access token to the client side, and they'll be able to use it on the next API calls.
                                $userValues = $this->db->checkAccessToken($accessToken)[0];
                                header("Content-type: application/json");
                                $data = array(
                                    'token' => $accessToken,
                                    'role' => $userValues["role"]
                                  );
                                echo json_encode($data);
                            }
                            else {
                                //If the insert fails, which should rarely happen, we'll notify the front-end about the database error.
                                header('HTTP/1 500 Database error');
                            }
                        }
                        else {
                            //If the checkUser function returns false it's because the user data doesn't match an existing one, so we'll return 403.
                            header("Content-type: application/json");
                            $data = "false";
                            echo json_encode($data);
                            // header('HTTP/1 403 Wrong credentials');
                        }
                    }
                    else {
                        //If the token doesn't exist, we return Forbidden
                        header('HTTP/1 403 Forbidden');
                        echo json_encode("false");
                        
                    }

                    break;
                
                /**
                *
                * Method: get-routes
                *
                *       Gets detailed information of all the routes associated with a user.
                *
                * Parameters:
                *
                *       accessToken - #.
                *
                * Returns:
                *
                *       {...} - routes data.
                */
                case 'get-routes':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];
                    if ($userValues != null) {
                        //Select to return my routes values for user
                        if($userValues["role"] == "driver") {
                            $routes = $this->db->getRoutesDriver($userValues["userId"]);
                            echo json_encode($routes);
                        }
                        //Select to return my routes values for manager
                        else if ($userValues["role"] == "manager") {
                            $routes = $this->db->getRoutesManager($userValues["userId"]);
                            echo json_encode($routes);
                        }
                        else {
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbiden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;
               
                /** 
                *
                * Method: get-route
                *
                *       Gets detailed information of a specific route.
                *
                * Parameters:
                *
                *       accessToken - .#
                *       routeId - #.
                *
                * Returns:
                *
                *       routeData - object with the route details.
                *       {...} - indexed array with the routePoint objects linked with the route.
                */
                case 'get-route':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];
                    if ($userValues != false) {
                        //in case the PUT parameters aren't right, we'll return a 422 error.
                        if ( !isset($put["routeId"]) ) {
                            echo json_encode("Unprocessable Entity: you may be missing the a parameter.");
                            header('HTTP/1 422 Unprocessable Entity');
                            exit();
                        }

                        $routeId = $put['routeId'];
                        
                        //Now we'll check the user's role to see what action to perform.
                        if($userValues["role"] == "driver") {
                            $route = $this->db->getRouteDriver($routeId, $userValues["userId"]);
                            $routePoints = $this->db->getRoutePoints($routeId);
                            $output = array("routeData"=>$route, "routePoints"=>$routePoints);
                            
                            if ( $route == false ){ header('HTTP/1 403 Forbidden') ;}
                            else { echo json_encode($output); }
                        }
                        else if ($userValues["role"] == "manager") { //If the user is a manager, we need to get their linked drivers as well as the route.
                            $route = $this->db->getRouteManager($routeId, $userValues["userId"]);
                            $routePoints = $this->db->getRoutePoints($routeId);
                            $output = array("routeData"=>$route, "routePoints"=>$routePoints);
                            // $linkedDrivers = $this->db->getManagerDrivers($userValues["userId"]);

                            if ($route == false){
                                header('HTTP/1 403 Forbidden');
                            }
                            else {
                                echo json_encode($output);
                            }
                        }
                        else {
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbiden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;
                
                /**
                *
                * Method: get-route-points
                *
                *       Retrieves detailed information on each route point linked to a specific route.
                *     
                * Parameters:
                *
                *       accessToken - #.
                *       routeId - #.
                *
                * Returns:
                *
                *       routePoints - indexed array with the routePoint objects linked with the route.
                */
                case 'get-route-points':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];
                    if ($userValues != false) {
                        //in case the PUT parameters aren't right, we'll return a 422 error.
                        if ( !isset($put["routeId"]) ) {
                            echo json_encode("Unprocessable Entity: you may be missing the a parameter.");
                            header('HTTP/1 422 Unprocessable Entity');
                            exit();
                        }

                        $routeId = $put['routeId'];
                        
                        //Now we'll check the user's role to see what action to perform.
                        if($userValues["role"] == "driver") {
                            $routePoints = $this->db->getRoutePoints($routeId);
                            $output = $routePoints;
                            
                            // if ( $routePoints == false ){ header('HTTP/1 403 Forbidden') ;}
                            // else { 
                                echo json_encode($output); 
                            // }
                        }
                        else if ($userValues["role"] == "manager") { //If the user is a manager, we need to get their linked drivers as well as the route.
                            $routePoints = $this->db->getRoutePoints($routeId);
                            $output = $routePoints;
                            
                            // if ( $routePoints == false ){ header('HTTP/1 403 Forbidden') ;}
                            // else { 
                                echo json_encode($output); 
                            // }
                        }
                        else {
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbiden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;
                
                /**    
                *
                * Method: modify-route
                *
                *       Updates the information on the database of a route in specific.
                *
                * Parameters:
                *
                *       accessToken - #. 
                *       routeId - #.
                *       driverId - #.
                *       managerId - #.
                *       totalKm - #.
                *       currentMapUrl - #.
                *       originalMapUrl - #.
                *       progress - #.
                *       vehiclePlate - #.
                *       date - #.
                *       origin - #.
                *       destination - #.
                *       routePoints - indexed array with the routePoint objects linked with the route.
                *
                * Returns:
                *
                *       completion state - (true-->success or false-->failed).
                */ 
                case 'modify-route':  
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];
                    //Correct user check.
                    if ($userValues != false) {
                        //If the user is manager can change the route.
                        if($userValues["role"] == "manager") {
                            //in case the PUT parameters aren't right, we'll return a 422 error.
                            if ( 
                                !isset($put["routeId"]) || 
                                !isset($put["driverId"]) || 
                                !isset($put["totalKm"]) || 
                                !isset($put["currentMapUrl"]) || 
                                !isset($put["originalMapUrl"]) || 
                                !isset($put["progress"]) || 
                                !isset($put["vehiclePlate"]) || 
                                !isset($put["date"]) || 
                                !isset($put["origin"]) ||
                                !isset($put["destination"]) ||
                                !isset($put["routePoints"])
                            ) {
                                echo json_encode("Unprocessable Entity: you may be missing the a parameter.");
                                header('HTTP/1 422 Unprocessable Entity');
                                exit();
                            }
                            $modifiedRoute = array(
                                "routeId"=>$put["routeId"],
                                "driverId"=>$put["driverId"], 
                                "managerId"=>$userValues["userId"], //the managerId will be retrieved from the user data we got from the token.
                                "totalKm"=>$put["totalKm"], 
                                "currentMapUrl"=>$put["currentMapUrl"], 
                                "originalMapUrl"=>$put["originalMapUrl"], 
                                "progress"=>$put["progress"], 
                                "vehiclePlate"=>$put["vehiclePlate"], 
                                "date"=>$put["date"], 
                                "origin"=>$put["origin"]
                            );

                            //If the route has been changed, the following method will return true, else, false.
                            $result = $this->db->updateRoute($modifiedRoute);
                            $routePointsStatus = true;
                            if ( !$this->db->removeRoutePoints($put["routeId"]) ) $result = false;

                            foreach($put["routePoints"] as $routePoint) {
                                if(!$this->db->insertRoutePoint($routePoint, $put["routeId"])) $result = false;
                            }
                            
                            echo json_encode($result); //The client will receive true or false depending on the result of the operation.
                        }
                        else {
                            //If the user isn't a manager, we'll return forbidden.
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbiden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;
                
                case 'route-status-update':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];
                    //Correct user check.
                    if ($userValues != false) {
                        //If the user is driver can update the route.
                        if($userValues["role"] == "driver") {
                            $resultProgress = $this->db->updateRouteProgress($put['routeId'],$put['progress']);
                            $resultPonit = $this->db->updateRoutePoint($put['pointId'],$put['isCompleted']);
                            echo json_encode($resultProgress, $resultPonit); //The client will receive true or false depending on the result of the operation.
                        }
                        else {
                            //If the user isn't a manager, we'll return forbidden.
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbiden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;

                /** 
                *
                * Method: remove-route
                *
                *       Removes a route entry and its linked route points from the database.
                *
                * Parameters:
                *
                *       accessToken - #.
                *       routeId - #.
                *
                * Returns:
                *
                *       completion state - (true-->success or false-->failed).
                */
                case 'remove-route':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];
                    
                    //Correct user check.
                    if ($userValues != false) {
                        //Select to return my routes values for user
                        if($userValues["role"] == "manager") {
                            //in case the PUT parameters aren't right, we'll return a 422 error.
                            if ( !isset($put["routeId"]) ) {
                                echo json_encode("Unprocessable Entity: you may be missing the a parameter.");
                                header('HTTP/1 422 Unprocessable Entity');
                                exit();
                            }
                            $routeId = $put["routeId"];
                            $result2 = $this->db->removeRoutePoints($routeId);
                            //If the route has been removed, the following method will return true, else, false.
                            
                            $result = $this->db->removeRoute($routeId, $userValues["userId"]);
                            echo json_encode($result); //The client will receive true or false depending on the result of the operation.
                        }
                        else {
                            //If the user isn't a manager, we'll return forbidden.
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbiden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;
                
                /** 
                *
                * Method: create-route
                *
                *       Inserts a new route and its route points in the database.
                *
                * Parameters:
                *
                *       accessToken - #.
                *       driverId - #.
                *       totalKm - #.
                *       currentMapUrl - #.
                *       originalMapUrl - #.
                *       progress - #.
                *       vehiclePlate - #.
                *       date - #.
                *       origin - #.
                *       destination - #.
                *       routePoints - #.
                *
                * Returns:
                *
                *       completion state - (true-->success or false-->failed).
                */   
                case 'create-route':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];
                    if ($userValues != false) {                        
                        //Now we'll check the user's role to see what action to perform.
                        if ($userValues["role"] == "manager") {

                            //in case the PUT parameters aren't right, we'll return a 422 error.
                            if ( 
                                !isset($put["driverId"]) ||
                                !isset($put["totalKm"]) || 
                                !isset($put["currentMapUrl"]) || 
                                !isset($put["originalMapUrl"]) || 
                                !isset($put["progress"]) || 
                                !isset($put["vehiclePlate"]) || 
                                !isset($put["date"]) || 
                                !isset($put["origin"]) || 
                                !isset($put["destination"]) || 
                                !isset($put["routePoints"])
                            ) {
                                echo json_encode("Unprocessable Entity: you may be missing the a parameter.");
                                header('HTTP/1 422 Unprocessable Entity');
                                exit();
                            }
                            $newRoute = array(
                                "driverId"=>$put["driverId"], 
                                "managerId"=>$userValues["userId"], //the managerId will be retrieved from the user data we got from the token.
                                "totalKm"=>$put["totalKm"], 
                                "currentMapUrl"=>$put["currentMapUrl"], 
                                "originalMapUrl"=>$put["originalMapUrl"], 
                                "progress"=>$put["progress"], 
                                "vehiclePlate"=>$put["vehiclePlate"], 
                                "date"=>$put["date"], 
                                "origin"=>$put["origin"], 
                                "destination"=>$put["destination"]
                            );

                            //This statement exectutes the SQL instruction and stores the completion status (true or false) on a variable.
                            $routeCreationStatus = $this->db->createRoute($newRoute);

                            //We need to store the routeId of the route we just added in order to insert the routePoints.
                            $routeId = $this->db->getLastAddedRouteId();
                            $routePointsStatus = true;
                            foreach($put["routePoints"] as $routePoint) {
                                if(!$this->db->insertRoutePoint($routePoint, $routeId)) $routePointsStatus = false;
                            }

                            //If the operation is successful, we'll return true, else, false.
                            echo json_encode($routeCreationStatus);
                        }
                        else { //If the user isn't a manager, we'll return a 403 error.
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbiden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;
                
                /** 
                *
                * Method: create-user
                *
                *       Inserts a user entry in the database.
                *
                * Parameters:
                *
                *       accessToken - #.
                *       name - #.
                *       surnames - #.
                *       password - #.
                *       nationalId - #.
                *       socSecNum - #.
                *       phone - #.
                *       email - #.
                *       role - #.
                *       isActive - #.
                *       linkedDrivers - (array of IDs)(Only if the role is manager).
                *
                * Returns:
                *
                *       completion state - (true-->success or false-->failed) .
                */
                case 'create-user':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];
                    
                    if ($userValues != false) {                        
                        //Now we'll check the user's role to see what action to perform.
                        if ($userValues["role"] == "admin") {
                            //in case the PUT parameters aren't right, we'll return a 422 error.
                            if ( 
                                !isset($put["name"]) || 
                                !isset($put["surnames"]) || 
                                !isset($put["password"]) || 
                                !isset($put["nationalId"]) || 
                                !isset($put["socSecNum"]) || 
                                !isset($put["phone"]) || 
                                !isset($put["email"]) || 
                                !isset($put["role"]) || 
                                !isset($put["isActive"]) 
                            ) {
                                echo json_encode("Unprocessable Entity: you may be missing the a parameter.");
                                header('HTTP/1 422 Unprocessable Entity');
                                exit();
                            }

                            $newUser = array(
                                "name"=>$put["name"],
                                "surnames"=>$put["surnames"],
                                "password"=>$put["password"],
                                "nationalId"=>$put["nationalId"],
                                "socSecNum"=>$put["socSecNum"],
                                "phone"=>$put["phone"],
                                "email"=>$put["email"],
                                "role"=>$put["role"],
                                "isActive"=>$put["isActive"]
                            );

                            //This statement exectutes the SQL instruction and stores the completion status (true or false) on a variable.
                            $userCreationStatus = $this->db->createUser($newUser);

                            // -----------------------------------------------------------------
                            // |               SEPARATION ACCORDING TO ROLES                   |
                            // ----------------------------------------------------------------- 
                            $lastAddedUserId = $this->db->getIdOfLastAddedUser(); //This variable will store the ID of the user that was just added.
                            
                                // ----------DRIVER----------
                            //In case the new user has the driver role, we'll need to create them an entry on the DRIVERS table as well:
                                $driverCreationStatus = true; //first we set the driverCreationStatus variable to true in case the user isn't a driver.
                            if ($newUser["role"] == "driver") {
                                //Now we'll retrieve the ID of the user that was just added.
                                if ($lastAddedUserId != null) {
                                    $newDriver = array(
                                        "driverId"=>$lastAddedUserId,
                                        "managerId"=>$put["managerId"],
                                        "defaultVehiclePlate"=>$put["defaultVehiclePlate"]
                                    );
                                    $driverCreationStatus = $this->db->createDriver($newDriver);
                                }
                                else { //If the getIdOfLastAddedUser fails, we'll show database error.
                                    header('HTTP/1 500 Database error');
                                }
                            }
                                // ----------MANAGER----------
                            //If the user is a manager, we'll need to handle the drivers that have been linked to them.
                            else if ($newUser["role"] == "manager") { 
                                $linkedDrivers = $put["linkedDrivers"]; //the ID of each linked driver will be retrieved from an array on the PUT entry.
                                if ($linkedDrivers != null && $linkedDrivers != []) {
                                    foreach($linkedDrivers as $linkedDriverId) {
                                        $this->db->linkManagerToDriver($lastAddedUserId, $linkedDriverId);
                                    }
                                }
                            }

                            //If the operation is successful, we'll return true. Else, false.
                            if ($userCreationStatus && $driverCreationStatus) {
                                echo json_encode(true);
                            }
                            else {
                                echo json_encode(false);
                            }
                        }
                        else { //If the user isn't a manager, we'll return a 403 error.
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, we return Forbidden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;
                
                /** 
                *
                * Method: get-all-users
                *
                *       Gets detailed information of all the users in the database.
                *
                * Parameters:
                *
                *       accessToken - #.
                *
                * Returns:
                *
                *       users' data - #.
                */
                case 'get-all-users':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];
                    if ($userValues != false) {
                        //If the role is admin return all users
                        if($userValues["role"] == "admin") {
                            $users = $this->db->getUsers();
                            echo json_encode($users);
                        }
                        else {
                            echo json_encode("false");
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbidden
                        echo json_encode("false");
                        header('HTTP/1 403 Forbidden');
                    }
                    break;

                /** 
                *
                * Method: get-user
                *
                *       Gets detailed information of the user that's linked to a specific userId and accessToken.
                *
                * Parameters:
                *
                *       accessToken - #.
                *       userId - #.
                *
                * Returns:
                *
                *       user data - #.
                */
                case 'get-user':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];

                    //in case the PUT parameters aren't right, we'll return a 422 error.
                    if ( !isset($put["userId"]) ) {
                        echo json_encode("Unprocessable Entity: you may be missing the a parameter.");
                        header('HTTP/1 422 Unprocessable Entity');
                        exit();
                    }

                    $userId = $put['userId'];
                    if ($userValues != false) {
                        //If the role is admin return the user
                        if($userValues["role"] == "admin") {
                            //If the user exists return the user
                            $user = $this->db->getUser($userId);
                            if($user != null)
                            {
                                echo json_encode($user);
                            }
                            else {
                                header('HTTP/1 403 Forbidden');
                            }
                        }
                        else {
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbiden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;

                /** 
                *
                * Method: get-current-user
                *
                *       Gets detailed information of the user that's linked to a specific accessToken.
                *
                * Parameters:
                *
                *       accessToken - #.
                *
                * Returns:
                *
                *       user data - #.
                */
                case 'get-current-user':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    
                    //in case the PUT parameters aren't right, we'll return a 422 error.
                    if ( !isset($put["accessToken"]) ) {
                        echo json_encode("Unprocessable Entity: you may be missing the a parameter.");
                        header('HTTP/1 422 Unprocessable Entity');
                        exit();
                    }
                    $userValues = $this->db->checkAccessToken($accessToken)[0];

                    $userId = $userValues["userId"];
                    if ($userValues != false) {
                        //If the user exists return the user
                        $user = $this->db->getUser($userId);
                        if($user != null)
                        {
                            echo json_encode($user);
                        }
                        else {
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbiden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;

                /**
                *                 
                * Method: get-driver
                *
                *       Gets detailed information on a specific driver.
                *
                * Parameters:
                *
                *       accessToken - #.
                *       userId - #.
                *
                * Returns:
                *
                *       driver data - #.
                */ 
                case 'get-driver':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];

                    //in case the PUT parameters aren't right, we'll return a 422 error.
                    if ( !isset($put["driverId"]) ) {
                        echo json_encode("Unprocessable Entity: you may be missing the a parameter.");
                        header('HTTP/1 422 Unprocessable Entity');
                        exit();
                    }

                    $driverId = $put['driverId'];
                    if ($userValues != false) {
                        //If the role is admin return the driver
                        if($userValues["role"] == "admin" || $userValues["role"] == "manager") {
                            //If the user exists return the driver
                            $driver = $this->db->getDriver($driverId);
                            if($driver != false)
                            {
                                echo json_encode($driver);
                            }
                            else {
                                header('HTTP/1 403 Forbidden');
                            }
                        }
                        else {
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbidden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;

                /**
                *
                * Method: modify-user
                *
                *       Updates the information of a user on the database.
                *
                * Parameters:
                *
                *       accessToken - #.
                *       userId - #.
                *       name - #.
                *       surnames - #.
                *       password - #.
                *       nationalId - #.
                *       socSecNum - #.
                *       phone - #.
                *       email - #.
                *       role - #.
                *       isActive - #.
                *       linkedDrivers - (Only if the role is manager)  array of IDs.
                *
                * Returns:
                *
                *       user completion state - (true-->success, "0"-->no changes made, or false-->failed).
                *       driver completion state - (true-->success, "0"-->no changes made, or false-->failed).
                *       manager completion state - (true-->success, "0"-->no changes made, or false-->failed).
                */
                case 'modify-user':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];
                    //Correct user check.
                    if ($userValues != false) {
                        //Only if the user is a manager, can they modify another users' data.
                        if($userValues["role"] == "admin") {
                            //in case the PUT parameters aren't right, we'll return a 422 error.
                            if ( 
                                !isset($put["userId"]) || 
                                !isset($put["name"]) || 
                                !isset($put["surnames"]) || 
                                !isset($put["password"]) || 
                                !isset($put["nationalId"]) || 
                                !isset($put["socSecNum"]) || 
                                !isset($put["phone"]) || 
                                !isset($put["email"]) || 
                                !isset($put["role"]) || 
                                !isset($put["isActive"]) 
                            ) {
                                echo json_encode("Unprocessable Entity: you may be missing the a parameter.");
                                header('HTTP/1 422 Unprocessable Entity');
                                exit();
                            }
                            $modifiedUser = array(
                                "userId"=>$put["userId"],
                                "name"=>$put["name"], 
                                "surnames"=>$put["surnames"],
                                "password"=>$put["password"], 
                                "nationalId"=>$put["nationalId"], 
                                "socSecNum"=>$put["socSecNum"], 
                                "phone"=>$put["phone"], 
                                "email"=>$put["email"], 
                                "role"=>$put["role"],
                                "isActive"=>$put["isActive"]
                            );
                            //If the user has been changed, the following method will return true, else, false.
                            $userModificationStatus = $this->db->updateUser($modifiedUser);

                            
                            // -----------------------------------------------------------------
                            // |               SEPARATION ACCORDING TO ROLES                   |
                            // ----------------------------------------------------------------- 

                            $driverModificationStatus = "not a driver"; //first we set the driverCreationStatus variable to true in case the user isn't a driver.
                            $managerModificationStatus = "not a manager";

                                // ----------DRIVER----------
                            //In case the new user has the driver role, we'll need to create them an entry on the DRIVERS table as well:
                            if ($modifiedUser["role"] == "driver") {
                                $modifiedUser = array(
                                    "driverId"=>$put["userId"],
                                    "managerId"=>$put["managerId"],
                                    "defaultVehiclePlate"=>$put["defaultVehiclePlate"]
                                );
                                $driverModificationStatus = $this->db->updateDriver($modifiedUser);
                            }
                                // ----------MANAGER----------
                            //If the user is a manager, we'll need to handle the drivers that have been linked to them.
                            else if ($modifiedUser["role"] == "manager") {
                                $linkedDrivers = $put["linkedDrivers"]; //the ID of each linked driver will be retrieved from an array on the PUT entry.
                                if ($linkedDrivers != null && $linkedDrivers != []) {
                                    foreach($linkedDrivers as $linkedDriverId) {
                                        $this->db->linkManagerToDriver($put["userId"], $linkedDriverId);
                                    }
                                    //The following line will remove the correlation between the drivers that weren't specified on the put linkedDrivers array and the manager.
                                    $this->db->unlinkDriversFromManager($put["userId"], $put["linkedDrivers"]);
                                }
                                $managerModificationStatus = true;
                            }

                            //Now we'll separate the return values in 3 so that the front-end can get information about how each step went.
                            //For each of the 3 steps, the result values are:
                                // "0" -> successful query but no changes were made. (the sent information is the same as the stored one)
                                // true -> changes applied successfully.
                                // false -> changes failed to be applied.
                            $status = array(
                                "userModificationStatus"=>$userModificationStatus, 
                                "driverModificationStatus"=>$driverModificationStatus, 
                                "managerModificationStatus"=>$managerModificationStatus
                            );
                            echo json_encode($status);

                        }
                        else {
                            //If the user isn't a manager, we'll return forbidden.
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, returns Forbiden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;


                /** 
                *
                * Method: get-available-drivers
                *
                *       Gets detailed information of all the drivers that are currently active.
                *
                * Parameters:
                *
                *       accessToken - #.
                *       managerId - (Only if the role is admin).
                *
                * Returns:
                *
                *       {...} - one object for each available. Keys: "driver","userId","defaultVehiclePlate","name","surnames","password","nationalId","socSecNum","phon ,"email".
                */
                case 'get-available-drivers':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];
                    if ($userValues != false) {
                        //If the role is admin return all users
                        if ($userValues["role"] == "admin" || $userValues["role"] == "manager") {
                            if ($userValues["role"]=="admin") { $availableDrivers = $this->db->getAvailableDrivers($put["managerId"]); }
                            else { $availableDrivers = $this->db->getAvailableDrivers(); }
                            
                            //the result values vary depending on the operation result status. The 3 possible states are:
                                // "0" -> successful query but there aren't any values to display.
                                // [...] -> successful query and the returned data is an array with the resulting data.
                                // false -> error in the query.
                            echo json_encode($availableDrivers);
                            
                        }
                        else {
                            //If the user who's sending the request isn't a manager or an admin, we'll return Forbidden.
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, we'll return Forbidden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;

                /**
                *
                * Method: get-available-managers
                *
                *       Gets detailed information of all the managers that are currently active.
                *
                * Parameters:
                *
                *       accessToken - #.
                *
                * Returns:
                *
                *       {...} - one object for each available. Keys: "manager", "userId", "defaultVehiclePlate", "name", "surnames", "password", "nationalId", "socSecNum", "phone", "email".
                *       "0" - if the query is successful but there aren't any values to display.
                *       false - if there's an error in the query.
                */
                case 'get-available-managers':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];
                    if ($userValues != false) {
                        //If the role is admin return all users
                        if ($userValues["role"] == "admin" || $userValues["role"] == "manager") {
                            $availableManagers = $this->db->getAvailableManagers();
                            
                            //the result values vary depending on the operation result status. The 3 possible states are:
                                // "0" -> successful query but there aren't any values to display.
                                // [...] -> successful query and the returned data is an array with the resulting data.
                                // false -> error in the query.
                            echo json_encode($availableManagers);
                            
                        }
                        else {
                            //If the user who's sending the request isn't a manager or an admin, we'll return Forbidden.
                            header('HTTP/1 403 Forbidden');
                        }
                    }
                    else {
                        //If the token doesn't exist, we'll return Forbidden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;

                
                /**
                * Method: get-linked-drivers
                *
                *       Gets detailed information of all the drivers linked to a specific manager.
                *
                * Parameters:
                *
                *       accessToken - #.
                *       managerId - (only if the user is an admin).
                *
                * Return values:
                *
                *       linkedDrivers - (if there are results) array of linked drivers.
                *       "0" - (if no entries were found).
                *       false - (if there was any type of error).
                */
                case 'get-linked-drivers':
                    $put = json_decode(file_get_contents('php://input'), true);
                    $accessToken = $put['accessToken'];
                    $userValues = $this->db->checkAccessToken($accessToken)[0];
                    // var_dump($userValues);
                    if ($userValues != false) {
                            //If the user who launched the request is an admin, we'll retrieve the managerId from the PUT inputs.
                        if ($userValues["role"] == "admin") { 
                            //in case the managerId isn't passed as a parameter, we'll return a 422 error.
                            if ( !isset($put["managerId"]) ) {
                                echo json_encode("Unprocessable Entity: you may be missing the managerId parameter.");
                                header('HTTP/1 422 Unprocessable Entity');
                                exit();
                            }
                            $managerId = $put["managerId"]; 
                        }
                            //If the user who launched the request is a manager, we'll retrieve the managerId from the accessToken.
                        else if ($userValues["role"] == "manager") { $managerId = $userValues["userId"]; }
                        else {
                            //If the user who's sending the request isn't a manager or an admin, we'll return Forbidden.
                            header('HTTP/1 403 Forbidden');
                            exit();
                        }
                        
                        //Now we'll retrieve all the linked drivers.
                        $linkedDrivers = $this->db->getLinkedDrivers($managerId);

                        echo json_encode($linkedDrivers);
                    }
                    else {
                        //If the token doesn't exist, we'll return Forbidden
                        header('HTTP/1 403 Forbidden');
                    }
                    break;

                //IN CASE THE END-POINT ISN'T FOUND
                default:
                    header('HTTP/1.1 404 Not Found');
                    break;
                
            }
        }

        private function paths($url) {
            $uri = parse_url($url);
            return $uri["path"];
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
