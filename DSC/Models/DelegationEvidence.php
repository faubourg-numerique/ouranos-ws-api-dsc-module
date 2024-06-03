<?php

namespace API\Modules\DSC\Models;

use Core\Model;

class DelegationEvidence extends Model
{
    public array $delegationEvidence;

    public function __construct(array $data = null)
    {
        if (!is_null($data)) {
            $this->delegationEvidence = $data["delegationEvidence"];
        }
    }
}
