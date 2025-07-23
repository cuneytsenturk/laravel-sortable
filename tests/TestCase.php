<?php

namespace Akaunting\Sortable\Tests;

use Akaunting\Sortable\Provider;
use Akaunting\Sortable\Tests\Models\Comment;
use Akaunting\Sortable\Tests\Models\Post;
use Akaunting\Sortable\Tests\Models\Profile;
use Akaunting\Sortable\Tests\Models\User;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected User $user;

    protected Profile $profile;

    protected Post $post;

    protected Comment $comment;

    protected string $direction = 'asc';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();

        $this->setUpModels();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            Provider::class,
        ];
    }

    protected function setUpDatabase(): void
    {
        config(['database.default' => 'testbench']);

        config(['database.connections.testbench' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
        ]);
    }

    protected function setUpModels(): void
    {
        $this->user    = new User();
        $this->profile = new Profile();
        $this->post    = new Post();
        $this->comment = new Comment();
    }

    public function getNextClosure(): \Closure
    {
        return function (): string {
            return 'next';
        };
    }
}
