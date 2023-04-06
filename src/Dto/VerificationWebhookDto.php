<?php

namespace veriface\Dto;

class VerificationWebhookDto extends VerificationIdentifiersDto
{
    public ?string $status;
    public ?string $verificationEndUserStatus;
}
