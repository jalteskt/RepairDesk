<?php
declare(strict_types=1);

function format_ticket_email(PHPMailer\PHPMailer\PHPMailer $mailer, string $message, string $url): void {
    require_once __DIR__.'/lib/qr-autoload.php';
    $matrix = \BaconQrCode\Encoder\Encoder::encode(
        $url,
        \BaconQrCode\Common\ErrorCorrectionLevel::M()
    )->getMatrix();
    $size = $matrix->getWidth();

    // Six pixels per module and a white border four modules wide.
    $scale = 6;
    $width = ($size + 8) * $scale;
    $margin = str_repeat("\xff", 4 * $scale);
    $border = str_repeat("\0".str_repeat("\xff", $width), 4 * $scale);
    $pixels = $border;
    foreach ($matrix->getArray() as $row) {
        $scanline = "\0".$margin;
        foreach ($row as $dark) {
            $scanline .= str_repeat($dark ? "\0" : "\xff", $scale);
        }
        $pixels .= str_repeat($scanline.$margin, $scale);
    }
    $chunk = static fn(string $kind, string $data): string =>
        pack('N', strlen($data)).$kind.$data.pack('N', crc32($kind.$data));
    $png = "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $width, $width, 8, 0, 0, 0, 0))
        .$chunk('IDAT', gzcompress($pixels.$border)).$chunk('IEND', '');

    $mailer->CharSet = 'UTF-8';
    $mailer->addStringEmbeddedImage($png, 'repair-ticket-qr', 'ticket.png', 'base64', 'image/png');
    $mailer->isHTML(true);
    $mailer->Body = '<p>'.nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')).'</p>'
        .'<p><img src="cid:repair-ticket-qr" alt="Scan to track your repair"></p>'
        .'<p><a href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'">Track repair</a></p>';
    $mailer->AltBody = $message."\nTrack repair: ".$url;
}
