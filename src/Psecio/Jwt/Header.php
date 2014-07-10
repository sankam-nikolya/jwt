<?php

namespace Psecio\Jwt;

class Header
{
	private $type = 'JWT';
	private $algorithm = 'HS256';
	private $key;
	private $hashTypes = array(
		'HS256' => 'SHA256',
		'HS384' => 'SHA384',
		'HS512' => 'SHA512',
	);
	private $hashMethod = 'hmac';

	public function __construct($type = 'JWT', $algorithm = 'HS256', $key = null)
	{
		$this->setType($type);
		$this->setAlgorithm($algorithm);
		if ($key !== null) {
			$this->setKey($key);
		}
	}

	public function setType($type)
	{
		$this->type = $type;
	}
	public function getType()
	{
		return $this->type;
	}
	public function setAlgorithm($algorithm)
	{
		$this->algorithm = $algorithm;
	}
	public function getAlgorithm($resolve = false)
	{
		$algorithm = $this->algorithm;
		if ($resolve === true) {
			foreach ($this->hashTypes as $key => $algo) {
				if ($key === $algorithm) {
					return $this->hashTypes[$key];
				}
			}
		}
		return $algorithm;
	}
	public function setKey($key)
	{
		$this->key = $key;
	}
	public function getKey()
	{
		return $this->key;
	}

	public function __toString()
	{
		$data = $this->toArray();
		return json_encode($data);
	}

	public function toArray()
	{
		$data = array(
			'typ' => $this->getType(),
			'alg' => $this->getAlgorithm()
		);
		return $data;
	}
}