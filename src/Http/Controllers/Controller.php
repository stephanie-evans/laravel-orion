<?php

namespace Orion\Http\Controllers;

use Orion\Concerns\HandlesSyncOperations;
use Orion\Concerns\HandlesStandardBatchOperations;
use Orion\Exceptions\BindingException;

abstract class Controller extends BaseController
{
    use HandlesSyncOperations, HandlesStandardBatchOperations;

    /**
     * Controller constructor.
     *
     * @throws BindingException
     */
    public function __construct()
    {
        $this->model = $this->associate(get_class($this), 2, 0);
        $this->request = $this->associate($this->model,0,1);
        parent::__construct();
    }
    /**
     * Retrieves model related to resource.
     *
     * @return string
     */
    public function resolveResourceModelClass(): string
    {
        return $this->getModel();
    }
}
