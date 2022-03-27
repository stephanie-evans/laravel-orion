<?php

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

use Orion\Contracts\QueryBuilder;
use Orion\Http\Controllers\BaseController;
use Orion\Tests\Fixtures\App\Models\Post;

class BaseControllerStubWithoutComponents extends BaseController
{
    /**
     * @var string $tag
     */
    protected $model = Post::class;

    /**
     * @inheritDoc
     */
    public function resolveResourceModelClass(): string
    {
        return $this->getModel();
    }

    public function getResourceQueryBuilder(): QueryBuilder
    {
        return $this->getQueryBuilder();
    }
}
