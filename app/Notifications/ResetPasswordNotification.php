<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(public string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe - '.config('app.name'))
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line("Vous recevez cet e-mail car une demande de réinitialisation de mot de passe a été effectuée pour votre compte.")
            ->action('Réinitialiser le mot de passe', $url)
            ->line('Ce lien de réinitialisation expirera dans 60 minutes.')
            ->line("Si vous n'êtes pas à l'origine de cette demande, aucune action n'est requise.");
    }
}
