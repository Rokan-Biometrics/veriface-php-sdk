<?php

namespace veriface\Dto;

class VerificationIdentifiersDto
{
    public string $sessionId;
    public ?string $referenceId;

    /**
     * @var ExtendedReferenceDto[] | null
     */
    public ?array $extendedReferences;
}
