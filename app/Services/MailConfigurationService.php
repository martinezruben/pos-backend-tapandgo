<?php

namespace App\Services;

use App\Models\SystemParameter;
use Exception;
use Swift_Mailer;
use Swift_SmtpTransport;

class MailConfigurationService
{
    protected SystemParameter $settings;

    public function __construct()
    {
        $this->settings = SystemParameter::current();
    }

    public function getActiveDriver(): string
    {
        return $this->settings->mail_driver ?? 'smtp';
    }

    public function getMailFromAddress(): ?string
    {
        return $this->settings->mail_from_address;
    }

    public function getMailFromName(): ?string
    {
        return $this->settings->mail_from_name;
    }

    public function getSmtpConfig(): array
    {
        return [
            'host' => $this->settings->smtp_host,
            'port' => $this->settings->smtp_port,
            'username' => $this->settings->smtp_username,
            'password' => $this->settings->smtp_password,
            'encryption' => $this->settings->smtp_encryption,
        ];
    }

    public function getOffice365Config(): array
    {
        return [
            'tenant_id' => $this->settings->office365_tenant_id,
            'client_id' => $this->settings->office365_client_id,
            'client_secret' => $this->settings->office365_client_secret,
            'scopes' => $this->settings->office365_scopes ?? [],
        ];
    }

    public function validateSmtpConnection(): array
    {
        try {
            $config = $this->getSmtpConfig();

            if (! $config['host'] || ! $config['port'] || ! $config['username'] || ! $config['password']) {
                return ['success' => false, 'message' => 'Credenciales SMTP incompletas'];
            }

            $transport = new Swift_SmtpTransport($config['host'], $config['port'], $config['encryption']);
            $transport->setUsername($config['username']);
            $transport->setPassword($config['password']);
            $transport->setStreamOptions(['ssl' => ['allow_self_signed' => true]]);

            $mailer = new Swift_Mailer($transport);
            $mailer->getTransport()->start();
            $mailer->getTransport()->stop();

            return ['success' => true, 'message' => 'Conexión SMTP válida'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error de conexión SMTP: '.$e->getMessage()];
        }
    }

    public function validateOffice365Connection(): array
    {
        try {
            $config = $this->getOffice365Config();

            if (! $config['tenant_id'] || ! $config['client_id'] || ! $config['client_secret']) {
                return ['success' => false, 'message' => 'Credenciales Office 365 incompletas'];
            }

            // In a real implementation, you would call Microsoft Graph API
            // to validate the token can be obtained. For now, just validate fields.
            return ['success' => true, 'message' => 'Credenciales Office 365 válidas (validación básica)'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error de validación Office 365: '.$e->getMessage()];
        }
    }

    public function testEmail(string $toAddress): array
    {
        try {
            $driver = $this->getActiveDriver();

            if ($driver === 'smtp') {
                return $this->testSmtpEmail($toAddress);
            } elseif ($driver === '365') {
                return $this->testOffice365Email($toAddress);
            }

            return ['success' => false, 'message' => 'Driver de correo no soportado'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al enviar correo de prueba: '.$e->getMessage()];
        }
    }

    private function testSmtpEmail(string $toAddress): array
    {
        try {
            $config = $this->getSmtpConfig();

            if (! $config['host'] || ! $config['port'] || ! $config['username'] || ! $config['password']) {
                return ['success' => false, 'message' => 'Configuración SMTP incompleta'];
            }

            $transport = new Swift_SmtpTransport($config['host'], $config['port'], $config['encryption']);
            $transport->setUsername($config['username']);
            $transport->setPassword($config['password']);
            $transport->setStreamOptions(['ssl' => ['allow_self_signed' => true]]);

            $mailer = new Swift_Mailer($transport);

            $message = (new \Swift_Message)
                ->setSubject('Prueba de conexión - Tap&Go')
                ->setFrom($config['username'], $this->getMailFromName())
                ->setTo($toAddress)
                ->setBody('Este es un correo de prueba de la configuración SMTP de Tap&Go.', 'text/plain');

            $mailer->send($message);

            return ['success' => true, 'message' => "Correo de prueba enviado a {$toAddress}"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al enviar correo SMTP: '.$e->getMessage()];
        }
    }

    private function testOffice365Email(string $toAddress): array
    {
        // This would require proper Office 365 / Microsoft Graph integration
        // For now, return a placeholder response
        return [
            'success' => false,
            'message' => 'Prueba de Office 365 requiere configuración adicional de Microsoft Graph SDK',
        ];
    }
}
