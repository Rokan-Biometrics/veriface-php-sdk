<?php

namespace veriface\Dto;

class ExtendedReferenceDto
{
    /**
     * @var ?string may be one of CUSTOMER_ID, ORDER_ID, EMAIL, GENERAL, PRIMARY (primary can be just one in the list)
     */
    public ?string $type;
    public ?string $value;

    /**
     * @param $type string may be one of CUSTOMER_ID, ORDER_ID, EMAIL, GENERAL, PRIMARY (primary can be used just once)
     * @param $value
     */
    public function __construct(string $type, string $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

}
