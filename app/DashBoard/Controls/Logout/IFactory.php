<?php declare(strict_types = 1);

namespace CI\DashBoard\Controls\Logout;

interface IFactory
{
	public function create() : Control;
}
