<?php

namespace Orion\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\CollectionResource;

/**
 * Trait HandlesStandardBatchOperations
 * @package Orion\Concerns
 */
trait HandlesStandardBatchOperations
{
    /**
     * Creates a batch of new resources in a transaction-safe way.
     *
     * @param Request $request
     * @return CollectionResource
     */
    public function batchStore(Request $request)
    {
        try {
            $this->startTransaction();
            $result = $this->batchStoreWithTransaction($request);
            $this->commitTransaction();
            return $result;
        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Creates a batch of new resources.
     *
     * @param Request $request
     * @return CollectionResource
     */
    protected function batchStoreWithTransaction(Request $request)
    {
        $beforeHookResult = $this->beforeBatchStore($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $resourceModelClass = $this->resolveResourceModelClass();

        $this->authorize($this->resolveAbility('create'), $resourceModelClass);

        $resources = $this->retrieve($request, 'resources', []);
        $entities = collect([]);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        foreach ($resources as $resource) {
            /**
             * @var Model $entity
             */
            $entity = new $resourceModelClass;

            $this->beforeStore($request, $entity);
            $this->beforeSave($request, $entity);

            $this->performStore($request, $entity, $resource);

            $this->beforeStoreFresh($request, $entity);

            $entityQuery = $this->buildStoreFetchQuery($request, $requestedRelations);
            $entity = $this->runStoreFetchQuery($request, $entityQuery, $entity->{$this->keyName()});

            $entity->wasRecentlyCreated = true;

            $this->afterSave($request, $entity);
            $this->afterStore($request, $entity);

            $entities->push($entity);
        }

        $afterHookResult = $this->afterBatchStore($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities, $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * The hook is executed before creating a batch of new resources.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeBatchStore(Request $request)
    {
        return null;
    }

    /**
     * The hook is executed after creating a batch of new resources.
     *
     * @param Request $request
     * @param Collection $entities
     * @return mixed
     */
    protected function afterBatchStore(Request $request, Collection $entities)
    {
        return null;
    }

    /**
     * Update a batch of resources in a transaction-safe way.
     *
     * @param Request $request
     * @return CollectionResource
     */
    public function batchUpdate(Request $request)
    {
        try {
            $this->startTransaction();
            $result = $this->batchUpdateWithTransaction($request);
            $this->commitTransaction();
            return $result;
        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Update a batch of resources.
     *
     * @param Request $request
     * @return CollectionResource
     */
    protected function batchUpdateWithTransaction(Request $request)
    {
        $beforeHookResult = $this->beforeBatchUpdate($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildBatchUpdateFetchQuery($request, $requestedRelations);
        $entities = $this->runBatchUpdateFetchQuery($request, $query);

        foreach ($entities as $entity) {
            /** @var Model $entity */
            $this->authorize($this->resolveAbility('update'), $entity);

            $this->beforeUpdate($request, $entity);
            $this->beforeSave($request, $entity);

            $this->performUpdate(
                $request,
                $entity,
                $this->retrieve($request, "resources.{$entity->{$this->keyName()}}")
            );

            $this->beforeUpdateFresh($request, $entity);

            $entity = $this->refreshUpdatedEntity(
                $request, $requestedRelations, $entity->{$this->keyName()}
            );

            $this->afterSave($request, $entity);
            $this->afterUpdate($request, $entity);
        }

        $afterHookResult = $this->afterBatchUpdate($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities, $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * The hook is executed before updating a batch of resources.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeBatchUpdate(Request $request)
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching entities in batch update method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildBatchUpdateFetchQuery(Request $request, array $requestedRelations): Builder
    {
        return $this->buildBatchFetchQuery($request, $requestedRelations);
    }

    /**
     * Builds Eloquent query for fetching entities in batch methods.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildBatchFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $resourceKeyName = $this->resolveQualifiedKeyName();
        $resourceKeys = $this->resolveResourceKeys($request);

        return $this->buildFetchQuery($request, $requestedRelations)
            ->whereIn($resourceKeyName, $resourceKeys);
    }

    /**
     * Runs the given query for fetching entities in batch update method.
     *
     * @param Request $request
     * @param Builder $query
     * @return Collection
     */
    protected function runBatchUpdateFetchQuery(Request $request, Builder $query): Collection
    {
        return $this->runBatchFetchQuery($request, $query);
    }

    /**
     * Runs the given query for fetching entities in batch methods.
     *
     * @param Request $request
     * @param Builder $query
     * @return Collection
     */
    protected function runBatchFetchQuery(Request $request, Builder $query): Collection
    {
        return $query->get();
    }

    /**
     * The hook is executed after updating a batch of resources.
     *
     * @param Request $request
     * @param Collection $entities
     * @return mixed
     */
    protected function afterBatchUpdate(Request $request, Collection $entities)
    {
        return null;
    }

    /**
     * Deletes a batch of resources in a transaction-safe way.
     *
     * @param Request $request
     * @return CollectionResource
     * @throws Exception
     */
    public function batchDestroy(Request $request)
    {
        try {
            $this->startTransaction();
            $result = $this->batchDestroyWithTransaction($request);
            $this->commitTransaction();
            return $result;
        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Deletes a batch of resources.
     *
     * @param Request $request
     * @return CollectionResource
     * @throws Exception
     */
    protected function batchDestroyWithTransaction(Request $request)
    {
        $beforeHookResult = $this->beforeBatchDestroy($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $softDeletes = $this->softDeletes($this->resolveResourceModelClass());
        $forceDeletes = $this->forceDeletes($request, $softDeletes);

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildBatchDestroyFetchQuery($request, $requestedRelations, $softDeletes);
        $entities = $this->runBatchDestroyFetchQuery($request, $query);

        foreach ($entities as $entity) {
            /**
             * @var Model $entity
             */
            $this->authorize($this->resolveAbility($forceDeletes ? 'forceDelete' : 'delete'), $entity);

            $this->beforeDestroy($request, $entity);

            if (!$forceDeletes) {
                $this->performDestroy($entity);

                if ($softDeletes) {
                    $this->beforeDestroyFresh($request, $entity);

                    $entityQuery = $this->buildDestroyFetchQuery($request, $requestedRelations, $softDeletes);
                    $entity = $this->runDestroyFetchQuery($request, $entityQuery, $entity->{$this->keyName()});
                }
            } else {
                $this->performForceDestroy($entity);
            }

            $this->afterDestroy($request, $entity);
        }

        $afterHookResult = $this->afterBatchDestroy($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities, $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * The hook is executed before deleting a batch of resources.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeBatchDestroy(Request $request)
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching entities in batch destroy method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @param bool $softDeletes
     * @return Builder
     */
    protected function buildBatchDestroyFetchQuery(Request $request, array $requestedRelations, bool $softDeletes): Builder
    {
        return $this->buildBatchFetchQuery($request, $requestedRelations)
            ->when($softDeletes, function ($query) {
                $query->withTrashed();
            });
    }

    /**
     * Runs the given query for fetching entities in batch destroy method.
     *
     * @param Request $request
     * @param Builder $query
     * @return Collection
     */
    protected function runBatchDestroyFetchQuery(Request $request, Builder $query): Collection
    {
        return $this->runBatchFetchQuery($request, $query);
    }

    /**
     * The hook is executed after deleting a batch of resources.
     *
     * @param Request $request
     * @param Collection $entities
     * @return mixed
     */
    protected function afterBatchDestroy(Request $request, Collection $entities)
    {
        return null;
    }

    /**
     * Restores a batch of resources in a transaction-safe way.
     *
     * @param Request $request
     * @return CollectionResource
     * @throws Exception
     */
    public function batchRestore(Request $request)
    {
        try {
            $this->startTransaction();
            $result = $this->batchRestoreWithTransaction($request);
            $this->commitTransaction();
            return $result;
        } catch (\Exception $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }
    }

    /**
     * Restores a batch of resources.
     *
     * @param Request $request
     * @return CollectionResource
     * @throws Exception
     */
    protected function batchRestoreWithTransaction(Request $request)
    {
        $beforeHookResult = $this->beforeBatchRestore($request);
        if ($this->hookResponds($beforeHookResult)) {
            return $beforeHookResult;
        }

        $requestedRelations = $this->relationsResolver->requestedRelations($request);

        $query = $this->buildBatchRestoreFetchQuery($request, $requestedRelations);
        $entities = $this->runBatchRestoreFetchQuery($request, $query);

        foreach ($entities as $entity) {
            /**
             * @var Model $entity
             */
            $this->authorize($this->resolveAbility('restore'), $entity);

            $this->beforeRestore($request, $entity);

            $this->performRestore($entity);

            $this->beforeRestoreFresh($request, $entity);

            $entityQuery = $this->buildRestoreFetchQuery($request, $requestedRelations);
            $entity = $this->runRestoreFetchQuery($request, $entityQuery, $entity->{$this->keyName()});

            $this->afterRestore($request, $entity);
        }

        $afterHookResult = $this->afterBatchRestore($request, $entities);
        if ($this->hookResponds($afterHookResult)) {
            return $afterHookResult;
        }

        $this->relationsResolver->guardRelationsForCollection($entities, $requestedRelations);

        return $this->collectionResponse($entities);
    }

    /**
     * The hook is executed before restoring a batch of resources.
     *
     * @param Request $request
     * @return mixed
     */
    protected function beforeBatchRestore(Request $request)
    {
        return null;
    }

    /**
     * Builds Eloquent query for fetching entities in batch restore method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildBatchRestoreFetchQuery(Request $request, array $requestedRelations): Builder
    {
        return $this->buildBatchFetchQuery($request, $requestedRelations)->withTrashed();
    }

    /**
     * Runs the given query for fetching entities in batch restore method.
     *
     * @param Request $request
     * @param Builder $query
     * @return Collection
     */
    protected function runBatchRestoreFetchQuery(Request $request, Builder $query): Collection
    {
        return $this->runBatchFetchQuery($request, $query);
    }

    /**
     * The hook is executed after restoring a batch of resources.
     *
     * @param Request $request
     * @param Collection $entities
     * @return mixed
     */
    protected function afterBatchRestore(Request $request, Collection $entities)
    {
        return null;
    }
}
