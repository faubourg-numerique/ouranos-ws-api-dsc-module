<?php

namespace API\Modules\DSC\Models;

use Core\Model;

class DelegationRequest extends Model
{
    public array $delegationRequest;

    public function __construct(array $data = null)
    {
        if (!is_null($data)) {
            $this->delegationRequest = $data["delegationRequest"];
        }
    }
}
