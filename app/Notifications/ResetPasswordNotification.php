<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

        $resetUrl = sprintf(
            '%s/reset-password?token=%s&email=%s',
            rtrim($frontendUrl, '/'),
            $this->token,
            urlencode($notifiable->getEmailForPasswordReset())
        );

        return (new MailMessage)
            ->subject('Reset Your Password — Space Hub')
            ->greeting('Hello ' . ($notifiable->first_name ?? 'there') . '!')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $resetUrl)
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.')
            ->salutation('Best regards, Space Hub Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'token' => $this->token,
        ];
    }
}