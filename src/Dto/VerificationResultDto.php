<?php

namespace veriface\Dto;


class VerificationResultDto extends VerificationListDto
{
    public ?\DateTime $userStarted;
    public ?\DateTime $userFinished;
    public ?string $name;
    public ?string $birthDate;
    public ?string $documentNumber;
    public ?string $personalNumber;
    public ?string $documentCountry;
    public ?string $documentType;
    public ?string $summaryStatus;
    public ?string $documentStatus;
    public ?string $selfieStatus;
    public ?string $livenessCheckStatus;
    public ?string $amlStatus;
    public ?string $monitoringStatus;
    public ?bool $stabilizedResult;

    /**
     * @var \veriface\Dto\ExtractedDataDto[]
     */
    public array $extractedData;

    public ?bool $waitingAction;
    public ?bool $waitingActionConfirmed;
    public ?bool $waitingManualResult;
    public ?bool $incorrectResultReported;

    /**
     * @var IndicatorDto[]
     */
    public array $indicators;
}
