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

            $check_id = preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i", $req->email);
            $check_pw = pwCheck($req->pw);
            if(!$check_id){
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "올바르지 않은 이메일 형식입니다.";
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            if($check_pw[0] == false){
                $res->isSuccess = FALSE;
                $res->code = 202;
                $res->message = $check_pw[1];
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            if(trim($req->lastName) == "" || trim($req->firstName) == "" || trim($req->birth) == ""){
                $res->isSuccess = FALSE;
                $res->code = 203;
                $res->message = "입력하지 않은 필수 기입사항을 확인해주세요";
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            if($req->sex > 2){
                $res->isSuccess = FALSE;
                $res->code = 204;
                $res->message = "성별은 2 이하 이어야 합니다.";
                echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            $result = createUser($req->email, $req->pw, $req->lastName, $req->firstName, $req->sex, $req->birth, $req->profileImage);

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

        case "setInitialBody":
            http_response_code(200);
            if($req->heightType > 2){
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "heightType은 2 이하 이어야합니다.";
                echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                break;
            }
            if($req->weightType > 3){
                $res->isSuccess = FALSE;
                $res->code = 202;
                $res->message = "weightType은 3 이하 이어야 합니다.";
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            $result = setInitialBody($req->userNo, $req->height, $req->heightType, $req->weight, $req->weightType);
            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "신체 정보 넣기 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "setInitialGoal":
            http_response_code(200);
            $result = setInitialGoal($req->userNo, $req->exerciseType, $req->termType, $req->termValue, $req->measureType, $req->measureValue);
            if($req->termType > 5){
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "termType은 5 이하 이어야 합니다.";
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            if($req->measureType > 3){
                $res->isSuccess = FALSE;
                $res->code = 202;
                $res->message = "measureType은 3 이하 이어야 합니다.";
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
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
            $userEmail = $result->info->email;
            $userNo = $vars["userNo"];

            if(isExistUser($userNo)) {
                $res->result = userProfile($userEmail, $userNo);
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

            $result = editProfile($req->profileImage, $req->lastName, $req->firstName, $req->sex, $req->email, $req->birth, $req->height, $req->heightType, $req->weight, $req->weightType, $userEmail);
            if($result == 100) {
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "프로필 편집 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            break;

        case "profileTab":
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

            $res->result = profileTab($userEmail);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색결과 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "searchFriend":
            http_response_code(200);
            $query = $_GET["query"];
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
            $res->result = searchFriend($query);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색결과 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
            $result = addFriend($userEmail, $req->userNo);

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
                $res->message = "친구요청 검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "acceptOrDenyRequest":
            http_response_code(200);
            $type = $vars["type"];
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
            $result = acceptOrDenyRequest($req->requestNo, $type);

            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "요청 수락 성공";
            }else if($result == 101){
                $res->isSucces = TRUE;
                $res->code = 100;
                $res->message = "요청 거절 성공";
            }else if($result == 200){
                $res->isSuccess = FALSE;
                $res->code = 200;
                $res->message = "유효하지 않은 타입입니다";
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }else if($result ==  201){
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "존재 하지 않은 요청입니다.";
            }

            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "deleteFriend":
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
            $result = deleteFriend($userEmail, $req->friendNo);

            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "친구 삭제 성공";
            }else if($result == 200){
                $res->isSuccess = FALSE;
                $res->code = 200;
                $res->message = "친구 관계가 아닙니다";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "searchSneakersBrand":
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

            $res->result = searchSneakersBrand();

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 101;
                $res->message = "브랜드 검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "브랜드 검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "searchSneakersModel":
            http_response_code(200);
            $brandNo = $vars["brandNo"];
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

            $res->result = searchSneakersModel($brandNo);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 101;
                $res->message = "모델 검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "모델 검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "addSneakers":
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
            if(trim($req->modelNo) == "" ||  trim($req->sizeType) == "" || trim($req->sizeValue) == "" || trim($req->colorNo) == "" || trim($req->startedAt) == "" || trim($req->limitDistance) == ""){
                $res->isSuccess = FALSE;
                $res->code = 203;
                $res->message = "입력하지 않은 필수 기입사항을 확인해주세요";
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }

            $userEmail = $result->info->email;
            $result = addSneakers($userEmail, $req->modelNo, $req->nickname, $req->imageUrl, $req->sizeType, $req->sizeValue, $req->colorNo, $req->startedAt, $req->initDistance, $req->limitDistance);
            
            if($result = 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "운동화 추가 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "editSneakers":
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
            $result = editSneakers($userEmail, $req->sneakersNo, $req->modelNo, $req->nickname, $req->imageUrl, $req->sizeType, $req->sizeValue, $req->colorNo, $req->limitDistance);

            if($result = 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "운동화 수정 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "deleteSneakers":
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
            $result = deleteSneakers($userEmail, $req->sneakersNo);

            if($result = 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "운동화 삭제 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "userSneakers":
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

            $res->result = userSneakers($userEmail);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "sneakersInfo":
            http_response_code(200);
            $sneakersNo = $vars["sneakersNo"];
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

            $res->result = sneakersInfo($userEmail, $sneakersNo);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "userGoal":
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

            $res->result = userGoal($userEmail);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "addGoal":
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

            $result = addGoal($userEmail, $req->exerciseType, $req->termType, $req->termValue, $req->measureType, $req->measureValue);
            if($req->termType > 5){
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "termType은 5 이하 이어야 합니다.";
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            if($req->measureType > 3){
                $res->isSuccess = FALSE;
                $res->code = 202;
                $res->message = "measureType은 3 이하 이어야 합니다.";
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "목표 설정 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        case "deleteGoal":
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
            
            $result = deleteGoal($userEmail, $req->goalNo);
            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "목표 삭제 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "terminateGoal":
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

            $result = terminateGoal($userEmail, $req->goalNo);
            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "목표 종료 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "goalInfo":
            http_response_code(200);
            $goalNo = $vars["goalNo"];
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

            $res->result = goalInfo($userEmail, $goalNo);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "addActivity":
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

            $result = addActivity($userEmail, $req->sneakersNo, $req->distance, $req->exerciseTime, $req->calorie, $req->averagePace, $req->averageSpeed, $req->maxSpeed, $req->exerciseType, $req->goalType, $req->goalNo, $req->facialEmoticon, $req->placeEmoticon, $req->weather, $req->temperature, $req->imageUrl, $req->memo);

            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "활동 추가 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "userActivity":
            http_response_code(200);
            $exerciseType = $_GET["exerciseType"];
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

            $res->result = userActivity($userEmail, $exerciseType);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "activityInfo":
            http_response_code(200);
            $activityNo = $vars["activityNo"];
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

            $res->result =activityInfo($userEmail, $activityNo);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "editActivity":
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
            $result = editActivity($req->activityNo, $req->startedAt, $req->sneakersNo, $req->distance, $req->exerciseTime, $req->calorie, $req->exerciseType, $req->facialEmoticon, $req->placeEmoticon, $req->weather, $req->temperature, $req->imageUrl, $req->memo);

            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "활동 수정 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            break;

        case "deleteActivity":
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
            $result = deleteActivity($userEmail, $req->activityNo);

            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "활동 삭제 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            break;

        case "addCommunity":
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
            $result = addCommunity($userEmail, $req->communityName, $req->depict, $req->imageUrl);

            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "커뮤니티 생성 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            break;
            
        case "editCommunity":
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
            $result = editCommunity($userEmail, $req->communityNo, $req->communityName, $req->depict, $req->imageUrl);

            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "커뮤니티 수정 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            break;

        case "userCommunity":
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
            $res->result = userCommunity($userEmail);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "inviteCommunity":
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
            $result = inviteCommunity($userEmail, $req->communityNo, $req->friendNo);

            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "커뮤니티 초대 성공";
            }else if($result == 200){
                $res->isSuccess = FALSE;
                $res->code = 200;
                $res->message = "친구가 아닙니다";
            }
            echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            break;

        case "requestedCommunity":
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
            $res->result = requestedCommunity($userEmail);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "acceptOrDenyInvite":
            http_response_code(200);
            $type = $vars["type"];
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
            $result = acceptOrDenyInvite($req->requestNo, $type);

            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "요청 수락 성공";
            }else if($result == 101){
                $res->isSucces = TRUE;
                $res->code = 100;
                $res->message = "요청 거절 성공";
            }else if($result == 200){
                $res->isSuccess = FALSE;
                $res->code = 200;
                $res->message = "유효하지 않은 타입입니다";
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }else if($result ==  201){
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "존재 하지 않은 요청입니다.";
            }

            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "exitCommunity":
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
            $result = exitCommunity($userEmail, $req->communityNo);

            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "커뮤니티 나가기 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            break;

        case "kickUser":
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
            $result = kickUser($userEmail, $req->communityNo, $req->friendNo);

            if($result == 100){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "유저 추방 성공";
            }
            echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            break;

        case "communityInfo":
            http_response_code(200);
            $communityNo = $vars["communityNo"];
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
            $res->result = communityInfo($userEmail, $communityNo);
            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "statistic":
            http_response_code(200);
            $exerciseType = $_GET["exerciseType"];
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

            $res->result = statistic($userEmail, $exerciseType);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "leaderboard":
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
            $res->result = leaderboard($userEmail);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case "progressStatus":
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
            $res->result = progressStatus($userEmail);

            if($res->result == null){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "검색 결과가 없습니다.";
            }else{
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->messgae = "검색 결과.";
            }
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

            
    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
