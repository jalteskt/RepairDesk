<?php
declare(strict_types=1);
function remember_cookie(string $token,int $expires): void {
    setcookie('rd_saved_login',$token,['expires'=>$expires,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
}
function forget_device(): void {
    $token=$_COOKIE['rd_saved_login']??'';
    if (preg_match('/^([a-f0-9]{32})\.([a-f0-9]{64})$/D',$token,$m)) {
        query('DELETE FROM saved_logins WHERE selector=? AND token_hash=?',[$m[1],hash('sha256',$m[2])]);
    }
    remember_cookie('',time()-3600);
}
function remember_device(int $id): void {
    $selector=bin2hex(random_bytes(16)); $secret=bin2hex(random_bytes(32));
    query('INSERT INTO saved_logins(selector,user_id,token_hash,expires_at) VALUES(?,?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 DAY))',[$selector,$id,hash('sha256',$secret)]);
    remember_cookie($selector.'.'.$secret,time()+30*86400);
}
function restore_device(): void {
    if (isset($_SESSION['uid']) || empty($_COOKIE['rd_saved_login'])) return;
    if (!preg_match('/^([a-f0-9]{32})\.([a-f0-9]{64})$/D',$_COOKIE['rd_saved_login'],$m)) return;
    $row=query('SELECT s.user_id,s.token_hash FROM saved_logins s JOIN users u ON u.id=s.user_id WHERE selector=? AND expires_at>UTC_TIMESTAMP() AND u.active=1',[$m[1]])->fetch();
    if (!$row || !hash_equals($row['token_hash'],hash('sha256',$m[2]))) { remember_cookie('',time()-3600); return; }
    session_regenerate_id(true); $_SESSION['uid']=$row['user_id'];
}
