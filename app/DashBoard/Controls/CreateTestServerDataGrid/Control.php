<?php declare(strict_types = 1);

namespace CI\DashBoard\Controls\CreateTestServerDataGrid;

use CI;
use Nette;
use Nextras;


class Control extends Nette\Application\UI\Control
{

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;

	/**
	 * @var \CI\GitHub\RepositoriesRepository
	 */
	private $repositoriesRepository;

	public function __construct(
		CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		CI\GitHub\RepositoriesRepository $repositoriesRepository
	) {
		$this->createTestServersRepository = $createTestServersRepository;
		$this->repositoriesRepository = $repositoriesRepository;

	}


	public function render()
	{
		$this->template->setFile(__DIR__ . '/Control.latte');
		$this->template->render();
	}


	public function createComponentDataGrid()
	{
		$grid = new Nextras\Datagrid\Datagrid;
		$grid->addColumn('id')->enableSort();
		$grid->addColumn('repository', 'Projekt')->enableSort();
		$grid->addColumn('branchName', 'Větev')->enableSort();
		$grid->addColumn('commit')->enableSort();
		$grid->addColumn('finish', 'Sestaveno')->enableSort();
		$grid->setDataSourceCallback([$this, 'getDataSource']);
		$grid->setPagination(10, [$this, 'getDataSourceSum']);
		$grid->addCellsTemplate(__DIR__ . '/Cells.latte');

		$grid->setFilterFormFactory( function () {
			$form = new Nette\Forms\Container;

			$repositories = $this->repositoriesRepository->findAll()->orderBy('name')->fetchPairs('id', 'name');
			$form->addSelect('repository', 'repository', $repositories)->setPrompt(' --- ');
			$form->addText('branchName')->setAttribute('placeholder', 'přesná shoda');
			$form->addSubmit('filter', 'Použít filtr')->getControlPrototype()->class = 'btn btn-primary';
			$form->addSubmit('cancel', 'Zrušit filtr')->getControlPrototype()->class = 'btn';

			return $form;
		} );

		return $grid;
	}


	public function getDataSource($filter, $order, Nette\Utils\Paginator $paginator = NULL) : array
	{
		$selection = $this->prepareDataSource($filter, $order);
		$selection = $selection->limitBy($paginator->getItemsPerPage(), $paginator->getOffset());
		$selection = iterator_to_array($selection);

		return $selection;
	}


	public function getDataSourceSum($filter, $order) : int
	{
		return $this->prepareDataSource($filter, $order)->count();
	}


	private function prepareDataSource(array $filter = [], $order) : Nextras\Orm\Collection\ICollection
	{
		$filters = [];
		foreach ( $filter as $k => $v ) {
			if ( ! empty( $v ) ) {
				$filters[ $k ] = $v;
			}
		}

		if ( is_array( $order ) ) {
			$selection = $this->createTestServersRepository
				->findBy($filters)
				->orderBy($order[0], $order[1] === 'DESC' ? Nextras\Orm\Collection\ICollection::DESC : Nextras\Orm\Collection\ICollection::ASC)
			;
		}
		else {
			$selection = $this->createTestServersRepository
				->findBy($filters)
				->orderBy('id', Nextras\Orm\Collection\ICollection::DESC)
			;
		}

		return $selection;
	}

}
