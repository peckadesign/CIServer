<?php declare(strict_types = 1);

namespace CI\DashBoard\Controls\RabbitMQStatus;

interface IFactory
{

	public function create() : Control;
}
