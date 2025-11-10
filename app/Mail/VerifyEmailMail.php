<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function build()
    {
        $verifyUrl = url('/api/email/verify?token=' . $this->token);

        return $this->subject('Verify Your Email Address')
                    ->view('emails.verify')
                    ->with(['verifyUrl' => $verifyUrl]);
    }
}
