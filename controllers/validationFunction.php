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