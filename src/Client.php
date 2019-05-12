<?php
namespace ProfiCloS\Ares;

use ProfiCloS\Validators;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class Client
{

	protected $url = 'http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_bas.cgi';

	/**
	 * @param string $in Identification Number (IČO)
	 * @return Entity|null
	 * @throws InvalidArgumentException
	 * @throws ServerException
	 * @throws ParseException
	 */
	public function get($in): ?Entity
	{

		if (!Validators::isIdentificationNumber($in)) {
			throw new InvalidArgumentException('Identification Number (IČO) is not valid');
		}

		try {

			$guzzleClient = new GuzzleClient();
			$response = $guzzleClient->get($this->url, [
				'query' => [
					'ico' => $in
				]
			]);
			$responseContent = $response->getBody()->getContents();

			$parsedResponse = $this->parseResponseXML($responseContent);
			if (!$parsedResponse) {
				throw new ParseException('Cannot parse response XML');
			}

			return $this->createEntity($parsedResponse);

		} catch (ClientException $e) {
			throw new ServerException($e->getMessage());
		}
	}

	protected function createEntity($data): Entity
	{
		$companyData = $data['VBAS'];
		$addressData = $companyData['AA'];

		$entity = new Entity();
		$entity->ico = $companyData['ICO'];
		$entity->dic = $companyData['DIC'] ?? null;
		$entity->company = $companyData['OF'];

		$address = new Address();
		$address->city = $addressData['N'] ?? null;
		$address->postCode = $addressData['PSC'] ?? null;
		$address->country = $addressData['NS'] ?? null;
		$address->parts['street'] = $addressData['NU'] ?? null;
		$address->parts['streetNumber'] = $addressData['CO'] ?? null;
		$address->parts['cityPart'] = $addressData['NMC'] ?? null;
		$address->parts['cityPartNumber'] = $addressData['CD'] ?? null;

		$address->street = $address->parts['street'];
		if ($address->street === null && $address->city !== null) {
			$address->street = $address->city;
		}

		$address->streetNumber = $address->parts['cityPartNumber'];
		if (!$address->streetNumber) {
			$address->streetNumber = $address->parts['streetNumber'];
		} else if ($address->parts['streetNumber']) {
			$address->streetNumber .= '/' . $address->parts['streetNumber'];
		}


		$entity->address = $address;

		return $entity;
	}

	/**
	 * @param $content
	 * @return mixed|null
	 * @throws ParseException
	 */
	protected function parseResponseXML($content)
	{
		if (!$content) {
			return null;
		}
		$xml = @simplexml_load_string($content);
		if (!$xml) {
			return null;
		}
		$namespaces = $xml->getDocNamespaces();
		$result = $xml->children($namespaces['are']);
		$data = $result->children($namespaces['D']);

		try {
			$json = Json::encode($data);
			return Json::decode($json, Json::FORCE_ARRAY);
		} catch (JsonException $e) {
			throw new ParseException('Cannot transform data');
		}
	}

}
