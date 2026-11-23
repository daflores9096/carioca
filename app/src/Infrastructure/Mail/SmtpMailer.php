<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use RuntimeException;

/**
 * Cliente SMTP mínimo (AUTH LOGIN, STARTTLS/SSL). Sin dependencias externas.
 */
final class SmtpMailer
{
    /**
     * @param array{
     *   host:string,
     *   port:int,
     *   username:string,
     *   password:string,
     *   encryption:string,
     *   from_address:string,
     *   from_name:string
     * } $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function isConfigured(): bool
    {
        return ($this->config['host'] ?? '') !== ''
            && ($this->config['from_address'] ?? '') !== '';
    }

    public function send(string $to, string $subject, string $textBody): void
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('El envío de correo no está configurado.', 503);
        }

        $host = $this->config['host'];
        $port = (int) $this->config['port'];
        $encryption = strtolower((string) $this->config['encryption']);
        $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;

        $socket = @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            20,
            STREAM_CLIENT_CONNECT
        );
        if ($socket === false) {
            throw new RuntimeException("No se pudo conectar al SMTP ({$errno}): {$errstr}", 503);
        }

        stream_set_timeout($socket, 20);

        try {
            $this->expect($socket, 220);
            $this->command($socket, 'EHLO carioca');
            $this->expect($socket, 250);

            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS');
                $this->expect($socket, 220);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('No se pudo iniciar STARTTLS.', 503);
                }
                $this->command($socket, 'EHLO carioca');
                $this->expect($socket, 250);
            }

            $user = (string) ($this->config['username'] ?? '');
            $pass = (string) ($this->config['password'] ?? '');
            if ($user !== '') {
                $this->command($socket, 'AUTH LOGIN');
                $this->expect($socket, 334);
                $this->command($socket, base64_encode($user));
                $this->expect($socket, 334);
                $this->command($socket, base64_encode($pass));
                $this->expect($socket, 235);
            }

            $from = (string) $this->config['from_address'];
            $fromName = (string) ($this->config['from_name'] ?? 'CARIOCA');
            $this->command($socket, 'MAIL FROM:<' . $from . '>');
            $this->expect($socket, 250);
            $this->command($socket, 'RCPT TO:<' . $to . '>');
            $this->expect($socket, 250);
            $this->command($socket, 'DATA');
            $this->expect($socket, 354);

            $headers = [
                'Date: ' . date('r'),
                'From: ' . $this->encodeAddress($fromName, $from),
                'To: <' . $to . '>',
                'Subject: ' . $this->encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];

            $body = preg_replace("/\r\n|\r|\n/", "\r\n", $textBody) ?? $textBody;
            $body = str_replace("\r\n.", "\r\n..", $body);

            fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n");
            $this->expect($socket, 250);
            $this->command($socket, 'QUIT');
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket */
    private function command($socket, string $line): void
    {
        fwrite($socket, $line . "\r\n");
    }

    /** @param resource $socket */
    private function expect($socket, int $code): void
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $got = (int) substr($response, 0, 3);
        if ($got !== $code) {
            throw new RuntimeException(
                "Respuesta SMTP inesperada (esperado {$code}): " . trim($response),
                503
            );
        }
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function encodeAddress(string $name, string $email): string
    {
        $name = trim($name);
        if ($name === '') {
            return '<' . $email . '>';
        }
        return $this->encodeHeader($name) . ' <' . $email . '>';
    }
}
