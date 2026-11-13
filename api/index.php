<?php
declare(strict_types=1);
ini_set('display_errors','0');
require_once __DIR__.'/tracking-auth.php';
require_once __DIR__.'/notifications.php';
require_once __DIR__.'/saved-login.php';
require_once __DIR__.'/staff-recovery.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true, 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite'=>'Lax', 'path'=>'/']);
session_start();
function reply($data, int $status=200): void { http_response_code($status); echo json_encode($data, JSON_THROW_ON_ERROR); exit; }
function fail(string $message, int $status=400): void { reply(['error'=>$message],$status); }
function query(string $sql, array $args=[]): PDOStatement { global $db; $q=$db->prepare($sql); $q->execute($args); return $q; }
function text($value, int $max=255, bool $required=true): string {
    if (!is_string($value) || strlen(trim($value))>$max || ($required && trim($value)==='')) fail('Invalid text field.');
    return trim($value);
}
function email($value): string { $v=strtolower(text($value)); if (!filter_var($v,FILTER_VALIDATE_EMAIL)) fail('Enter a valid email.'); return $v; }
function money($value): string { if (!is_numeric($value) || $value<0 || $value>9999999.99) fail('Invalid amount.'); return number_format((float)$value,2,'.',''); }
function verify_password(string $password,string $hash): bool {
    if (str_starts_with($hash,'pbkdf2$')) {
        $parts=explode('$',$hash);
        return count($parts)===4 && $parts[1]==='600000' && hash_equals($parts[3],hash_pbkdf2('sha256',$password,$parts[2],600000,64));
    }
    return password_verify($password,$hash);
}
function admin(): void { global $user; if (!$user || $user['role']!=='admin') fail('Administrator access required.',403); }
function event(int $id,string $action): void { global $user; query('INSERT INTO job_events(job_id,action,actor) VALUES(?,?,?)',[$id,$action,$user['name']??'Customer']); }
function limited(string $scope,int $max): void {
    $key=hash('sha256',$scope.'|'.($_SERVER['REMOTE_ADDR']??''));
    query('INSERT INTO rate_limits(bucket,hits,expires_at) VALUES(?,1,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE hits=IF(expires_at<UTC_TIMESTAMP(),1,hits+1), expires_at=IF(expires_at<UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE),expires_at)',[$key]);
    if ((int)query('SELECT hits FROM rate_limits WHERE bucket=?',[$key])->fetchColumn()>$max) fail('Too many attempts. Try again in 15 minutes.',429);
}
function intake_photo(): ?array {
    $file = $_FILES['photo'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) fail('Photo upload failed. Choose a smaller image and try again.');
    if ($file['size'] > 2 * 1024 * 1024) fail('Choose a photo of 2 MB or less.', 413);
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) fail('Choose a JPEG, PNG or WebP photo.');
    $bytes = file_get_contents($file['tmp_name']);
    if ($bytes === false) fail('Could not read the uploaded photo.');
    return [$info['mime'], $bytes];
}

function job(int $id,bool $lock=false): array {
    global $user;
    $j=query('SELECT * FROM jobs WHERE id=?'.($lock?' FOR UPDATE':''),[$id])->fetch();
    if (!$j || ($user && $user['role']==='technician' && (int)$j['technician_id']!==(int)$user['id'])) fail('Job not found.',404);
    return $j;
}
function shape(array $j,bool $public=false): array {
    $c=query('SELECT name,email,phone,consent_at FROM customers WHERE id=?',[$j['customer_id']])->fetch();
    $t=query('SELECT name FROM users WHERE id=?',[$j['technician_id']])->fetchColumn()?:'Unassigned';
    $notes=query('SELECT id,created_at AS time,author,text,customer_visible AS customerVisible FROM job_notes WHERE job_id=?'.($public?' AND customer_visible=1':'').' ORDER BY id DESC',[$j['id']])->fetchAll();
    foreach($notes as &$note) $note['customerVisible']=(bool)$note['customerVisible']; unset($note);
    $billing=['diagnosticFee'=>(float)$j['diagnostic_fee'],'parts'=>(float)$j['parts'],'labor'=>(float)$j['labor']];
    $billing['total']=round(array_sum($billing),2);
    $billing['partItems'] = json_decode($j['quote_parts_json'] ?? '[]', true, 512, JSON_THROW_ON_ERROR);
    $paid=(float)query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE job_id=?',[$j['id']])->fetchColumn();
    $status=$j['status'];
    $atCounter = in_array($status,['Ready','Cannot Be Repaired'],true) && !$j['picked_up_at'];
    $canPay = $atCounter && $paid < $billing['total'];
    $canPickUp = $atCounter && !$canPay;
    $canCancel = in_array($status,['Received','Diagnosing','Awaiting Quote Approval'],true);
    if ($public) { [$local,$domain]=explode('@',$c['email'],2); $c=['name'=>$c['name'],'email'=>substr($local,0,1).'***@'.substr($domain,0,1).'***.'.pathinfo($domain,PATHINFO_EXTENSION)]; }
    return ['hasPhoto'=>(bool)query('SELECT 1 FROM job_photos WHERE job_id=?',[$j['id']])->fetchColumn(),'pendingEmails'=>$public?0:(int)query('SELECT COUNT(*) FROM job_notifications WHERE job_id=? AND sent_at IS NULL',[$j['id']])->fetchColumn(),'canPay'=>$canPay,'canPickUp'=>$canPickUp,'canCancel'=>$canCancel,'pickedUpAt'=>$j['picked_up_at'],'id'=>'JOB-'.$j['id'],'dbId'=>(int)$j['id'],'version'=>(int)$j['version'],'code'=>$j['tracking_code'],'customer'=>$c,'technician'=>$t,'technicianId'=>$j['technician_id'],'appliance'=>$j['appliance'],'brand'=>$j['brand'],'model'=>$j['model'],'serial'=>$public?'':$j['serial'],'reportedIssue'=>$j['reported_issue'],'accessories'=>$public?'':$j['accessories'],'condition'=>$public?'':$j['item_condition'],'status'=>$status,'tab'=>$status==='Collected'?'completed':(in_array($status,['Cancelled','Declined','Cannot Be Repaired'],true)?'cancelled':'pending'),'dateStarted'=>substr($j['created_at'],0,10),'estCompletion'=>$j['estimated_completion']??'Not set','warrantyUntil'=>$j['warranty_until'],'billing'=>$billing,'paidAmount'=>$paid,'quoteDecision'=>$j['quote_decision'],'payment'=>$paid>=$billing['total']?'Paid':($paid>0?'Deposit':'Unpaid'),'notes'=>$notes,'timeline'=>$public?[]:query('SELECT created_at AS time,action,actor AS user FROM job_events WHERE job_id=? ORDER BY id DESC',[$j['id']])->fetchAll()];
}
try {
    if (!is_file(__DIR__.'/config.php')) fail('Database setup required. See BACKEND.md.',503);
    $config=require __DIR__.'/config.php';
    $db=new PDO('mysql:host='.$config['host'].';dbname='.$config['database'].';charset=utf8mb4',$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $db->exec("SET time_zone = '+00:00'");
    $action=$_GET['action']??'session';
    $method=$_SERVER['REQUEST_METHOD'];
    if (!in_array($method,['GET','POST'],true)) fail('Method not allowed.',405);
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
    if (isset($_SESSION['last']) && time()-$_SESSION['last']>3600) { unset($_SESSION['uid'],$_SESSION['tracking']); }
    $_SESSION['last']=time();
    restore_device();
    $user=isset($_SESSION['uid'])?query('SELECT id,name,email,role FROM users WHERE id=? AND active=1',[$_SESSION['uid']])->fetch():false;
    if ($method==='GET' && $action==='session') reply(['csrf'=>$_SESSION['csrf'],'user'=>$user?:null]);
    if ($method === 'GET' && $action === 'appliance-photo') {
        $id = (int)($_GET['id'] ?? 0);
        $tracking = $_SESSION['tracking'] ?? [];
        $verified = ($tracking['id'] ?? 0) === $id && ($tracking['until'] ?? 0) > time();
        if (!$verified) {
            if (!$user) fail('Verify your ticket or sign in.', 403);
            job($id);
        }
        $photo = query('SELECT mime, image_data FROM job_photos WHERE job_id=?', [$id])->fetch();
        if (!$photo) fail('Photo not found.', 404);
        header('Content-Type: '.$photo['mime']);
        echo $photo['image_data'];
        exit;
    }
    $body=[];
    if ($method==='POST') {
        if (!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??'')) fail('Session expired. Refresh and try again.',403);
        $multipart = $action === 'create-job' && str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');
        if ($multipart) admin();
        $limit = $multipart ? 2 * 1024 * 1024 + 65536 : 65536;
        if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > $limit) fail('Request too large.',413);
        $json = $multipart ? ($_POST['job'] ?? '') : file_get_contents('php://input');
        if (!is_string($json) || strlen($json) > 65536) fail('Invalid job details.');
        $body=json_decode($json,true,32,JSON_THROW_ON_ERROR);
        if (!is_array($body)) fail('Invalid request.');
    }
    if ($method==='POST' && $action==='request-staff-reset') request_staff_reset($body, $config['smtp'] ?? []);
    if ($method==='POST' && $action==='reset-staff-password') reset_staff_password($body);
    if ($action==='login' && $method==='POST') {
        limited('login',20);
        $account=query('SELECT * FROM users WHERE email=? AND active=1',[email($body['email']??'')])->fetch();
        if (!$account || !verify_password(text($body['password']??'',200),$account['password_hash'])) fail('Email or password is incorrect.',401);
        if (str_starts_with($account['password_hash'],'pbkdf2$')) query('UPDATE users SET password_hash=? WHERE id=?',[password_hash($body['password'],PASSWORD_DEFAULT),$account['id']]);
        forget_device();
        if (($body['remember']??false)===true) remember_device((int)$account['id']);
        session_regenerate_id(true); $_SESSION['uid']=$account['id']; unset($_SESSION['tracking']);
        reply(['ok'=>true]);
    }
    if ($action==='logout' && $method==='POST') { forget_device(); $_SESSION=[]; session_destroy(); reply(['ok'=>true]); }
    if ($action==='request-otp' && $method==='POST') request_tracking_otp($body,$config['smtp']??[]);
    if ($action==='track' && $method==='POST') verify_tracking_otp($body);
    if ($action==='shop-contact' && $method==='GET') {
        $address=query("SELECT value FROM settings WHERE field='shop-email'")->fetchColumn();
        reply(['email'=>filter_var($address,FILTER_VALIDATE_EMAIL) ? $address : null]);
    }
    if ($action==='positions' && $method==='GET') reply(query('SELECT id,title,description FROM positions WHERE is_open=1 ORDER BY id DESC')->fetchAll());
    if ($action==='customer-action' && $method==='POST') {
        limited('customer-actions',40);
        $access=$_SESSION['tracking']??[];
        if (($access['until']??0)<time()) fail('Verify your tracking details again.',401);
        $db->beginTransaction();
        $j=query('SELECT * FROM jobs WHERE id=? FOR UPDATE',[$access['id']])->fetch();
        if (!$j) fail('Job not found.',404);
        if ((int)($body['version']??0)!==(int)$j['version']) fail('Job changed. Look it up again before continuing.',409);
        $kind=$body['kind']??'';
        $user=false;
        if (in_array($kind,['approve','decline'],true)) {
            if ($j['status']!=='Awaiting Quote Approval') fail('No quote awaiting approval.',409);
            $status=$kind==='approve'?'Repairing':'Declined';
            query('UPDATE jobs SET status=?,quote_decision=?,version=version+1 WHERE id=?',[$status,$kind,$j['id']]);
            event((int)$j['id'],'Customer '.$kind.'d quote');
        } elseif (in_array($kind,['message','cancellation','warranty'],true)) {
            if ($kind==='cancellation' && !shape($j,true)['canCancel']) fail('Cancellation is unavailable at this stage.',409);
            if ($kind==='warranty' && ($j['status']!=='Collected' || !$j['warranty_until'] || $j['warranty_until']<gmdate('Y-m-d'))) fail('No active warranty.',409);
            query('INSERT INTO customer_requests(job_id,kind,message) VALUES(?,?,?)',[$j['id'],$kind,text($body['message']??'',4000)]);
            event((int)$j['id'],'Customer request: '.$kind);
        } else fail('Unknown request.');
        $db->commit(); reply(shape(query('SELECT * FROM jobs WHERE id=?',[$j['id']])->fetch(),true));
    }
    if (!$user) fail('Sign in required.',401);
    if ($action==='bootstrap' && $method==='GET') {
        $jobs=query('SELECT * FROM jobs'.($user['role']==='technician'?' WHERE technician_id=?':'').' ORDER BY id DESC',$user['role']==='technician'?[$user['id']]:[])->fetchAll();
        $techs=query("SELECT id,name,email,phone,specialty,IF(active=1,'Active','Deactivated') AS status FROM users WHERE role='technician'".($user['role']==='technician'?' AND id=?':''),$user['role']==='technician'?[$user['id']]:[])->fetchAll();
        foreach($techs as &$t) { $t['activeJobs']=(int)query("SELECT COUNT(*) FROM jobs WHERE technician_id=? AND status NOT IN ('Collected','Cancelled','Declined','Cannot Be Repaired')",[$t['id']])->fetchColumn(); $t['completedJobs']=(int)query("SELECT COUNT(*) FROM jobs WHERE technician_id=? AND status='Collected'",[$t['id']])->fetchColumn(); $t['id']='TECH-'.$t['id']; } unset($t);
        reply(['user'=>$user,'jobs'=>array_map('shape',$jobs),'technicians'=>$techs,'settings'=> $user['role']==='admin'?query('SELECT field,value FROM settings')->fetchAll():[]]);
    }
    if ($action==='reports' && $method==='GET') {
        admin();
        reply(['months'=>query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,COUNT(*) AS intake,SUM(status='Collected') AS completed,SUM(quote_decision='approve') AS approved,SUM(quote_decision IS NOT NULL) AS decided,SUM(diagnostic_fee) AS diagnostic FROM jobs GROUP BY month ORDER BY month DESC")->fetchAll(),'payments'=>query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,SUM(amount) AS revenue FROM payments GROUP BY month ORDER BY month DESC")->fetchAll()]);
    }
    if ($method!=='POST') fail('Unknown endpoint.',404);
    if ($action==='create-job') {
        admin(); $photo=intake_photo(); $c=$body['customer']??[]; $mail=email($c['email']??''); $name=text($c['name']??''); $phone=text($c['phone']??'',40);
        if (($body['consent']??false)!==true) fail('Customer consent must be confirmed.');
        $db->beginTransaction();
        $tech=query("SELECT id FROM users WHERE name=? AND role='technician' AND active=1 FOR UPDATE",[text($body['technician']??'')])->fetchColumn(); if (!$tech) fail('Select an active technician.');
        $values=[]; foreach(['appliance','brand','model','serial','reportedIssue','accessories','condition'] as $f) $values[]=text($body[$f]??'', $f==='reportedIssue'?4000:255, in_array($f,['appliance','brand','model','reportedIssue'],true));
        query('INSERT INTO customers(name,email,phone,consent_at) VALUES(?,?,?,UTC_TIMESTAMP())',[$name,$mail,$phone]); $cid=$db->lastInsertId();
        $alphabet='23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        for($attempt=0;$attempt<10;$attempt++) {
            $code=''; for($i=0;$i<8;$i++) $code.=$alphabet[random_int(0,strlen($alphabet)-1)];
            try { query('INSERT INTO jobs(customer_id,technician_id,tracking_code,appliance,brand,model,serial,reported_issue,accessories,item_condition) VALUES(?,?,?,?,?,?,?,?,?,?)',array_merge([$cid,$tech,$code],$values)); break; }
            catch(PDOException $e) { if (($e->errorInfo[1]??0)!==1062 || $attempt===9) throw $e; }
        }
        $id=(int)$db->lastInsertId();
        if ($photo !== null) query('INSERT INTO job_photos(job_id,mime,image_data) VALUES(?,?,?)', [$id,$photo[0],$photo[1]]);
        event($id,'Job created; customer consent recorded'); $notice=queue_job_email($id,'Your repair ticket has been created.'); $db->commit(); deliver_job_email($notice,$config); reply(shape(job($id)),201);
    }
    if ($action==='update-job') {
        $db->beginTransaction(); $j=job((int)($body['id']??0),true);
        if ((int)($body['version']??0)!==(int)$j['version']) fail('Job changed. Refresh before saving.',409);
        $op=$body['operation']??'';
        if ($op==='note') { query('INSERT INTO job_notes(job_id,author,text,customer_visible) VALUES(?,?,?,?)',[$j['id'],$user['name'],text($body['text']??'',4000),($body['customerVisible']??false)===true?1:0]); event((int)$j['id'],'Progress note added'); }
        elseif ($op==='quote') {
            if (in_array($j['status'],['Collected','Cancelled','Declined','Cannot Be Repaired'],true)) fail('Closed jobs cannot be quoted.',409);
            $parts = money($body['parts'] ?? 0);
            $itemsJson = $parts === money($j['parts']) ? ($j['quote_parts_json'] ?? null) : null;
            if (array_key_exists('partItems', $body)) {
                if (!array_key_exists('quote_parts_json', $j)) fail('Install the quote parts database migration before saving itemized quotes.', 409);
                if (!is_array($body['partItems']) || !array_is_list($body['partItems'])) fail('Invalid parts list.');
                $items = [];
                $cents = 0;
                foreach ($body['partItems'] as $item) {
                    if (!is_array($item)) fail('Invalid replacement part.');
                    $name = trim(is_string($item['name'] ?? null) ? $item['name'] : '');
                    if ($name === '' || !preg_match('/^.{1,120}$/usD', $name)) fail('Enter a part name of up to 120 characters.');
                    $fee = money($item['fee'] ?? null);
                    $cents += (int)str_replace('.', '', $fee);
                    $items[] = ['name' => $name, 'fee' => $fee];
                }
                $parts = money($cents / 100);
                $itemsJson = json_encode($items, JSON_THROW_ON_ERROR);
            }
            query("UPDATE jobs SET diagnostic_fee=?,parts=?,labor=?,quote_decision=NULL,status='Awaiting Quote Approval' WHERE id=?",
                [money($body['diagnosticFee'] ?? $j['diagnostic_fee']), $parts, money($body['labor'] ?? 0), $j['id']]);
            if (array_key_exists('quote_parts_json', $j)) {
                query('UPDATE jobs SET quote_parts_json=? WHERE id=?', [$itemsJson, $j['id']]);
            }
            event((int)$j['id'], 'Quote revised; customer approval required');
        } elseif ($op==='payment') {
            admin(); if (!shape($j)['canPay']) fail('Payment is unavailable at this stage.',409);
            $due=round((float)$j['diagnostic_fee']+(float)$j['parts']+(float)$j['labor']-(float)query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE job_id=?',[$j['id']])->fetchColumn(),2);
            if ($due<=0) fail('Already paid.',409);
            query('INSERT INTO payments(job_id,amount,recorded_by) VALUES(?,?,?)',[$j['id'],money($due),$user['id']]); event((int)$j['id'],'Balance paid: PHP '.money($due));
        } elseif ($op==='pickup') {
            admin(); if (!shape($j)['canPickUp']) fail('Pickup requires a paid item that is ready or cannot be repaired.',409);
            if ($j['status']==='Ready') query("UPDATE jobs SET status='Collected',warranty_until=DATE_ADD(UTC_DATE(),INTERVAL 90 DAY) WHERE id=?",[$j['id']]);
            query('UPDATE jobs SET picked_up_at=UTC_TIMESTAMP() WHERE id=?',[$j['id']]);
            event((int)$j['id'],'Customer collected item at counter');
        } elseif ($op==='status') {
            $s=text($body['status']??'',50); $flow=['Received','Diagnosing','Awaiting Quote Approval','Repairing','Waiting for Parts','Ready','Collected'];
            if (!in_array($s,array_merge($flow,['Declined','Cancelled','Cannot Be Repaired']),true)) fail('Invalid status.');
            if ($s==='Collected' || $j['picked_up_at']) fail('Use counter pickup to collect an item. Collected items are closed.',409);
            if ($user['role']==='technician') {
                $from=array_search($j['status'],$flow,true); $to=array_search($s,$flow,true);
                if ($s==='Collected' || $from===false || $to===false || abs($to-$from)>1 || ($s==='Repairing' && $j['quote_decision']!=='approve')) fail('This transition requires administrator access or quote approval.',403);
            }
            $warranty=$j['warranty_until']; if ($s==='Collected') { admin(); if ($j['status']!=='Collected') $warranty=gmdate('Y-m-d',time()+90*86400); }
            query('UPDATE jobs SET status=?,warranty_until=? WHERE id=?',[$s,$warranty,$j['id']]); event((int)$j['id'],'Status: '.$s.'; '.text($body['reason']??'Staff update',1000));
        } else fail('Unknown operation.');
        query('UPDATE jobs SET version=version+1 WHERE id=?',[$j['id']]);
        $messages=['quote'=>'Your repair quotation is ready for review.','payment'=>'Your payment has been recorded.','pickup'=>'Your item was collected.','status'=>'Your repair status is now '.($s??'').'.'];
        $notice=isset($messages[$op])?queue_job_email((int)$j['id'],$messages[$op]):null;
        $db->commit(); if ($notice) deliver_job_email($notice,$config); reply(shape(job((int)$j['id'])));
    }
    if ($action==='retry-emails') {
        admin(); $j=job((int)($body['id']??0));
        $pending=query('SELECT id FROM job_notifications WHERE job_id=? AND sent_at IS NULL ORDER BY id LIMIT 10',[$j['id']])->fetchAll();
        foreach($pending as $item) if(!deliver_job_email((int)$item['id'],$config)) fail('Email delivery failed. Check SMTP settings and retry.',503);
        reply(['ok'=>true]);
    }
    if ($action==='reassign') {
        admin(); $db->beginTransaction();
        $tech=query("SELECT id FROM users WHERE id=? AND active=1 AND role='technician' FOR UPDATE",[(int)($body['technicianId']??0)])->fetchColumn();
        if(!$tech) fail('Choose an active technician.');
        $j=job((int)($body['id']??0),true);
        if((int)($body['version']??0)!==(int)$j['version']) fail('Job changed. Refresh first.',409);
        query('UPDATE jobs SET technician_id=?,version=version+1 WHERE id=?',[$tech,$j['id']]); event((int)$j['id'],'Technician assignment changed');$db->commit();reply(shape(job((int)$j['id'])));
    }
    if ($action==='manage-position') {
        admin();
        if (isset($body['closeId'])) query('UPDATE positions SET is_open=0 WHERE id=?',[(int)$body['closeId']]);
        else query('INSERT INTO positions(title,description) VALUES(?,?)',[text($body['title']??'',120),text($body['description']??'',4000)]);
        reply(['ok'=>true]);
    }
    if ($action==='staff') {
        admin();
        if (isset($body['id'])) {
            $db->beginTransaction(); $id=(int)$body['id']; query('SELECT id FROM users WHERE id=? FOR UPDATE',[$id]);
            $active=($body['active']??false)===true;
            if (!$active && (int)query("SELECT COUNT(*) FROM jobs WHERE technician_id=? AND status NOT IN ('Collected','Cancelled','Declined','Cannot Be Repaired')",[$id])->fetchColumn()>0) fail('Reassign open jobs before deactivation.',409);
            query("UPDATE users SET active=? WHERE id=? AND role='technician'",[$active?1:0,$id]); if(!$active) query('DELETE FROM saved_logins WHERE user_id=?',[$id]); $db->commit();
        } else {
            $password=text($body['password']??'',200); if(strlen($password)<8) fail('Use at least 8 password characters.');
            query("INSERT INTO users(name,email,password_hash,role,phone,specialty) VALUES(?,?,?,'technician',?,?)",[text($body['name']??''),email($body['email']??''),password_hash($password,PASSWORD_DEFAULT),text($body['phone']??'',40,false),text($body['specialty']??'',255,false)]);
        } reply(['ok'=>true]);
    }
    if ($action==='settings') {
        admin(); $allowed=['shop-name','shop-email','shop-phone','shop-address']; $db->beginTransaction();
        foreach($body as $field=>$value) { if(!in_array($field,$allowed,true)) fail('Unsupported setting.'); query('INSERT INTO settings(field,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',[$field,text($value,1000,false)]); }
        $db->commit(); reply(['ok'=>true]);
    }
    if ($action==='requests') { admin(); reply(query('SELECT r.*,j.tracking_code FROM customer_requests r JOIN jobs j ON j.id=r.job_id ORDER BY r.id DESC')->fetchAll()); }
    if ($action==='password') { $pass=text($body['password']??'',200); if(strlen($pass)<8) fail('Use at least 8 characters.'); $hash=query('SELECT password_hash FROM users WHERE id=?',[$user['id']])->fetchColumn(); if(!verify_password(text($body['current']??'',200),$hash)) fail('Current password is incorrect.',403); query('UPDATE users SET password_hash=? WHERE id=?',[password_hash($pass,PASSWORD_DEFAULT),$user['id']]); query('DELETE FROM saved_logins WHERE user_id=?',[$user['id']]); forget_device(); session_regenerate_id(true); reply(['ok'=>true]); }
    fail('Unknown endpoint.',404);
} catch (JsonException $e) { fail('Invalid JSON request.',400); }
catch (Throwable $e) { if(isset($db) && $db->inTransaction()) $db->rollBack(); error_log('RepairDesk: '.$e->getMessage()); fail('Request failed. Check your input or contact the administrator.',500); }
