<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Database\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use SchoolPalm\MessageDelivery\Contracts\NotificationContext;

final class CurrentContextNotificationScope implements Scope
{
    /**
     * Apply the current notification context to the query.
     */
    public function apply(
        Builder $builder,
        Model $model
    ): void {
        /*
         * If the host application has not registered a
         * NotificationContext provider, MessageDelivery can
         * still operate without context scoping.
         */
        if (! app()->bound(NotificationContext::class)) {
            return;
        }

        $contextId = app(NotificationContext::class)
            ->notificationContext();

        $column = $model->qualifyColumn('context_id');

        /*
         * No active context means general notifications.
         */
        if ($contextId === null) {
            $builder->whereNull($column);

            return;
        }

        /*
         * Active context means only notifications belonging
         * to that context are returned.
         */
        $builder->where(
            $column,
            (string) $contextId
        );
    }
}