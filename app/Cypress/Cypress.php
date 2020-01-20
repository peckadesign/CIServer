<?php declare(strict_types = 1);

namespace CI\Cypress;

class Cypress
{

	/**
	 * @var string
	 */
	private $input;

	/**
	 * @var int
	 */
	private $errors;


	public function __construct(string $input)
	{
		$this->input = $input;
	}


	public function getErrors(): int
	{
		if ($this->errors === NULL) {
			$this->parse();
		}

		return (int) $this->errors;
	}


	private function parse()
	{
		$this->errors = 0;

		\preg_match('/(\d*) of (\d*) failed/', $this->input, $matches);

		if (isset($matches[0])) {
			$this->errors = (int) $matches[0];
		}
	}
}
