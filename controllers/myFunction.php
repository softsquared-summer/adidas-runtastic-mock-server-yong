<?php

function createUser($email, $pw, $lName, $fName, $sex, $birth, $profileImage){
    if(availableEmail($email)){
        $pdo = pdoSqlConnect();
        if($sex == null && $profileImage == null){
            $query = "insert into user (email, pw, lName, fName, birth) values (?, ?, ?, ?, ?);";

            $st = $pdo->prepare($query);
            $st->execute([$email, $pw, $lName, $fName, $birth]);
        }else if($sex != null && $profileImage == null){
            if($sex != 1){
                $query = "insert into user (email, pw, lName, fName, birth, sex, profileImage) values (?, ?, ?, ?, ?, ?, ?);";

                $st = $pdo->prepare($query);
                $st->execute([$email, $pw, $lName, $fName, $birth, $sex, "https://dragonhyun.com/images/profileFemaleDefault.JPG"]);
            }
            else {
                $query = "insert into user (email, pw, lName, fName, birth, sex) values (?, ?, ?, ?, ?, ?);";

                $st = $pdo->prepare($query);
                $st->execute([$email, $pw, $lName, $fName, $birth, $sex]);
            }
        }else if($sex == null && $profileImage != null){
            $query = "insert into user (email, pw, lName, fName, birth, profileImage) values (?, ?, ?, ?, ?, ?);";
            
            /*
             * 추후 upload기능 완성시키면 수정할 것.
             * $query = "insert into user (email, pw, lName, fName, birth) values (?, ?, ?, ?, ?);";
             * $st = $pdo->prepare($query);
             * $st->execute([$email, $pw, $lName, $fName, $birth]);
             * 
             * uploadProfileImage($file, $userNo)  -> userNo는 위의 query에서 no 받아와야할듯
             */

            $st = $pdo->prepare($query);
            $st->execute([$email, $pw, $lName, $fName, $birth, $profileImage]);
        }else{
            $query = "insert into user (email, pw, lName, fName, birth, sex, profileImage) values (?, ?, ?, ?, ?, ?, ?);";

            $st = $pdo->prepare($query);
            $st->execute([$email, $pw, $lName, $fName, $birth, $sex, $profileImage]);
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

function userProfile($userNo){
    $pdo = pdoSqlConnect();

    $query = "select profileImage, fName, lName, createdAt from user where no=?;";

    $st = $pdo->prepare($query);
    $st->execute([$userNo]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

function editProfile($profileImage, $lName, $fName, $sex, $email, $birth, $height, $heightType, $weight, $weightType, $userEmail){
    $pdo = pdoSqlConnect();

    $query = "update user set profileImage=?, lName=?, fName=?, sex=?, email=?, birth=?, height=?, heightType=?, weight=?, weightType=? where email=?;";

    $st = $pdo->prepare($query);
    $st->execute([$profileImage, $lName, $fName, $sex, $email, $birth, $height, $heightType, $weight, $weightType, $userEmail]);

    $st = null;
    $pdo = null;

    return 100;
}

function userFriend($userEmail){
    $pdo = pdoSqlConnect();

    $query = "select no as friendNo, lName, fName, profileImage from user 
inner join (select followingNo as friendNo, followerNo as myNo from friend inner join user u on friend.followerNo = u.no and u.email = ?) a on user.no = a.friendNo
union
select no as friendNo, lName, fName, profileImage from user 
inner join (select followerNo as friendNo, followingNo as myNo from friend inner join user u on friend.followingNo = u.no and u.email = ?) a on user.no = a.friendNo;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail, $userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if(sizeof($res) == 1)
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

    $query = "select requestNo, no as senderNo, lName, fName, profileImage from user 
inner join (select friendRequest.no as requestNo, senderNo, receiverNo from friendRequest inner join user u on friendRequest.receiverNo = u.no and u.email=?) f on f.senderNo = user.no;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if(sizeof($res) == 1)
        return $res[0];
    else
        return $res;
}

function acceptOrDenyRequest($requestNo, $type){
    if($type == 'accept'){
        $pdo = pdoSqlConnect();

        $query = "insert into friend (followingNo, followerNo) select receiverNo, senderNo from friendRequest where no=?;";

        $st = $pdo->prepare($query);
        $st->execute([$requestNo]);
        $code = 100;
    }else if($type == 'denial'){
        $pdo = pdoSqlConnect();
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
        return $res[0];
    else
        return $res;
}

function addSneakers($userEmail, $modelNo, $nickname, $imageUrl, $sizeType, $sizeValue, $colorNo, $startedAt, $limitDistance){
    $pdo = pdoSqlConnect();
    if(trim($nickname) == "")
        $nickname = null;
    if(trim($imageUrl) == "")
        $imageUrl = null;

    if($nickname == null && $imageUrl == null){
        $query = "insert into userSneakers (userNo, modelNo, nickname, imageUrl, sizeType, sizeValue, colorNo, startedAt, limitDistance)
 select (select no from user where email=?) as userNo, no as modelNo, modelName as nickname, imageUrl, ?, ?, ?, ?, ? from sneakersModel where no=?;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail, $sizeType, $sizeValue, $colorNo, $startedAt, $limitDistance, $modelNo]);
    }else if($nickname == null && $imageUrl != null){
        $query = "insert into userSneakers (userNo, modelNo, nickname, imageUrl, sizeType, sizeValue, colorNo, startedAt, limitDistance)
 select (select no from user where email=?) as userNo, no as modelNo, modelName as nickname, ?, ?, ?, ?, ?, ? from sneakersModel where no=?;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail, $imageUrl, $sizeType, $sizeValue, $colorNo, $startedAt, $limitDistance, $modelNo]);
    }else if($nickname != null && $imageUrl == null){
        $query = "insert into userSneakers (userNo, modelNo, nickname, imageUrl, sizeType, sizeValue, colorNo, startedAt, limitDistance)
 select (select no from user where email=?) as userNo, no as modelNo, ?, imageUrl, ?, ?, ?, ?, ? from sneakersModel where no=?;";

        $st = $pdo->prepare($query);
        $st->execute([$userEmail, $nickname, $sizeType, $sizeValue, $colorNo, $startedAt, $limitDistance, $modelNo]);
    }else {
        $query = "insert into userSneakers (userNo, modelNo, nickname, imageUrl, sizeType, sizeValue, colorNo, startedAt, limitDistance) select no as userNo, ?, ?, ?, ?, ?, ?, ?, ? from user where email=?;";

        $st = $pdo->prepare($query);
        $st->execute([$modelNo, $nickname, $imageUrl, $sizeType, $sizeValue, $colorNo, $startedAt, $limitDistance, $userEmail]);
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

    $query = "select userSneakers.no as sneakersNo, userNo, nickname, imageUrl, limitDistance from userSneakers 
inner join user u on userSneakers.userNo = u.no and u.email = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if(sizeof($res) == 1)
        return $res[0];
    else
        return $res;
}

function sneakersInfo($userEmail, $sneakersNo){
    $pdo = pdoSqlConnect();

    $query = "select nickname, modelName, userSneakers.imageUrl as imageUrl, limitDistance, startedAt from userSneakers
    inner join user u on userSneakers.userNo = u.no and u.email = ? and userSneakers.no = ?
    inner join sneakersModel sM on userSneakers.modelNo = sM.no
    inner join sneakersBrand sB on sM.brandNo = sB.no;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail, $sneakersNo]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if(sizeof($res) == 1)
        return $res[0];
    else
        return $res;
}

function userGoal($userEmail){
    $pdo = pdoSqlConnect();

    $query = "select termValue,
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
       measureValue, measureType, exerciseName, exerciseType from userGoal
    inner join user u on userGoal.userNo = u.no and u.email = ?
    inner join exercise e on userGoal.exerciseType = e.no;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if(sizeof($res) == 1)
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