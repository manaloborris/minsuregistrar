<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    public function send(string $to, string $subject, string $body): bool
    {
        $driver = strtolower(trim((string) MAIL_DRIVER));
        if ($driver === '' || $driver === 'smtp') {
            $sent = $this->sendViaSmtp($to, $subject, $body);
            if ($sent) {
                return true;
            }

            // Keep a local trace when SMTP fails.
            $this->writeToLog($to, '[SMTP FAILED] ' . $subject, $body);
            return false;
        }

        if ($driver !== 'log') {
            $this->writeToLog($to, '[MAIL DRIVER ERROR] ' . $subject, $body . "\nError: Unsupported MAIL_DRIVER value: " . $driver);
            return false;
        }

        return $this->writeToLog($to, $subject, $body);
    }

    private function sendViaSmtp(string $to, string $subject, string $body): bool
    {
        if (SMTP_HOST === '' || MAIL_FROM_ADDRESS === '') {
            return false;
        }

        $useAuth = defined('SMTP_AUTH') ? (bool) SMTP_AUTH : true;
        if ($useAuth && (SMTP_USERNAME === '' || SMTP_PASSWORD === '')) {
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->Port = SMTP_PORT;
            $mail->SMTPAuth = $useAuth;
            if ($useAuth) {
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
            }
            $mail->Timeout = SMTP_TIMEOUT;

            if (SMTP_ENCRYPTION === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif (SMTP_ENCRYPTION === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false);

            return $mail->send();
        } catch (Exception $e) {
            $this->writeToLog($to, '[SMTP ERROR] ' . $subject, $body . "\nError: " . $e->getMessage());
            return false;
        }
    }

    private function writeToLog(string $to, string $subject, string $body): bool
    {
        $path = (string) MAIL_LOG_PATH;
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $entry = '[' . date('Y-m-d H:i:s') . ']'
            . " TO: " . $to
            . " | SUBJECT: " . $subject
            . "\n"
            . $body
            . "\n---\n";

        return file_put_contents($path, $entry, FILE_APPEND) !== false;
    }
}
