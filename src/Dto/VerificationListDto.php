<?php

namespace veriface\Dto;

class VerificationListDto extends VerificationIdentifiersDto
{
    /**
     * @var string NEW,VERIFIED,VERIFIED_WARNING,PARTIALLY_VERIFIED,CANCELLED,VALIDATION_NEEDED,ACTION_NEEDED,REFUSED,
     * ERROR,WAITING,WAITING_ENDUSER,VERIFIED_MANUAL,REFUSED_MANUAL,UNKNOWN,EXPIRED
     */
    public string $status;

    /**
     * @var string UNSTARTED,NOT_FINISHED_CHANGE_DEVICE,NOT_FINISHED,SUCCESS,FAILURE,FAILURE_NOT_RETRYABLE,
     * FAILURE_TOO_MANY_RETRIES,CANCELLED,POSTPONED,EXPIRED
     */
    public string $verificationEndUserStatus;
    public ?\DateTime $created;
    public ?\DateTime $finished;
    public ?\DateTime $deleted;

}
