<?php

namespace API\Modules\DSC\Enums;

use ArchTech\Enums\Values;

enum AuthorizationRegistryImplementationName: string
{
    use Values;

    case Keyrock = "keyrock";
}
