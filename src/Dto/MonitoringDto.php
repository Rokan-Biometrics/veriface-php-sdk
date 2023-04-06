<?php

namespace veriface\Dto;

class MonitoringDto
{
    public ?string $status;
    public ?string $sessionId;

    public ?string $name;
    public ?\DateTime $verificationDate;
    public ?\DateTime $monitoringStart;
    public ?\DateTime $monitoringEnd;
    public ?\DateTime $monitoringLastChange;
    public ?string $monitoringVariant;
    public ?array $detail;
}
