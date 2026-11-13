<?php
declare(strict_types=1);

function request_staff_reset(array $body, array $smtp): void {
    $address = email($body['email'] ?? '');
    limited('staff-reset-request', 6);
    $prior = $_SESSION['staff_reset'] ?? [];
    if (($prior['email'] ?? '') === $address && time() < $prior['issued'] + 60) {
        reply(['ok' => true]);
    }
    unset($_SESSION['staff_reset']);
    $account = query('SELECT id,password_hash FROM users WHERE email=? AND active=1', [$address])->fetch();
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['staff_reset'] = [
        'email' => $address, 'issued' => time(), 'attempts' => 0,
        'digest' => password_hash($code, PASSWORD_DEFAULT),
        'id' => $account['id'] ?? 0, 'original' => $account['password_hash'] ?? '',
    ];
    if ($account) {
        try {
            send_shop_mail($address, 'Reset your RepairDesk staff password',
                "Your password reset code is $code. Enter it in the same browser within ten minutes. If you did not request this, you can ignore this email.", $smtp);
        } catch (Throwable $e) {
            unset($_SESSION['staff_reset']);
            error_log('RepairDesk staff recovery email delivery failed.');
        }
    }
    reply(['ok' => true]);
}

function reset_staff_password(array $body): void {
    global $db;
    limited('staff-reset-verify', 20);
    $password = text($body['password'] ?? '', 72);
    if (strlen($password) < 8) fail('Use at least eight characters for your new password.');
    $code = text($body['code'] ?? '', 6);
    $challenge = $_SESSION['staff_reset'] ?? [];
    if (!$challenge || time() >= $challenge['issued'] + 600 || $challenge['attempts'] >= 5) {
        unset($_SESSION['staff_reset']);
        fail('Request a new password reset code.', 403);
    }
    $_SESSION['staff_reset']['attempts']++;
    if (!password_verify($code, $challenge['digest']) || !$challenge['id']) {
        fail('Incorrect password reset code.', 403);
    }
    unset($_SESSION['staff_reset']);
    $db->beginTransaction();
    $updated = query('UPDATE users SET password_hash=? WHERE id=? AND active=1 AND email=? AND BINARY password_hash=?',
        [password_hash($password, PASSWORD_DEFAULT), $challenge['id'], $challenge['email'], $challenge['original']]);
    if (!$updated->rowCount()) {
        $db->rollBack();
        fail('Request a new password reset code.', 403);
    }
    query('DELETE FROM saved_logins WHERE user_id=?', [$challenge['id']]);
    $db->commit();
    reply(['ok' => true]);
}
