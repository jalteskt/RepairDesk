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
        $ticketUrl = null;
        if (filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http','https'], true)) {
            $ticketUrl = explode('#', $url, 2)[0].'#ticket='.rawurlencode($row['tracking_code']);
        }
        send_shop_mail($row['email'],'RepairDesk repair update',$message,$config['smtp']??[],$ticketUrl);
        query('UPDATE job_notifications SET sent_at=UTC_TIMESTAMP() WHERE id=?',[$id]);
        $db->commit(); return true;
    } catch(Throwable $error) {
        if($db->inTransaction())$db->rollBack();
        return false;
    }
}

// Runs within the customer action transaction.
function notify_staff_of_customer_action(array $job,string $kind): void {
    $labels=['approve'=>'Customer approved the quotation','decline'=>'Customer declined the quotation','message'=>'Customer sent a message','cancellation'=>'Customer requested cancellation','warranty'=>'Customer submitted a warranty concern'];
    query("INSERT INTO staff_notifications(user_id,job_id,message) SELECT id,?,? FROM users WHERE active=1 AND (role='admin' OR (role='technician' AND id=?))",[$job['id'],$labels[$kind],$job['technician_id']]);
}
function staff_notifications(string $method,array $body): void {
    global $user;
    $scope="n.user_id=? AND (?='admin' OR j.technician_id=?)";
    $args=[$user['id'],$user['role'],$user['id']];
    if ($method==='POST') {
        $id=filter_var($body['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if ($id===false) fail('Invalid notification.');
        query("UPDATE staff_notifications n JOIN jobs j ON j.id=n.job_id SET n.read_at=COALESCE(n.read_at,UTC_TIMESTAMP()) WHERE $scope AND n.id=?",[...$args,$id]);
        reply(['ok'=>true]);
    }
    $count=(int)query("SELECT COUNT(*) FROM staff_notifications n JOIN jobs j ON j.id=n.job_id WHERE $scope AND n.read_at IS NULL",$args)->fetchColumn();
    $items=query("SELECT n.id,n.job_id,n.message,n.created_at,n.read_at,j.tracking_code FROM staff_notifications n JOIN jobs j ON j.id=n.job_id WHERE $scope ORDER BY (n.read_at IS NULL) DESC,n.id DESC LIMIT 50",$args)->fetchAll();
    reply(['unread'=>$count,'items'=>$items]);
}
