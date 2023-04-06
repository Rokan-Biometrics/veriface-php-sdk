<?php

namespace veriface\Dto;

class MonitoringDataDto
{
    public ?string $service;
    public ?string $status;

    public ?\DateTime $created;
    public ?\DateTime $updated;

    public ?\DateTime $validUntil;

    public ?string $previousCode;
    public ?string $previousLocalizedLabel;

    public ?string $code;
    public ?string $localizedLabel;
}
