<?php

    //learn from w3schools.com

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='p'){
            header("location: ../login.php");
        }else{
            $useremail=$_SESSION["user"];
        }

    }else{
        header("location: ../login.php");
    }
    

    //import database
    include("../connection.php");
    $sqlmain= "select * from patient where pemail=?";
    $stmt = $database->prepare($sqlmain);
    $stmt->bind_param("s",$useremail);
    $stmt->execute();
    $userrow = $stmt->get_result();
    $userfetch=$userrow->fetch_assoc();
    $userid= $userfetch["pid"];
    $username=$userfetch["pname"];


    if($_POST){
        if(isset($_POST["booknow"])){
            $apponum=$_POST["apponum"];
            $scheduleid=$_POST["scheduleid"];
            $date=$_POST["date"];
            $amount=$_POST["fee"];

            // get user phone number
            $userEmail = $_SESSION['user'];
            $query = "SELECT ptel FROM patient WHERE pemail = '$userEmail'";
            $res = $database->query($query);
            $phoneNumber;
            $merchantId;

            // initialize stk push
            if ($res) {
                // Check if any rows were returned
                if ($res->num_rows > 0) {
                    // Fetch the phone number
                    $row = $res->fetch_assoc();
                    $phoneNumber = $row['ptel'];
                    // Use the phone number as needed
                    echo "Phone Number: " . $phoneNumber;

                    // perfom stk push
                    $postData = array(
                        "trackindId" => $scheduleid,
                        "amount" => $amount,
                        "phone" => $phoneNumber,
                        "accountNumber" =>$apponum
                    );
                    
                    // Convert data to JSON format
                    $jsonData = json_encode($postData);
                    
                    // API endpoint
                    $apiUrl = "https://api.dialaserviceke.com/api/rhone/stkpush";
                    
                    // Initialize cURL session
                    $curl = curl_init();
                    
                    // Set cURL options
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $apiUrl,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => $jsonData,
                        CURLOPT_HTTPHEADER => array(
                            "Content-Type: application/json",
                            "Content-Length: " . strlen($jsonData)
                        )
                    ));
                    
                    // Execute the request and store the response
                    $response = curl_exec($curl);

                    // Check for errors
                    if (curl_errno($curl)) {
                        echo "Error: " . curl_error($curl);
                    } else {
                        // Close cURL session
                        curl_close($curl);
                        
                        $responseData = json_decode($response, true);

                        // Handle API response
                        if (isset($responseData['result']['MerchantRequestID'])) {
                            // Assign MerchantRequestID to a variable
                            $merchantRequestId = $responseData['result']['MerchantRequestID'];
                            $merchantId=$merchantRequestId;
                            
                        } else {
                            echo "MerchantRequestID not found in the response";
                            $merchantId="";
                        }
                        
                    }


                } else {
                    echo "No records found";
                }
            } else {
                // Handle query error
                echo "Query failed: " . $database->error;
            }

            $sql2="insert into appointment(pid,apponum,scheduleid,appodate,merchantId,feeCharged) values ($userid,$apponum,$scheduleid,'$date', '$merchantId', '$amount')";
            $result= $database->query($sql2);
            //echo $apponom;
            header("location: appointment.php?action=booking-added&id=".$apponum."&titleget=none");

        }
    }
 ?>