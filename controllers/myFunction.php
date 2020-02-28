<?php

function createUser($email, $pw, $lastName, $firstName, $sex, $birth, $profileImage){
    if(availableEmail($email)){
        $pdo = pdoSqlConnect();
        if($sex == null && $profileImage == null){
            $query = "insert into user (email, pw, lastName, firstName, birth, profileImage) values (?, ?, ?, ?, ?, ?);";

            $st = $pdo->prepare($query);
            $st->execute([$email, $pw, $lastName, $firstName, $birth, "https://dragonhyun.com/images/profileMaleDefault.JPG"]);
        }else if($sex != null && $profileImage == null){
            if($sex != 1){
                $query = "insert into user (email, pw, lastName, firstName, birth, sex, profileImage) values (?, ?, ?, ?, ?, ?, ?);";

                $st = $pdo->prepare($query);
                $st->execute([$email, $pw, $lastName, $firstName, $birth, $sex, "https://dragonhyun.com/images/profileFemaleDefault.JPG"]);
            }
            else {
                $query = "insert into user (email, pw, lastName, firstName, birth, sex, profileImage) values (?, ?, ?, ?, ?, ?, ?);";

                $st = $pdo->prepare($query);
                $st->execute([$email, $pw, $lastName, $firstName, $birth, $sex, "https://dragonhyun.com/images/profileMaleDefault.JPG"]);
            }
        }else if($sex == null && $profileImage != null){
            $query = "insert into user (email, pw, lastName, firstName, birth, profileImage) values (?, ?, ?, ?, ?, ?);";
            
            /*
             * 추후 upload기능 완성시키면 수정할 것.
             * $query = "insert into user (email, pw, lName, fName, birth) values (?, ?, ?, ?, ?);";
             * $st = $pdo->prepare($query);
             * $st->execute([$email, $pw, $lName, $fName, $birth]);
             * 
             * uploadProfileImage($file, $userNo)  -> userNo는 위의 query에서 no 받아와야할듯
             */

            $st = $pdo->prepare($query);
            $st->execute([$email, $pw, $lastName, $firstName, $birth, $profileImage]);
        }else{
            $query = "insert into user (email, pw, lastName, firstName, birth, sex, profileImage) values (?, ?, ?, ?, ?, ?, ?);";

            $st = $pdo->prepare($query);
            $st->execute([$email, $pw, $lastName, $firstName, $birth, $sex, $profileImage]);
        }

        $query = "select no from user where email=?;";
        $st = $pdo->prepare($query);
        $st->execute([$email]);

        $res = $st->fetchAll();

        $st = null; $pdo = null;
        $res[0]['code'] = 100;

        return $res;
    }else{
        $res = array();
        array_push($res, array("code" => 200));

        return $res;
    }
}

function setInitialBody($userNo, $height, $heightType, $weight, $weightType){

    if(trim($height) == "")
        $height = null;
    if(trim($heightType) == "")
        $heightType = null;
    if(trim($weight) == "")
        $weight = null;
    if(trim($weightType) == "")
        $weightType = null;

    $pdo = pdoSqlConnect();

    if($height != null && $heightType != null && $weight == null || $weightType == null){
        $query = "update user set height=?, heightType=? where no=?;";

        $st = $pdo->prepare($query);
        $st->execute([$height, $heightType, $userNo]);
    }else if($height == null || $heightType == null && $weight != null && $weightType != null){
        $query = "update user set weight=?, weightType=? where no=?;";

        $st = $pdo->prepare($query);
        $st->execute([$weight, $weightType, $userNo]);
    }else if($height != null && $heightType != null && $weight != null && $weightType != null){

        $query = "update user set height=?, heightType=?, weight=?, weightType=? where no=?;";

        $st = $pdo->prepare($query);
        $st->execute([$height, $heightType, $weight, $weightType, $userNo]);
    }

    $st = null;
    $pdo = null;

    return 100;
}

function setInitialGoal($userNo, $exerciseType, $termType, $termValue, $measureType, $measureValue){
    $pdo = pdoSqlConnect();

    if($termType == 5){
        $query = "insert into userGoal (userNo, exerciseType, termType, termValue, measureType, measureValue) values (?, ?, ?, ?, ?, ?);";

        $st = $pdo->prepare($query);
        $st->execute([$userNo, $exerciseType, $termType, $termValue, $measureType, $measureValue]);
    }else{
        $query = "insert into userGoal (userNo, exerciseType, termType, measureType, measureValue) values (?, ?, ?, ?, ?);";

        $st = $pdo->prepare($query);
        $st->execute([$userNo, $exerciseType, $termType, $measureType, $measureValue]);
    }

    $st = null;
    $pdo = null;

    return 100;
}

function userProfile($userEmail, $userNo){
    $pdo = pdoSqlConnect();
    if(isFriend($userEmail, $userNo)) {
        $query = "select userNo, email, lastName, firstName, profileImage, createdAt, substr(now(), 6, 2) as date, sum(distance) as totalDistance, if(sum(exerciseTime) = 0, 0, count(exerciseTime)) as activityCount, sum(substr(exerciseTime, 1, 2) * 3600 + substr(exerciseTime, 3, 2) * 60 + substr(exerciseTime, 5, 2)) as totalExerciseTime, sum(calorie) as totalCalorie
from (select user.no as userNo, email, lastName, firstName, profileImage, user.createdAt as createdAt, ifnull(distance, 0) as distance, ifnull(calorie, 0) as calorie, ifnull(exerciseTime, 0) as exerciseTime from user
    left outer join activity a on user.no = a.userNo and date_format(a.createdAt, '%m') = date_format(now(), '%m')) t where t.email = ? or t.userNo = ? group by t.userNo;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail, $userNo]);

        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();

        $query = "select no as activityNo, exerciseType, exerciseTime, distance, createdAt from activity where userNo = ? order by createdAt desc limit 1;";

        $st = $pdo->prepare($query);
        $st->execute([$userNo]);

        $st->setFetchMode(PDO::FETCH_ASSOC);
        $rres = $st->fetchAll();

        $data = array();
        $data['targetInfo'] = array();
        $data['myInfo'] = array();
        foreach ($res as $r) {
            if ($r['email'] == $userEmail) {
                array_push($data['myInfo'] = $r);
            } else {
                array_push($data['targetInfo'] = $r);
            }
        }
        if ($data['myInfo']['totalDistance'] > $data['targetInfo']['totalDistance']) {
            $data['myInfo']['rank'] = 1;
            $data['targetInfo']['rank'] = 2;
        } else {
            $data['targetInfo']['rank'] = 1;
            $data['myInfo']['rank'] = 2;
        }
        $data['targetInfo']['createdAt'] = substr($data['targetInfo']['createdAt'], 0, 4) . ' ' . substr($data['targetInfo']['createdAt'], 5, 2) . ', ' . substr($data['targetInfo']['createdAt'], 8, 2);
        $data['targetInfo']['lastActivity'] = $rres;
        $st = null;
        $pdo = null;

        return $data;
    }else{
        $query = "select userNo, email, lastName, firstName, profileImage, createdAt, substr(now(), 6, 2) as date, sum(distance) as totalDistance 
from (select user.no as userNo, email, lastName, firstName, profileImage, user.createdAt as createdAt, ifnull(distance, 0) as distance, ifnull(calorie, 0) as calorie, ifnull(exerciseTime, 0) as exerciseTime from user
    left outer join activity a on user.no = a.userNo and date_format(a.createdAt, '%m') = date_format(now(), '%m')) t where t.email = ? or t.userNo = ? group by t.userNo;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail, $userNo]);

        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();

        $data = array();
        $data['targetInfo'] = array();
        $data['myInfo'] = array();
        foreach ($res as $r) {
            if ($r['email'] == $userEmail) {
                array_push($data['myInfo'] = $r);
            } else {
                array_push($data['targetInfo'] = $r);
            }
        }
        if ($data['myInfo']['totalDistance'] > $data['targetInfo']['totalDistance']) {
            $data['myInfo']['rank'] = 1;
            $data['targetInfo']['rank'] = 2;
        } else {
            $data['targetInfo']['rank'] = 1;
            $data['myInfo']['rank'] = 2;
        }
        $data['targetInfo']['createdAt'] = substr($data['targetInfo']['createdAt'], 0, 4) . ' ' . substr($data['targetInfo']['createdAt'], 5, 2) . ', ' . substr($data['targetInfo']['createdAt'], 8, 2);
        $st = null;
        $pdo = null;

        return $data;
    }
}

function editProfile($profileImage, $lastName, $firstName, $sex, $email, $birth, $height, $heightType, $weight, $weightType, $userEmail){
    $pdo = pdoSqlConnect();

    $query = "update user set profileImage=?, lastName=?, firstName=?, sex=?, email=?, birth=?, height=?, heightType=?, weight=?, weightType=? where email=?;";

    $st = $pdo->prepare($query);
    $st->execute([$profileImage, $lastName, $firstName, $sex, $email, $birth, $height, $heightType, $weight, $weightType, $userEmail]);

    $st = null;
    $pdo = null;

    return 100;
}

function profileTab($userEmail){
    $pdo = pdoSqlConnect();

    $query = "select profileImage, lastName, firstName, createdAt, (select count(*) from friend inner join user u on friend.followingNo = u.no and u.email = ?)+(select count(*) from friend inner join user u on friend.followerNo = u.no and u.email = ?) as friendCnt from user where email = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail, $userEmail, $userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

function searchFriend($search){
    $pdo = pdoSqlConnect();
    if(preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i", $search)){
        $query = "select no as userNo, lastName, firstName, profileImage from user where email = ?;";
        $st = $pdo->prepare($query);
        $st->execute([$search]);
    }else{
        $query = "select no as userNo, lastName, firstName, profileImage from user where substring_index(?, ' ', 1) like firstName or substring_index(?, ' ', -1) like firstName;";
        $st = $pdo->prepare($query);
        $st->execute([$search, $search]);
    }

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

function userFriend($userEmail){
    $pdo = pdoSqlConnect();

    $query = "select no as friendNo, lastName, firstName, profileImage from user 
inner join (select followingNo as friendNo, followerNo as myNo from friend inner join user u on friend.followerNo = u.no and u.email = ?) a on user.no = a.friendNo
union
select no as friendNo, lastName, firstName, profileImage from user 
inner join (select followerNo as friendNo, followingNo as myNo from friend inner join user u on friend.followingNo = u.no and u.email = ?) a on user.no = a.friendNo;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail, $userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if(sizeof($res) == 1)
       return $res;
    else if ($res == null)
        return $res[0];
    else
        return $res;
}

function addFriend($userEmail, $userNo){
    $pdo = pdoSqlConnect();

    $query = "insert into friendRequest (senderNo, receiverNo) select no as senderNo, ? as receiverNo from user where email=?;";

    $st = $pdo->prepare($query);
    $st->execute([$userNo, $userEmail]);

    $st = null;
    $pdo = null;

    return 100;
}

function requestedFriend($userEmail){
    $pdo = pdoSqlConnect();

    $query = "select requestNo, no as senderNo, lastName, firstName, profileImage from user 
inner join (select friendRequest.no as requestNo, senderNo, receiverNo from friendRequest inner join user u on friendRequest.receiverNo = u.no and u.email=?) f on f.senderNo = user.no;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if(sizeof($res) == 1)
        return $res;
    else if ($res == null)
        return $res[0];
    else
        return $res;
}

function acceptOrDenyRequest($requestNo, $type){
    $pdo = pdoSqlConnect();

    $query = "select * from friendRequest where no=?;";

    $st = $pdo->prepare($query);
    $st->execute([$requestNo]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    if($res == null)
        return 201;

    if($type == 'accept'){
        $query = "insert into friend (followingNo, followerNo) select receiverNo, senderNo from friendRequest where no=?;";

        $st = $pdo->prepare($query);
        $st->execute([$requestNo]);
        $code = 100;
    }else if($type == 'denial'){
        $code = 101;
    }else{
        return 200;
    }

    $query = "delete from friendRequest where no=?;";

    $st = $pdo->prepare($query);
    $st->execute([$requestNo]);

    $st = null;
    $pdo = null;

    return $code;
}

function deleteFriend($userEmail, $friendNo){
    $pdo = pdoSqlConnect();
    if(isFriend($userEmail, $friendNo)){
        $query = "delete from friend where no in (select no from (select friend.no as no from friend inner join user u on friend.followerNo = u.no and u.email = ? and friend.followingNo = ?
    union
    select friend.no as no from friend inner join user u on friend.followingNo = u.no and u.email = ? and friend.followerNo = ?) t);";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail, $friendNo, $userEmail, $friendNo]);
    }else{
        return 200;
    }

    $st = null;
    $pdo = null;

    return 100;
}

function searchSneakersBrand(){
    $pdo = pdoSqlConnect();

    $query = "select no as brandNo, brandName from sneakersBrand;";

    $st = $pdo->prepare($query);
    $st->execute();

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if(sizeof($res) == 1)
        return $res;
    else if ($res == null)
        return $res[0];
    else
        return $res;
}

function searchSneakersModel($brandNo){
    $pdo = pdoSqlConnect();

    $query = "select brandNo, no as modelNo, modelName, imageUrl from sneakersModel where brandNo = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$brandNo]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if(sizeof($res) == 1)
        return $res;
    else if ($res == null)
        return $res[0];
    else
        return $res;
}

function addSneakers($userEmail, $modelNo, $nickname, $imageUrl, $sizeType, $sizeValue, $colorNo, $startedAt, $initDistance, $limitDistance){
    $pdo = pdoSqlConnect();
    if(trim($nickname) == "")
        $nickname = null;
    if(trim($imageUrl) == "")
        $imageUrl = null;

    if($nickname == null && $imageUrl == null){
        $query = "insert into userSneakers (userNo, modelNo, nickname, imageUrl, sizeType, sizeValue, colorNo, startedAt, initDistance, limitDistance)
 select (select no from user where email=?) as userNo, no as modelNo, modelName as nickname, imageUrl, ?, ?, ?, ?, ?, ? from sneakersModel where no=?;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail, $sizeType, $sizeValue, $colorNo, $startedAt, $initDistance, $limitDistance, $modelNo]);
    }else if($nickname == null && $imageUrl != null){
        $query = "insert into userSneakers (userNo, modelNo, nickname, imageUrl, sizeType, sizeValue, colorNo, startedAt, initDistance, limitDistance)
 select (select no from user where email=?) as userNo, no as modelNo, modelName as nickname, ?, ?, ?, ?, ?, ?, ? from sneakersModel where no=?;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail, $imageUrl, $sizeType, $sizeValue, $colorNo, $startedAt, $initDistance, $limitDistance, $modelNo]);
    }else if($nickname != null && $imageUrl == null){
        $query = "insert into userSneakers (userNo, modelNo, nickname, imageUrl, sizeType, sizeValue, colorNo, startedAt, initDistance, limitDistance)
 select (select no from user where email=?) as userNo, no as modelNo, ?, imageUrl, ?, ?, ?, ?, ?, ? from sneakersModel where no=?;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail, $nickname, $sizeType, $sizeValue, $colorNo, $startedAt, $initDistance, $limitDistance, $modelNo]);
    }else {
        $query = "insert into userSneakers (userNo, modelNo, nickname, imageUrl, sizeType, sizeValue, colorNo, startedAt, initDistance, limitDistance) select no as userNo, ?, ?, ?, ?, ?, ?, ?, ?, ? from user where email=?;";

        $st = $pdo->prepare($query);
        $st->execute([$modelNo, $nickname, $imageUrl, $sizeType, $sizeValue, $colorNo, $startedAt, $initDistance, $limitDistance, $userEmail]);
    }
    $st = null;
    $pdo = null;

    return 100;
}

function editSneakers($userEmail, $sneakersNo, $modelNo, $nickname, $imageUrl, $sizeType, $sizeValue, $colorNo, $limitDistance){
    $pdo = pdoSqlConnect();

    if($nickname == null){
        $query = "update userSneakers set modelNo = ?, nickname = (select modelName as nickname from sneakersModel where no =?), imageUrl = ?, sizeType = ?, sizeValue = ?, colorNo = ?, limitDistance = ?
where no = ? and userNo in (select no as userNo from user where email = ?);";

        $st = $pdo->prepare($query);
        $st->execute([$modelNo, $modelNo, $imageUrl, $sizeType, $sizeValue, $colorNo, $limitDistance, $sneakersNo, $userEmail]);
    }else{
        $query = "update userSneakers set modelNo = ?, nickname = ?, imageUrl = ?, sizeType = ?, sizeValue = ?, colorNo = ?, limitDistance = ? 
where no = ? and userNo in (select no as userNo from user where email = ?);";

        $st = $pdo->prepare($query);
        $st->execute([$modelNo, $nickname, $imageUrl, $sizeType, $sizeValue, $colorNo, $limitDistance, $sneakersNo, $userEmail]);
    }
    $st = null;
    $pdo = null;

    return 100;
}

function deleteSneakers($userEmail, $sneakersNo){
    $pdo = pdoSqlConnect();


    $query = "delete from userSneakers where no = ? and userNo in (select no as userNo from user where email = ?);";

    $st = $pdo->prepare($query);
    $st->execute([$sneakersNo, $userEmail]);

    $st = null;
    $pdo = null;

    return 100;
}

function userSneakers($userEmail){
    $pdo = pdoSqlConnect();

    $query = "select sneakersNo, userNo, nickname, imageUrl, round(initDistance+sum(t.distance), 2) as totalDistance, limitDistance
from (select userSneakers.no as sneakersNo, userSneakers.userNo, nickname, userSneakers.imageUrl, initDistance, ifnull(a.distance, 0) as distance,
       limitDistance from userSneakers
inner join user u on userSneakers.userNo = u.no and u.email = ?
left outer join activity a on userSneakers.no = a.sneakersNo) t group by t.sneakersNo;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if(sizeof($res) == 1)
        return $res;
    else if ($res == null)
        return $res[0];
    else
        return $res;
}

function sneakersInfo($userEmail, $sneakersNo){
    $pdo = pdoSqlConnect();

    $query = "select nickname, modelName, imageUrl, round(initDistance+sum(t.distance), 2) as totalDistance, limitDistance, startedAt from (select userSneakers.no as sneakersNo, nickname, modelName, userSneakers.imageUrl as imageUrl, initDistance, ifnull(a.distance, 0) as distance, limitDistance, userSneakers.startedAt from userSneakers
    inner join user u on userSneakers.userNo = u.no and u.email = ? and userSneakers.no = ?
    inner join sneakersModel sM on userSneakers.modelNo = sM.no
    inner join sneakersBrand sB on sM.brandNo = sB.no
    left outer join activity a on userSneakers.no = a.sneakersNo) t group by t.sneakersNo;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail, $sneakersNo]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

function userGoal($userEmail){
    $pdo = pdoSqlConnect();

    $query = "select userGoal.no as goalNo, termValue,
       case
           when termType = 1 then '오늘'
           when termType = 2 then '이번 주'
           when termType = 3 then '이번 달'
           when termType = 4 then '올해'
           when termType = 5 then concat(concat(concat(concat(substr(termValue, 1, 4), '. '), concat(substr(termValue, 5, 2), '. '))
               , concat(substr(termValue, 7, 2), '. ')), '까지')
        end as termName,
       termType,
       case
           when measureType = 1 then concat('목표: ', concat(measureValue, ' km'))
           when measureType = 2 then concat('목표: ', concat(concat(substr(measureValue, 1, 2), ':'), substr(measureValue, 3, 2)))
           when measureType = 3 then concat('목표: ', concat(measureValue, ' 회'))
           end as goalName,
       measureValue, measureType, exerciseName, exerciseType, isTerminate from userGoal
    inner join user u on userGoal.userNo = u.no and u.email = ?
    inner join exercise e on userGoal.exerciseType = e.no;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if(sizeof($res) == 1)
        return $res;
    else if ($res == null)
        return $res[0];
    else
        return $res;
}

function addGoal($userEmail, $exerciseType, $termType, $termValue, $measureType, $measureValue){
    $pdo = pdoSqlConnect();

    if($termType == 5){
        $query = "insert into userGoal (userNo, exerciseType, termType, termValue, measureType, measureValue) select no as userNo, ?, ?, ?, ?, ? from user where email=?;";

        $st = $pdo->prepare($query);
        $st->execute([$exerciseType, $termType, $termValue, $measureType, $measureValue, $userEmail]);
    }else{
        $query = "insert into userGoal (userNo, exerciseType, termType, measureType, measureValue) select no as userNo, ?, ?, ?, ? from user where email=?;";

        $st = $pdo->prepare($query);
        $st->execute([$exerciseType, $termType, $measureType, $measureValue, $userEmail]);
    }

    $st = null;
    $pdo = null;

    return 100;
}

function deleteGoal($userEmail, $goalNo){
    $pdo = pdoSqlConnect();

    $query = "delete from userGoal where no = ? and userNo in (select no as userNo from user where email = ?);";

    $st = $pdo->prepare($query);
    $st->execute([$goalNo, $userEmail]);

    $st = null;
    $pdo = null;

    return 100;
}

function terminateGoal($userEmail, $goalNo){
    $pdo = pdoSqlConnect();

    $query = "update userGoal set isTerminate = 1 where no = ? and userNo in (select no from user where email = ?);";

    $st = $pdo->prepare($query);
    $st->execute([$goalNo, $userEmail]);

    $st = null;
    $pdo = null;

    return 100;
}

function goalInfo($userEmail, $goalNo){
    $pdo = pdoSqlConnect();

    $query = "select exerciseName, goalDate, totalDistance, concat(totalExerciseTime div 3600, ':', totalExerciseTime mod 3600 div 60) as totalExerciseTime, activityCount, goalValue,
       case
           when measureType = 1 then if(round(measureValue - totalDistance, 2) < 0, concat(round(measureValue - totalDistance) * -1, 'km 목표 초과'), concat(round(measureValue - totalDistance), 'km 남은 목표'))
           when measureType = 2 then if((substr(measureValue, 1, 2) * 3600 + substr(measureValue, 3, 2) * 60) < totalExerciseTime, concat((totalExerciseTime - (substr(measureValue, 1, 2) * 3600 + substr(measureValue, 3, 2) * 60) div 3600), ':', (totalExerciseTime - (substr(measureValue, 1, 2) * 3600 + substr(measureValue, 3, 2) * 60)) mod 3600 div 60, ' 목표 초과'), concat(((substr(measureValue, 1, 2) * 3600 + substr(measureValue, 3, 2) * 60) - totalExerciseTime) div 3600, ':', ((substr(measureValue, 1, 2) * 3600 + substr(measureValue, 3, 2) * 60) - totalExerciseTime) mod 3600 div 60, ' 남은 목표'))
           when measureType = 3 then if(measureValue < activityCount, concat(activityCount - measureValue, '회 목표 초과'), concat(measureValue - activityCount, '회 남은 목표'))
           end as leftGoalValue,
       case
           when termType = 1 then concat(substr(replace(now(), '-', '. '), 1, 12), '. 목표 시작됨')
           when termType = 2 then concat(substr(replace(adddate(now(), -weekday(now()) - 1), '-', '. '), 1, 12), '. 목표 시작됨')
           when termType = 3 then concat(substr(replace(last_day(now() - interval 1 month) + interval 1 day, '-', '. '), 1, 12), '. 목표 시작됨')
           when termType = 4 then concat(substr(now(), 1, 4), '. 01. 01. 목표 시작됨')
           when termType = 5 then concat(substr(replace(goalCreated, '-', '. '), 1, 12), '. 목표 시작됨')
           end as goalStarted,
       case
           when timestampdiff(day, now(), goalEnded) < 1 then if(timestampdiff(minute, now(), date_format(concat(substr(now(), 1, 11), '23:59'), '%y-%m-%d %H:%i')) < 60,
               concat('00:', timestampdiff(minute, now(), date_format(concat(substr(now(), 1, 11), '23:59'), '%y-%m-%d %H:%i')), ' 남음'), concat(timestampdiff(minute, now(), date_format(concat(substr(now(), 1, 11), '23:59'), '%y-%m-%d %H:%i')) div 60, ':', timestampdiff(minute, now(), date_format(concat(substr(now(), 1, 11), '23:59'), '%y-%m-%d %H:%i')) mod 60, ' 남음'))
           else
               concat(timestampdiff(day, now(), goalEnded), ' 일 남음')
           end as goalEnd, concat(substr(termValue, 1, 4), '. ', substr(termValue, 5, 2), '. ', substr(termValue, 7, 2), '. 목표일') as goalRemain
from (select userNo, exerciseName,
        case
           when termType = 1 then '오늘'
           when termType = 2 then '이번 주'
           when termType = 3 then '이번 달'
           when termType = 4 then '올해'
           else concat(substr(termValue, 1, 4), '. ', if(substr(termValue, 5, 1) = 0, substr(termValue, 6, 1), substr(termValue, 5, 2)), '. ', substr(termValue, 7, 2), '.')
           end as goalDate,
       case
           when measureType = 1 then concat('목표: ', measureValue, ' km')
           when measureType = 2 then concat('목표: ', substr(measureValue, 1, 2), ':', substr(measureValue, 3, 2))
           when measureType = 3 then concat('목표: ', measureValue, ' 회')
           end as goalValue,
       sum(distance) as totalDistance,
       sum(substr(exerciseTime, 1, 2) * 3600 + substr(exerciseTime, 3, 2) * 60 + substr(exerciseTime, 5, 2)) as totalExerciseTime,
       if(sum(exerciseTime) = 0, 0, count(exerciseTime)) as activityCount, termType, termValue, measureType, measureValue, goalCreated,
             case
                 when termType = 1 then date_format(concat(substr(now(), 1, 11), '23:59'), '%y-%m-%d %H:%i:%s')
                 when termType = 2 then date_format(concat(substr(adddate(now(), - weekday(now()) + 5), 1, 11), '23:59'), '%y-%m-%d %H:%i:%s')
                 when termType = 3 then date_format(concat(last_day(now()), ' 23:59'), '%y-%m-%d %H:%i:%s')
                 when termType = 4 then date_format(concat(substr(now(), 1, 4), '-12-31 23:59:59'), '%y-%m-%d %H:%i:%s')
                 when termType = 5 then date_sub(date_format(concat(substr(termValue, 1, 4), '-', substr(termValue, 5, 2), '-', substr(termValue, 7, 2), ' 23:59:59'), '%y-%m-%d %H:%i:%s'), interval 1 day)
                 end as goalEnded
from (select userGoal.userNo as userNo, e.exerciseName as exerciseName, termType, termValue, measureType, measureValue,
             ifnull(distance, 0) as distance, ifnull(exerciseTime, 0) as exerciseTime, userGoal.createdAt as goalCreated from userGoal
    inner join user u on userGoal.userNo = u.no and u.email = ? and userGoal.no = ?
    inner join exercise e on userGoal.exerciseType = e.no
    left outer join activity a on u.no = a.userNo and userGoal.exerciseType = a.exerciseType and
                                  case
                                      when termType = 1 then date_format(a.createdAt, '%m %d') = date_format(now(), '%m %d')
                                      when termType = 2 then yearweek(a.createdAt) = yearweek(now())
                                      when termType = 3 then a.createdAt > last_day(now() - interval 1 month) and a.createdAt <= last_day(now())
                                      when termType = 4 then date_format(a.createdAt, '%y') = date_format(now(), '%y')
                                      when termType = 5 then a.createdAt between date_format(userGoal.createdAt, '%y-%m-%d') and date_format(userGoal.termValue, '%y-%m-%d')
                                    end
    ) t group by t.userNo) tt;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail, $goalNo]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    return $res[0];

}

function addActivity($userEmail, $sneakersNo, $distance, $exerciseTime, $calorie, $averagePace, $averageSpeed, $maxSpeed, $exerciseType, $goalType, $goalNo, $facialEmoticon, $placeEmoticon, $weather, $temperature, $imageUrl, $memo){
    $pdo = pdoSqlConnect();

    $query = "insert into activity (userNo, sneakersNo, distance, exerciseTime, calorie, averagePace, averageSpeed, maxSpeed, exerciseType, goalType,
                      goalNo, facialEmoticon, placeEmoticon, weather, temperature, imageUrl, memo, startedAt)
                      select no as userNo, ? as sneakersNo, ? as distance, ? as exerciseTime, ? as calorie, ? as averagePace, ? as averageSpeed, ? as maxSpeed, ? as exerciseType, ? as goalType,
                             ? as goalNo, ? as facialEmotiocon, ? as placeEmoticon, ? as weather, ? as temperature, ? as imageurl, ? as memo,
                             DATE_SUB(current_timestamp(), interval concat(if(cast(substr(?, 1, 2) as unsigned) > 24, concat(cast(substr(?, 1, 2) as unsigned) div 24, ' '), '0 '),
    substr(makeTime(if(cast(substr(?, 1, 2) as unsigned) > 24, cast(substr(?, 1, 2) as unsigned) mod 24,substr(?, 1, 2)), substr(?, 3, 2), substr(?, 5, 2)), 1, 8)) day_second) as startedAt from user where email=?";

    $st = $pdo->prepare($query);
    $st->execute([$sneakersNo, $distance, $exerciseTime, $calorie, $averagePace, $averageSpeed, $maxSpeed, $exerciseType, $goalType, $goalNo, $facialEmoticon, $placeEmoticon, $weather, $temperature, $imageUrl, $memo, $exerciseTime, $exerciseTime, $exerciseTime, $exerciseTime, $exerciseTime, $exerciseTime, $exerciseTime, $userEmail]);

    $st = null;
    $pdo = null;

    return 100;
}

function userActivity($userEmail, $exerciseType){
    $pdo = pdoSqlConnect();
    if($exerciseType == null){
        $query = "select concat(month(createdAt), '월 ', year(createdAt)) date, count(*) as totalActivity, round(sum(distance), 2) as totalDistance
from (select activity.no as activityNo, u.no as userNo, distance, activity.createdAt as createdAt from activity inner join user u on activity.userNo = u.no and u.email = ?) a group by date order by date desc;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail]);

        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();

        $query = "select
       activity.no as activityNo,
       exerciseType,
       round(distance, 2) as distance,
       exerciseTime,
       weather,
       replace(substr(activity.createdAt, 1, 10), '-', ' .') as exerciseDate,
       concat(month(activity.createdAt), '월 ', year(activity.createdAt)) as date
from activity inner join user u on activity.userNo = u.no and u.email = ? order by activity.createdAt desc;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail]);

        $st->setFetchMode(PDO::FETCH_ASSOC);
        $item = $st->fetchAll();

        foreach($res as $key => $value){
            $res[$key]['item'] = array();
            foreach($item as $i){
                if($res[$key]['date'] == $i['date']){
                    array_push($res[$key]['item'], $i);
                }
            }
        }

        $st = null;
        $pdo = null;

        if(sizeof($res) == 1)
            return $res;
        else if ($res == null)
            return $res[0];
        else
            return $res;
    }else{
        $query = "select concat(month(createdAt), '월 ', year(createdAt)) date, count(*) as totalActivity, round(sum(distance), 2) as totalDistance
from (select activity.no as activityNo, u.no as userNo, distance, activity.createdAt as createdAt from activity inner join user u on activity.userNo = u.no and u.email = ? and activity.exerciseType = ?) a group by date order by date desc;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail, $exerciseType]);

        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();

        $query = "select
       activity.no as activityNo,
       exerciseType,
       round(distance, 2) as distance,
       exerciseTime,
       weather,
       replace(substr(activity.createdAt, 1, 10), '-', ' .') as exerciseDate,
       concat(month(activity.createdAt), '월 ', year(activity.createdAt)) as date
from activity inner join user u on activity.userNo = u.no and u.email = ? and activity.exerciseType = ? order by activity.createdAt desc;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail, $exerciseType]);

        $st->setFetchMode(PDO::FETCH_ASSOC);
        $item = $st->fetchAll();

        foreach($res as $key => $value){
            $res[$key]['item'] = array();
            foreach($item as $i){
                if($res[$key]['date'] == $i['date']){
                    array_push($res[$key]['item'], $i);
                }
            }
        }

        $st = null;
        $pdo = null;

        if(sizeof($res) == 1)
            return $res;
        else if ($res == null)
            return $res[0];
        else
            return $res;
    }
}

function activityInfo($userEmail, $activityNo){
    $pdo = pdoSqlConnect();

    $query = "select uS.no as sneakersNo, e.exerciseName as exerciseName, distance, exerciseTime, calorie, averagePace, averageSpeed, maxSpeed, activity.startedAt as startedAt,
       activity.imageUrl as imageUrl, weather, temperature, facialEmoticon, placeEmoticon, uS.imageUrl as sneakersUrl, uS.nickname as sneakersNickname from activity
    inner join user u on activity.userNo = u.no and u.email = ? and activity.no = ?
    inner join exercise e on activity.exerciseType = e.no
    inner join userSneakers uS on activity.sneakersNo = uS.no;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail, $activityNo]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $query = "select round(initDistance+sum(t.distance), 2) as sneakersDistance
from (select userSneakers.no as sneakersNo, initDistance, ifnull(a.distance, 0) as distance from userSneakers
inner join user u on userSneakers.userNo = u.no and u.email = ?
inner join activity a on userSneakers.no = a.sneakersNo and userSneakers.no = ?) t group by t.sneakersNo;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail, $res[0]['sneakersNo']]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $distance = $st->fetchAll();

    $res[0]['sneakersDistance'] = $distance[0]['sneakersDistance'];

    return $res[0];
}

function editActivity($activityNo, $startedAt, $sneakersNo, $distance, $exerciseTime, $calorie, $exerciseType, $facialEmoticon, $placeEmoticon, $weather, $temperature, $imageUrl, $memo){
    $pdo = pdoSqlConnect();

    $query = "update activity set exerciseType = ?, startedAt = ?, exerciseTime = ?, distance = ?,
                    calorie = ?, memo = ?, facialEmoticon = ?, placeEmoticon = ?,
                    weather = ?, temperature = ?, sneakersNo = ?, imageUrl = ?,
                    averagePace = if(length(replace(format((cast(substr(?, 1, 2) as unsigned) * 60 + (cast(substr(?, 3, 2) as unsigned) + (cast(substr(?, 5, 2) as unsigned) / 60))) / ?, 2), '.', '')) = 4,
                        replace(format((cast(substr(?, 1, 2) as unsigned) * 60 + (cast(substr(?, 3, 2) as unsigned) + (cast(substr(?, 5, 2) as unsigned) / 60))) / ?, 2), '.', ''),
                        concat('0', replace(format((cast(substr(?, 1, 2) as unsigned) * 60 + (cast(substr(?, 3, 2) as unsigned) + (cast(substr(?, 5, 2) as unsigned) / 60))) / ?, 2), '.', ''))),
                    averageSpeed = format(? * 1 / ((cast(substr(?, 1, 2) as unsigned) * 60 + (cast(substr(?, 3, 2) as unsigned) / 60) + (cast(substr(?, 5, 2) as unsigned) / 3600))), 1) where no = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$exerciseType, $startedAt, $exerciseTime, $distance, $calorie, $memo, $facialEmoticon, $placeEmoticon, $weather, $temperature, $sneakersNo, $imageUrl, $exerciseTime, $exerciseTime, $exerciseTime, $distance, $exerciseTime, $exerciseTime, $exerciseTime, $distance, $exerciseTime, $exerciseTime, $exerciseTime, $distance, $distance, $exerciseTime, $exerciseTime, $exerciseTime, $activityNo]);

    $st = null;
    $pdo = null;

    return 100;
}

function deleteActivity($userEmail, $activityNo){
    $pdo = pdoSqlConnect();

    $query = "delete from activity where no = ? and userNo in (select no as userNo from user where email = ?);";

    $st = $pdo->prepare($query);
    $st->execute([$activityNo, $userEmail]);

    $st = null;
    $pdo = null;

    return 100;
}

function addCommunity($userEmail, $communityName, $depict, $imageUrl){
    $pdo = pdoSqlConnect();

    $query = "insert into community (communityName, depict, imageUrl, master) select ? as communityName, ? as depict, ? as imageUrl, no as master from user where user.email = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$communityName, $depict, $imageUrl, $userEmail]);

    $query = "select last_insert_id();";
    $st = $pdo->prepare($query);
    $st->execute();

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $index = $st->fetchAll();
    $index = $index[0]['last_insert_id()'];

    $query = "insert into communityMember (communityNo, userNo) select ? as communityNo, no as userNo from user where email = ?;";
    $st = $pdo->prepare($query);
    $st->execute([$index, $userEmail]);

    $st = null;
    $pdo = null;

    return 100;
}

function editCommunity($userEmail, $communityNo, $communityName, $depict, $imageUrl){
    $pdo = pdoSqlConnect();

    $query = "update community set communityName = ?, depict = ?, imageUrl = ? where no = ? and master in (select no from user where user.email = ?);";

    $st = $pdo->prepare($query);
    $st->execute([$communityName, $depict, $imageUrl, $communityNo, $userEmail]);

    $st = null;
    $pdo = null;

    return 100;
}

function userCommunity($userEmail){
    $pdo = pdoSqlConnect();

    $query  = "select communityNo, communityName, depict, imageUrl, master from (select community.no as communityNo, communityName, depict, imageUrl, master, cM.userNo from community
    left outer join communityMember cM on community.no = cM.communityNo
    inner join (select a.communityNo, count(a.communityNo) as userCount from (select community.no as communityNo from community
    left outer join communityMember cM on community.no = cM.communityNo ) a group by a.communityNo) t on community.no = t.communityNo) ab where ab.master = (select no from user where email = ?) or ab.userNo = (select no from user where email = ?) group by ab.communityNo;";
    $st = $pdo->prepare($query);
    $st->execute([$userEmail, $userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $query = "select communityNo, count(no) as count from communityMember group by communityNo;";
    $st = $pdo->prepare($query);
    $st->execute();

    $rres = $st->fetchAll();

    $st = null;
    $pdo = null;
    for($i = 0 ; $i < sizeof($res); $i++){
        $temp = 0;
        for($j = 0; $j < sizeof($rres); $j++){
            if($res[$i]['communityNo'] == $rres[$j]['communityNo']) {
                $res[$i]['count'] = $rres[$j]['count'];
                $temp = 1;
            }
        }
        if($temp == 0)
            $res[$i]['count'] = 1;
    }

    return $res;
}

function inviteCommunity($userEmail, $communityNo, $friendNo){
    $pdo = pdoSqlConnect();
    if(isFriend($userEmail, $friendNo)){
        $query = "insert into communityRequest (communityNo, senderNo, receiverNo) select ? as communityNo, user.no as senderNo, ? as receiverNo
from user where user.email=?
            and not exists(select community.no as communityNo, communityName, imageUrl, master from community inner join communityMember cM on community.no = cM.communityNo and (master = ? or userNo = ?))
            and not exists(select * from communityRequest where receiverNo = ?);";

        $st = $pdo->prepare($query);
        $st->execute([$communityNo, $friendNo, $userEmail, $friendNo, $friendNo, $friendNo]);

        $st = null;
        $pdo = null;

        return 100;
    }else
        return 200;
}

function requestedCommunity($userEmail){
    $pdo = pdoSqlConnect();

    $query = "select communityRequest.no as requestNo, communityNo, communityName, imageUrl, lastName, firstname from communityRequest
    inner join community c on communityRequest.communityNo = c.no
    inner join user u on communityRequest.senderNo = u.no and receiverNo = (select no from user where email = ?);";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

function acceptOrDenyInvite($requestNo, $type){
    $pdo = pdoSqlConnect();

    $query = "select * from communityRequest where no=?;";

    $st = $pdo->prepare($query);
    $st->execute([$requestNo]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    if($res == null)
        return 201;

    if($type == 'accept'){
        $query = "insert into communityMember (communityNo, userNo) select communityNo, receiverNo from communityRequest where no = ?;";

        $st = $pdo->prepare($query);
        $st->execute([$requestNo]);
        $code = 100;
    }else if($type == 'denial'){
        $code = 101;
    }else{
        return 200;
    }

    $query = "delete from communityRequest where no=?;";

    $st = $pdo->prepare($query);
    $st->execute([$requestNo]);

    $st = null;
    $pdo = null;

    return $code;
}

function exitCommunity($userEmail, $communityNo){
    $pdo = pdoSqlConnect();

    $query = "delete from communityMember where communityNo = ? and userNo = (select no from user where email = ?);";

    $st = $pdo->prepare($query);
    $st->execute([$communityNo, $userEmail]);

    $st = null;
    $pdo = null;

    return 100;
}

function kickUser($userEmail, $communityNo, $friendNo){
    $pdo = pdoSqlConnect();

    $query = "delete from communityMember where communityNo = ? and userNo = ? and exists (select master from community inner join user u on u.no = community.master and u.email=?);";

    $st = $pdo->prepare($query);
    $st->execute([$communityNo, $friendNo, $userEmail]);

    $st = null;
    $pdo = null;

    return 100;
}

function communityInfo($userEmail, $communityNo){
    $pdo = pdoSqlConnect();

    $query = "select c.no as communityNo, communityName, depict, c.imageUrl as communityImageUrl, master, sum(distance) as totalDistance from communityMember
    inner join user u on communityMember.userNo = u.no
    inner join activity a on u.no = a.userNo
    inner join community c on communityMember.communityNo = c.no and c.createdAt <= a.startedAt and communityNo = ? group by communityNo;";

    $st = $pdo->prepare($query);
    $st->execute([$communityNo]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();


    $query = "select communityMember.userNo, lastName, firstName, profileImage from communityMember
    inner join user u on communityMember.userNo = u.no and communityNo = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$communityNo]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $users = $st->fetchAll();

    $res[0]['users'] = array();
    foreach($users as $u){
        array_push($res[0]['users'], $u);
    }

    $st = null;
    $pdo = null;

    return $res;
}

function statistic($userEmail, $exerciseType){
    $pdo = pdoSqlConnect();

    if($exerciseType == null){
        $query = "select date, activityCount, totalDistance, concat(totalExerciseTime div 3600, '시간', totalExerciseTime mod 3600 div  60, '분') as totalExerciseTime, totalCalorie from (select activityCreatedAt as date, count(activityNo) as activityCount, sum(distance) as totalDistance, sum(exerciseTime) as totalExerciseTime, sum(calorie) as totalCalorie
from (select activity.no as activityNo, distance, cast(substr(exerciseTime, 1, 2) as unsigned) * 3600 + cast(substr(exerciseTime, 3, 2) as unsigned) * 60 + cast(substr(exerciseTime, 5, 2) as unsigned) as exerciseTime, calorie, date_format(activity.createdAt, '%Y-%m') as activityCreatedAt from activity
    inner join user u on activity.userNo = u.no and u.email = ?) t group by t.activityCreatedAt) tt where date = date_format(now(), '%Y-%m') or date = date_format(date_sub(now(), interval 1 month), '%Y-%m') order by date desc;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail]);

        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();

        if(sizeof($res) == 0){
            $res[0]['date'] = date('Y-m');
            $res[0]['activityCount'] = 0;
            $res[0]['totalDistance'] = 0;
            $res[0]['totalExerciseTime'] = 0;
            $res[0]['totalCalorie'] = 0;
            $res[1]['date'] = date("Y-m",strtotime ("-1 months"));
            $res[1]['activityCount'] = 0;
            $res[1]['totalDistance'] = 0;
            $res[1]['totalExerciseTime'] = 0;
            $res[1]['totalCalorie'] = 0;
        }else if(sizeof($res) == 1){
            $res[1]['date'] = date("Y-m",strtotime ("-1 months"));
            $res[1]['activityCount'] = 0;
            $res[1]['totalDistance'] = 0;
            $res[1]['totalExerciseTime'] = 0;
            $res[1]['totalCalorie'] = 0;
        }
    }else {
        $query = "select date, activityCount, totalDistance, concat(totalExerciseTime div 3600, '시간', totalExerciseTime mod 3600 div  60, '분') as totalExerciseTime, totalCalorie from (select activityCreatedAt as date, count(activityNo) as activityCount, sum(distance) as totalDistance, sum(exerciseTime) as totalExerciseTime, sum(calorie) as totalCalorie
from (select activity.no as activityNo, exerciseType, distance, cast(substr(exerciseTime, 1, 2) as unsigned) * 3600 + cast(substr(exerciseTime, 3, 2) as unsigned) * 60 + cast(substr(exerciseTime, 5, 2) as unsigned) as exerciseTime, calorie, date_format(activity.createdAt, '%Y-%m') as activityCreatedAt from activity
    inner join user u on activity.userNo = u.no and u.email = ? and exerciseType = ?) t group by t.activityCreatedAt) tt where date = date_format(now(), '%Y-%m') or date = date_format(date_sub(now(), interval 1 month), '%Y-%m') order by date desc;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail, $exerciseType]);

        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();

        if (sizeof($res) == 0) {
            $res[0]['date'] = date('Y-m');
            $res[0]['activityCount'] = 0;
            $res[0]['totalDistance'] = 0;
            $res[0]['totalExerciseTime'] = 0;
            $res[0]['totalCalorie'] = 0;
            $res[1]['date'] = date("Y-m", strtotime("-1 months"));
            $res[1]['activityCount'] = 0;
            $res[1]['totalDistance'] = 0;
            $res[1]['totalExerciseTime'] = 0;
            $res[1]['totalCalorie'] = 0;
        } else if (sizeof($res) == 1) {
            $res[1]['date'] = date("Y-m", strtotime("-1 months"));
            $res[1]['activityCount'] = 0;
            $res[1]['totalDistance'] = 0;
            $res[1]['totalExerciseTime'] = 0;
            $res[1]['totalCalorie'] = 0;
        }
    }

    return $res;
}

function leaderboard($userEmail){
    $pdo = pdoSqlConnect();

    $query = "select * from(select no as userNo, firstName, lastName, profileImage, totalDistance from user inner join(select friendNo, sum(distance) as totalDistance from(select friendNo, lastName, firstName, profileImage, ifnull(distance, 0) as distance from
(select no as friendNo, lastName, firstName, profileImage from user
inner join (select followingNo as friendNo, followerNo as myNo from friend inner join user u on friend.followerNo = u.no and u.email = ?) a on user.no = a.friendNo
union
select no as friendNo, lastName, firstName, profileImage from user
inner join (select followerNo as friendNo, followingNo as myNo from friend inner join user u on friend.followingNo = u.no and u.email = ?) a on user.no = a.friendNo) t left outer join activity a on a.userNo = t.friendNo) tt group by tt.friendNo)ttt on ttt.friendNo = user.no
union
select user.no as userNo, firstName, lastName, profileImage, sum(distance) as totalDistance from user inner join activity a on user.no = a.userNo and user.email = ? group by user.no) tttt order by tttt.totalDistance desc;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail, $userEmail, $userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    return $res;
}

function progressStatus($userEmail){
    $pdo = pdoSqlConnect();

    $query = "select activity.no as activityNo, exerciseType, distance,
       concat(substr(exerciseTime, 1, 2), ':', substr(exerciseTime, 3, 2), ':', substr(exerciseTime, 5, 2)) as exerciseTime,
       case
           when date_format(activity.createdAt, '%Y-%m-%d') = date_format(now(), '%Y-%m-%d') then '오늘'
           when date_format(activity.createdAt, '%Y-%m-%d') = date_format(date_sub(now(), interval 1 day), '%Y-%m-%d') then '어제'
           else date_format(activity.createdAt, '%Y. %m. %d')
        end as startedAt
       from activity inner join user u on u.no = activity.userNo and u.email = ? order by activity.createdAt desc limit 3;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $recentActivity = $st->fetchAll();

    $query = "select distance, concat(exerciseTime div (distance * 60) div 60, ':', exerciseTime div (distance * 60) mod 60) as averagePace, activityCount from (select sum(distance) as distance,
       sum(cast(substr(exerciseTime, 1, 2) as unsigned) * 3600 + cast(substr(exerciseTime, 3, 2) as unsigned) * 60 + cast(substr(exerciseTime, 5, 2) as unsigned)) as exerciseTime,
       count(*) as activityCount from activity inner join user u on u.no = activity.userNo and u.email = ?) t;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $query = "select sneakersNo, nickname, imageUrl, round(initDistance+sum(t.distance), 2) as sneakersDistance, limitDistance from (select userSneakers.no as sneakersNo, nickname, userSneakers.imageUrl as imageUrl, initDistance, ifnull(a.distance, 0) as distance, limitDistance from userSneakers
    inner join user u on u.no = userSneakers.userNo and u.email = ?
    left outer join activity a on userSneakers.no = a.sneakersNo) t group by t.sneakersNo limit 1;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $sneakers = $st->fetchAll();

    $query = "select date_format(activity.createdAt, '%m') month, count(*) as activityCount from activity inner join user u on u.no = activity.userNo and u.email = ? and activity.createdAt between date_format(concat(date_format(now(), '%Y'), '0101'), '%Y-%m-%d') and date_format(concat(date_format(now(), '%Y'), '1231'), '%Y-%m-%d') group by month;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $month = $st->fetchAll();

    $query = "select userGoal.no as goalNo, termValue,
       case
           when termType = 1 then '오늘'
           when termType = 2 then '이번 주'
           when termType = 3 then '이번 달'
           when termType = 4 then '올해'
           when termType = 5 then concat(concat(concat(concat(substr(termValue, 1, 4), '. '), concat(substr(termValue, 5, 2), '. '))
               , concat(substr(termValue, 7, 2), '. ')), '까지')
        end as termName,
       termType,
       case
           when measureType = 1 then concat('목표: ', concat(measureValue, ' km'))
           when measureType = 2 then concat('목표: ', concat(concat(substr(measureValue, 1, 2), ':'), substr(measureValue, 3, 2)))
           when measureType = 3 then concat('목표: ', concat(measureValue, ' 회'))
           end as goalName,
       measureValue, measureType, exerciseName, exerciseType, isTerminate from userGoal
    inner join user u on userGoal.userNo = u.no and u.email = ?
    inner join exercise e on userGoal.exerciseType = e.no limit 2;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $goal = $st->fetchAll();

    $res[0]['sneakersNo'] = $sneakers[0]['sneakersNo'];
    $res[0]['sneakersNickname'] = $sneakers[0]['nickname'];
    $res[0]['sneakersImageUrl'] = $sneakers[0]['imageUrl'];
    $res[0]['sneakersDistance'] = $sneakers[0]['sneakersDistance'];
    $res[0]['limitDistance'] = $sneakers[0]['limitDistance'];
    $res[0]['recentActivity'] = $recentActivity;
    $res[0]['monthActivity'] = $month;
    $res[0]['goal'] = $goal;

    //echo $sneakers[0]['nickname'];
   // echo json_encode($sneakers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $res;

}