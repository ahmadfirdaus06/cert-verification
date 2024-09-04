<?php

namespace App;

enum VerificationResults
{
    CONST VERIFIED = 'verified';
    CONST INVALID_RECIPIENT = 'invalid_recipient';
    CONST INVALID_ISSUER = 'invalid_issuer';
    CONST INVALID_SIGNATURE = 'invalid_signature';
}
