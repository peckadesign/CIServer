<?php

namespace CI\Tap;

class Tap
{

	/**
	 * @var string
	 */
	private $input;

	private $succeeded;

	private $failed;


	public function __construct(string $input)
	{
		$this->input = $input;
	}


	public function getSucceeded()
	{
		if ($this->succeeded === NULL) {
			$this->parse();
		}

		return $this->succeeded;
	}


	public function getFailed()
	{
		if ($this->failed === NULL) {
			$this->parse();
		}

		return $this->failed;
	}


	private function parse()
	{
		$this->succeeded = $this->failed = 0;
		foreach (explode("\n", $this->input) as $line) {
			$line = strtoupper($line);
			if (strpos($line, 'OK') === 0) {
				$this->succeeded++;
			}
			if (strpos($line, 'NOT OK') === 0) {
				$this->failed++;
			}
		}
	}

}
