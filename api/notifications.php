<?php
declare(strict_types=1);

function queue_job_email(int $jobId,string $message): int {
    global $db;
    query('INSERT INTO job_notifications(job_id,message) VALUES(?,?)',[$jobId,$message]);
    return (int)$db->lastInsertId();
}

function deliver_job_email(int $id,array $config): bool {
    global $db;
    $db->beginTransaction();
    try {
        $row=query('SELECT n.message,n.sent_at,j.tracking_code,c.email FROM job_notifications n JOIN jobs j ON j.id=n.job_id JOIN customers c ON c.id=j.customer_id WHERE n.id=? FOR UPDATE',[$id])->fetch();
        if (!$row || $row['sent_at']) { $db->commit(); return (bool)$row; }
        $url=$config['tracking_url']??'';
        $message=$row['message']."\nTicket: ".$row['tracking_code'];
        if ($url) $message.="\nView repair: ".$url;
        send_shop_mail($row['email'],'RepairDesk repair update',$message,$config['smtp']??[]);
        query('UPDATE job_notifications SET sent_at=UTC_TIMESTAMP() WHERE id=?',[$id]);
        $db->commit(); return true;
    } catch(Throwable $error) {
        if($db->inTransaction())$db->rollBack();
        return false;
    }
}
