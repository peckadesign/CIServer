<?php

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

	public function render()
	{
		$this->template->setFile( __DIR__ . '/Control.latte' );
		$this->template->render();
	}


	public function __construct(
		CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		CI\GitHub\RepositoriesRepository $repositoriesRepository
	) {
		$this->createTestServersRepository = $createTestServersRepository;
		$this->repositoriesRepository = $repositoriesRepository;

	}


	public function createComponentDataGrid()
	{
		$grid = new Nextras\Datagrid\Datagrid;
		$grid->addColumn( 'id' )->enableSort();
		$grid->addColumn( 'repository' )->enableSort();
		$grid->addColumn( 'branchName' )->enableSort();
		$grid->addColumn( 'commit' )->enableSort();
		$grid->addColumn( 'finish' )->enableSort();
		$grid->setDataSourceCallback( [ $this, 'getDataSource' ] );
		$grid->setPagination( 10, [ $this, 'getDataSourceSum' ] );
		$grid->addCellsTemplate( __DIR__ . '/../../../../vendor/nextras/datagrid/bootstrap-style/@bootstrap3.datagrid.latte' );
		$grid->addCellsTemplate( __DIR__ . '/../../../../vendor/nextras/datagrid/bootstrap-style/@bootstrap3.extended-pagination.datagrid.latte' );
		$grid->addCellsTemplate( __DIR__ . '/Cells.latte' );

		$grid->setFilterFormFactory( function () {
			$form = new Nette\Forms\Container;

			$repositories = $this->repositoriesRepository->findAll()->orderBy( 'name' )->fetchPairs( 'id', 'name' );
			$form->addSelect( 'repository', 'repository', $repositories )->setPrompt( ' ' );
//			$form->addText( 'branchName' );
			$form->addSubmit( 'filter', 'Filter data' )->getControlPrototype()->class   = 'btn btn-primary';
			$form->addSubmit( 'cancel', 'Cancel filter' )->getControlPrototype()->class = 'btn';

			return $form;
		} );

		return $grid;
	}


	public function getDataSource( $filter, $order, Nette\Utils\Paginator $paginator = NULL ) : array
	{
		$selection = $this->prepareDataSource( $filter, $order );

		$selection = iterator_to_array( $selection );

		return $selection;
	}


	public function getDataSourceSum( $filter, $order ) : int
	{
		return $this->prepareDataSource( $filter, $order )->count();
	}


	private function prepareDataSource( array $filter = [], $order ) : Nextras\Orm\Collection\ICollection
	{
		$filters = [];
		foreach ( $filter as $k => $v ) {
			if( ! empty($v)){
				$filters[ $k ] = $v;
			}
		}

		$selection = $this->createTestServersRepository->findBy($filters)->orderBy('id', Nextras\Orm\Collection\ICollection::DESC);

		return $selection;
	}

}