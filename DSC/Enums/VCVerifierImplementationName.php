<?php

namespace API\Modules\DSC\Enums;

use ArchTech\Enums\Values;

enum VCVerifierImplementationName: string
{
    use Values;

    case FiwareVCVerifier = "fiware-vc-verifier";
}
