<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $user;

    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function build()
    {
        $verificationUrl = config('app.frontend_url', url('/')) . "/email/verify?token={$this->token}";
        // If you want API endpoint directly:
        // $verificationUrl = url('/api/email/verify?token=' . $this->token);

        return $this->subject('Verify your email')
                    ->markdown('emails.verify')
                    ->with(['user' => $this->user, 'verificationUrl' => $verificationUrl]);
    }
}
