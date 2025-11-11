<?php 


namespace App\Exceptions;

use Exception;

class AuthException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_type' => 'authentication_error'
        ], 500);
    }
}

// app/Exceptions/EmailVerificationException.php
namespace App\Exceptions;

use Exception;

class EmailVerificationException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_type' => 'email_verification_error'
        ], 500);
    }
}