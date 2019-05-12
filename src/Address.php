<?php
namespace ProfiCloS\Ares;

class Address
{

	/** @var string */
	public $street;

	/** @var string */
	public $streetNumber;

	/** @var string */
	public $city;

	/** @var string */
	public $postCode;

	/** @var string */
	public $country;

	public $parts = [
		'street' => null,
		'streetNumber' => null,
		'cityPart' => null,
		'cityPartNumber' => null
	];

}
