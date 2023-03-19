<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\Post;

class PostPostMetaController extends RelationController
{
    protected $model = Post::class;

    protected $relation = 'meta';

    public function includes(): array
    {
        return ['post'];
    }

    public function aggregates(): array
    {
        return ['post'];
    }
}
