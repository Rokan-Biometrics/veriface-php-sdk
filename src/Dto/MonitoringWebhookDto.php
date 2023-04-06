<?php

namespace veriface\Dto;

class MonitoringWebhookDto extends VerificationIdentifiersDto
{
    public ?string $service;
    public ?string $status;
}
