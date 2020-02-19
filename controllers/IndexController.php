<?php
require 'function.php';

const JWT_SECRET_KEY = "TEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEY";

$res = (Object)Array();
header('Content-Type: json');
$req = json_decode(file_get_contents("php://input"));
try {
    addAccessLogs($accessLogs, $req);
    switch ($handler) {
        case "index":
            echo "API Server";
            break;
        case "ACCESS_LOGS":
            //            header('content-type text/html charset=utf-8');
            header('Content-Type: text/html; charset=UTF-8');
            getLogs("./logs/access.log");
            break;
        case "ERROR_LOGS":
            //            header('content-type text/html charset=utf-8');
            header('Content-Type: text/html; charset=UTF-8');
            getLogs("./logs/errors.log");
            break;
        /*
         * API No. 0
         * API Name : 테스트 API
         * 마지막 수정 날짜 : 19.04.29
         */
        case "createUser":
            http_response_code(200);
            /*
            $check_id = preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i", $req->email);
            $check_pw = preg_match("/^(?=.*[a-zA-Z])(?=.*[^a-zA-Z0-9]|.*[0-9]).{8,24}$/", $req->pw);
            if(!$check_id){
                $res->isSuccess = FALSE;
                $res->code = 100;
                $res->message = "올바르지 않은 이메일 형식입니다.";
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            if(!$check_pw){
                $res->isSuccess = FALSE;
                $res->code = 100;
                $res->message = "올바르지 않은 비밀번호 형식입니다.";
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            validation*/
            $result = createUser($req->email, $req->pw, $req->lName, $req->fName, $req->sex, $req->birth, $req->profileImage);

            if($result[0][code] == 100){
                $res->userNo = $result[0]['no'];
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "회원가입 성공";
            }else if($result[0][code] == 200){
                $res->isSuccess = FALSE;
                $res->code = 200;
                $res->message = "회원가입 실패.(중복된 이메일 입니다.)";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "login":
            http_response_code(200);
            $email = $req->email; $pw = $req->pw;

            if(!isValidUser($email, $pw)){
                $res->isSuccess = FALSE;
                $res->code = 200;
                $res->message = "아이디 또는 비밀번호를 확인해주세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $jwt = getJWToken($email, $pw, JWT_SECRET_KEY);
            $res->result->jwt = $jwt;
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "로그인 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        case "jwtUser":
            http_response_code(200);

            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];
            $result = isValidHeader($jwt, JWT_SECRET_KEY);

            if (!$result->auth) {
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            http_response_code(200);
            $res->result = $result->info;
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "테스트 성공";

            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        case "userBodyInfo":
            http_response_code(200);

            $result = userBodyInfo($req->userNo, $req->height, $req->heightType, $req->weight, $req->weightType);
            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "신체 정보 넣기 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "userGoal":
            http_response_code(200);
            $result = userGoal($req->userNo, $req->exerciseType, $req->termType, $req->termValue, $req->measureType, $req->measureValue);

            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "초기 목표 설정 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "userProfile":
            http_response_code(200);
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];
            $result = isValidHeader($jwt, JWT_SECRET_KEY);
            if (!$result->auth) {
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            $userNo = $vars["userNo"];

            if(isExistUser($userNo)) {
                $res->result = userProfile($userNo);
                $res->result[0]['createdAt'] = substr(str_replace("-", "", $res->result[0]['createdAt']), 0, 8);
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "프로필 조회 성공";
            }else{
                $res->isSuccess = FALSE;
                $res->code = 200;
                $res->message = "유효하지 않은 회원번호입니다.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "editProfile":
            http_response_code(200);
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];
            $result = isValidHeader($jwt, JWT_SECRET_KEY);
            if (!$result->auth) {
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            $userEmail = $result->info->email;

            $result = editProfile($req->profileImage, $req->lName, $req->fName, $req->sex, $req->email, $req->birth, $req->height, $req->heightType, $req->weight, $req->weightType, $userEmail);
            if($result == 100) {
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "프로필 편집 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            break;

        case "userFriend":
            http_response_code(200);
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];
            $result = isValidHeader($jwt, JWT_SECRET_KEY);
            if (!$result->auth) {
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            $userEmail = $result->info->email;

            $res->result = userFriend($userEmail);
            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "친구가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "친구 검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "addFriend":
            http_response_code(200);
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];
            $result = isValidHeader($jwt, JWT_SECRET_KEY);
            if (!$result->auth) {
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            $userEmail = $result->info->email;
            $result = addFriend($userEmail, $req->targetNo);

            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "친구요청 성공";
            }

            echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            break;

        case "requestedFriend":
            http_response_code(200);
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];
            $result = isValidHeader($jwt, JWT_SECRET_KEY);
            if (!$result->auth) {
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            $userEmail = $result->info->email;
            $res->result = requestedFriend($userEmail);
            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "친구 요청이 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "친구요청 검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "acceptOrDenyRequest":
            http_response_code(200);
            $result = isValidHeader($jwt, JWT_SECRET_KEY);
            if (!$result->auth) {
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            $userEmail = $result->info->email;


    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
