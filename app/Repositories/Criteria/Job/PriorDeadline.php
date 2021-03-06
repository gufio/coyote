<?php

namespace Coyote\Repositories\Criteria\Job;

use Coyote\Repositories\Contracts\RepositoryInterface as Repository;
use Coyote\Repositories\Criteria\Criteria;

class PriorDeadline extends Criteria
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $model
     * @param Repository $repository
     * @return mixed
     */
    public function apply($model, Repository $repository)
    {
        return $model->priorDeadline();
    }
}
