<?php

namespace Coyote\Http\Controllers\Adm;

use Coyote\Http\Grids\Adm\FlagsGrid;
use Coyote\Repositories\Contracts\FlagRepositoryInterface as FlagRepository;
use Coyote\Repositories\Criteria\FlagList;
use Boduch\Grid\Source\EloquentSource;

class FlagController extends BaseController
{
    /**
     * @var FlagRepository
     */
    protected $flag;

    /**
     * @param FlagRepository $flag
     */
    public function __construct(FlagRepository $flag)
    {
        parent::__construct();

        $this->flag = $flag;
        $this->breadcrumb->push('Raporty', route('adm.flag'));
    }

    /**
     * @inheritdoc
     */
    public function index()
    {
        $this->flag->pushCriteria(new FlagList());
        $this->flag->applyCriteria();

        $grid = $this->gridBuilder()->createGrid(FlagsGrid::class)->setSource(new EloquentSource($this->flag));

        return $this->view('adm.flag')->with('grid', $grid);
    }
}
