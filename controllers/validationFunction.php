<?php

function availableEmail($email){
    $pdo = pdoSqlConnect();
    $query = "select * from user where email=?;";

    $st = $pdo->prepare($query);
    $st->execute([$email]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if($res == null)
        return true;
    else
        return false;
}

function isExistUser($userNo){
    $pdo = pdoSqlConnect();
    $query = "select * from user where no=?;";

    $st = $pdo->prepare($query);
    $st->execute([$userNo]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if($res == null)
        return false;
    else
        return true;
}

function isValidUser($email, $pw){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM user WHERE email= ? AND pw = ?) AS exist;";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email, $pw]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);

}

function pwCheck($_str){
    $pw = $_str;
    $num = preg_match('/[0-9]/u', $pw);
    $sEng = preg_match('/[a-z]/u', $pw);
    $bEng = preg_match('/[A-Z]/u', $pw);

    if(strlen($pw) < 8 || strlen($pw) > 30){
        return array(false, "비밀번호는 소문자, 대문자, 숫자 하나씩 혼합하여 최소8 ~ 최대30");
        exit;
    }
    if($num == 0 || $sEng == 0 || $bEng == 0){
        return array(false, "소문자, 대문자, 숫자를 혼합해주세요");
    }
     return array(true);
}

function isFriend($userEmail, $userNo){
    $pdo = pdoSqlConnect();
    $query = "select followingNo as friendNo, followerNo as myNo from friend inner join user u on friend.followerNo = u.no and u.email = ? and friend.followingNo = ?
union
select followerNo as friendNo, followingNo as myNo from friend inner join user u on friend.followingNo = u.no and u.email = ? and friend.followerNo = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$userEmail, $userNo, $userEmail, $userNo]);

    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    if($res == null)
        return false;
    else
        return true;

}