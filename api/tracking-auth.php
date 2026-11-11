<?php
declare(strict_types=1);

function send_shop_mail(string $address, string $subject, string $message, array $settings): void {
    $loader = dirname(__DIR__).'/vendor/autoload.php';
    if (!is_file($loader) || empty($settings['username']) || empty($settings['password'])) {
        throw new RuntimeException('SMTP configuration missing');
    }
    require_once $loader;
    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $settings['host'] ?? 'smtp.gmail.com';
    $mailer->Port = (int)($settings['port'] ?? 587);
    $mailer->SMTPAuth = true;
    $mailer->Username = $settings['username'];
    $mailer->Password = $settings['password'];
    $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->Timeout = 12;
    $mailer->setFrom(($settings['from'] ?? '') ?: $mailer->Username,'RepairDesk');
    $mailer->addAddress($address);
    $mailer->Subject = $subject;
    $mailer->Body = $message;
    $mailer->send();
}

function request_tracking_otp(array $input, array $settings): void {
    global $db;
    unset($_SESSION['tracking'], $_SESSION['otp_challenge']);
    limited('request-otp',15);
    $code = strtoupper(text($input['code'] ?? '',8));
    if (!preg_match('/^[A-Z2-9]{8}$/D',$code)) fail('Enter the eight-character ticket code.');
    $db->beginTransaction();
    $job = query('SELECT j.id,c.email FROM jobs j JOIN customers c ON c.id=j.customer_id WHERE j.tracking_code=? FOR UPDATE',[$code])->fetch();
    $sent = ['ok'=>true];
    if (!$job) { $db->commit(); reply($sent); }
    $old = query('SELECT challenge, issued_at > UTC_TIMESTAMP() - INTERVAL 60 SECOND AS recent FROM tracking_otp WHERE job_id=?',[$job['id']])->fetch();
    if ($old && $old['recent']) {
        $db->commit();
        // Retain usability of the previously mailed code within its issuing session.
        if (($_SESSION['last_otp'] ?? '') === $old['challenge']) $_SESSION['otp_challenge']=[$job['id'],$old['challenge']];
        reply($sent);
    }
    $otp = str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);
    $challenge = bin2hex(random_bytes(16));
    query('INSERT INTO tracking_otp(job_id,challenge,digest,issued_at,attempts) VALUES(?,?,?,UTC_TIMESTAMP(),0) ON DUPLICATE KEY UPDATE challenge=VALUES(challenge),digest=VALUES(digest),issued_at=UTC_TIMESTAMP(),attempts=0',[$job['id'],$challenge,password_hash($otp,PASSWORD_DEFAULT)]);
    $db->commit();
    try { send_shop_mail($job['email'],'RepairDesk verification code',"Your code is $otp. It expires in ten minutes.",$settings); }
    catch (Throwable $e) { query('DELETE FROM tracking_otp WHERE job_id=? AND challenge=?',[$job['id'],$challenge]); fail('Email delivery failed. Try again later.',503); }
    $_SESSION['otp_challenge']=[$job['id'],$challenge];
    $_SESSION['last_otp']=$challenge;
    reply($sent);
}

function verify_tracking_otp(array $input): void {
    global $db;
    limited('verify-otp',30);
    $otp = text($input['otp'] ?? '',6);
    [$id,$challenge] = $_SESSION['otp_challenge'] ?? [0,''];
    $db->beginTransaction();
    $row = query('SELECT digest,attempts FROM tracking_otp WHERE job_id=? AND challenge=? AND issued_at > UTC_TIMESTAMP() - INTERVAL 10 MINUTE FOR UPDATE',[$id,$challenge])->fetch();
    if (!$row || $row['attempts']>=5 || !preg_match('/^[0-9]{6}$/D',$otp) || !password_verify($otp,$row['digest'])) {
        if ($row && $row['attempts']<5) query('UPDATE tracking_otp SET attempts=attempts+1 WHERE job_id=?',[$id]);
        $db->commit(); fail('Incorrect or expired verification code.',403);
    }
    query('DELETE FROM tracking_otp WHERE job_id=?',[$id]);
    $db->commit(); unset($_SESSION['otp_challenge'],$_SESSION['last_otp']);
    $_SESSION['tracking']=['id'=>(int)$id,'until'=>time()+900];
    reply(shape(query('SELECT * FROM jobs WHERE id=?',[$id])->fetch(),true));
}
