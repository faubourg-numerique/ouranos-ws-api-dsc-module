<?php

namespace API\Modules\DSC\Enums;

use ArchTech\Enums\Values;

enum TrustedIssuersListImplementationName: string
{
    use Values;

    case FiwareTrustedIssuersList = "fiware-trusted-issuers-list";
}
