<?php

namespace PrestaShop\FunctionalTest;

/**
 * This test does the following:
 *
 * - Create and enable 2 tax rules, OldFrenchVat and NewFrenchVat
 * - Create a Tax Rule Group that applies OldFrenchVat to Country the country with id 1
 * - Create a Tax Rule Group that applies OldFrenchVat to all countries
 * - Create a Tax Rule Group that applies both OldFrenchVat and NewFrenchVat, combined, to all countries
 * 
 */

class TaxManagementTest extends \PrestaShop\TestCase\LazyTestCase
{
	public static function setupBeforeClass()
	{
		parent::setupBeforeClass();
		
		static::getShop()->getBackOfficeNavigator()->login();
	}

	public function testAccessToAdminTaxes()
	{
		$shop = static::getShop();
		$shop->getBackOfficeNavigator()
		->visit('AdminTaxes')
		->ensureElementIsOnPage('#PS_TAX_on');
	}

	public function testTaxCanBeDisabled()
	{
		static::getShop()->getTaxManager()->enableTax(false);
	}

	public function testTaxCanBeEnabled()
	{
		static::getShop()->getTaxManager()->enableTax(true);
	}

	public function testTaxDisplayInTheShoppingCartCanBeDisabled()
	{
		static::getShop()->getTaxManager()->enableTaxInTheShoppingCart(false);
	}

	public function testTaxDisplayInTheShoppingCartCanBeEnabled()
	{
		static::getShop()->getTaxManager()->enableTaxInTheShoppingCart(true);
	}

	public function testEcotaxCanBeEnabled()
	{
		static::getShop()->getTaxManager()->enableEcotax(true);
	}

	public function testEcotaxTaxRulesGroupCanBeSet()
	{
		static::getShop()->getTaxManager()->setEcotaxTaxGroup(1);
	}

	public function testEcotaxCanBeDisabled()
	{
		static::getShop()->getTaxManager()->enableEcotax(false);
	}

	public function testBaseTaxOnInvoiceAddress()
	{
		static::getShop()->getTaxManager()->baseTaxOn('invoice address');
	}

	public function testBaseTaxOnDeliveryAddress()
	{
		static::getShop()->getTaxManager()->baseTaxOn('delivery address');
	}

	public function taxRules()
	{
		return [
			['OldFrenchVat', 19.6, true],
			['NewFrenchVat', 20, true]
		];
	}

	/**
	 * @dataProvider taxRules
	 */
	public function testTaxRuleCreation($name, $rate, $enabled)
	{
		$shop = static::getShop();
		$id_tax = $shop->getTaxManager()->createTaxRule($name, $rate, $enabled);
		$shop
		->getDataStore()
		->set("tax_rules.$name", ['id_tax' => $id_tax, 'rate' => $rate]);
	}

	public function taxRuleGroups()
	{
		$groups = [];
		
		$groups[] = [
			'One Rate For One Country with Ziprange',
			[
				[
					'id_tax' => 'OldFrenchVat',
					'country' => ['id' => 21, 'ziprange' => '00000-12345'],
					'behavior' => '+'
				]
			],
			true
		];

		$groups[] = [
			'One Rate For 2 States In One Country',
			[
				[
					'id_tax' => 'OldFrenchVat',
					'country' => ['id' => 21, 'state' => [2, 3]],
					'behavior' => '+'
				]
			],
			true
		];

		$groups[] = [
			'One Rate For One State In One Country',
			[
				[
					'id_tax' => 'OldFrenchVat',
					'country' => ['id' => 21, 'state' => 2],
					'behavior' => '!'
				]
			],
			true
		];

		$groups[] = [
			'One Rate For 2 Countries',
			[
				[
					'id_tax' => 'OldFrenchVat',
					'country' => [1, 2],
					'behavior' => '!'
				]
			],
			true
		];

		$groups[] = [
			'One Rate For One Country',
			[
				[
					'id_tax' => 'OldFrenchVat',
					'country' => 1,
					'behavior' => '!'
				]
			],
			true
		];

		$groups[] = [
			'2 Rates For One Country, combined',
			[
				[
					'id_tax' => 'OldFrenchVat',
					'country' => 1,
					'behavior' => '+'
				],
				[
					'id_tax' => 'NewFrenchVat',
					'country' => 1,
					'behavior' => '+'
				]
			],
			true
		];

		$groups[] = [
			'Same Single Rate For Everyone',
			[
				[
					'id_tax' => 'OldFrenchVat',
					'country' => null,
					'behavior' => '!'
				]
			],
			true
		];

		$groups[] = [
			'Same Cumulative Rate For Everyone',
			[
				[
					'id_tax' => 'OldFrenchVat',
					'country' => null,
					'behavior' => '+'
				],
				[
					'id_tax' => 'NewFrenchVat',
					'country' => null,
					'behavior' => '+'
				]
			],
			true
		];

		return $groups;
	}

	/**
	 * @dataProvider taxRuleGroups
	 */
	public function testTaxRuleGroupCreation($name, array $taxRules, $enabled)
	{
		$shop = static::getShop();


		// Retrieve the id_tax's of the tax rules created earlier
		foreach ($taxRules as $n => $taxRule)
		{
			$tax_name = $taxRules[$n]['id_tax'];
			$taxRules[$n]['id_tax'] = $shop->getDataStore()->get("tax_rules.$tax_name")['id_tax'];
			// Sanity check, errors should have been caught earlier
			$this->assertInternalType('int', $taxRules[$n]['id_tax']);
			$this->assertGreaterThan(0, $taxRules[$n]['id_tax']);
		}

		$tm = $shop->getTaxManager();
		$id_tax_rules_group = $tm->createTaxRuleGroup($name, $taxRules, $enabled);

		$shop
		->getDataStore()
		->set("tax_rules_groups.$name", ['id_tax_rules_group' => $id_tax_rules_group]);
	}

	public function testTaxRuleGroupsCanBeDeleted()
	{
		foreach ($this->getShop()->getDataStore()->get('tax_rules_groups') as $name => $data)
		{
			$this->getShop()->getTaxManager()->deleteTaxRuleGroup($data['id_tax_rules_group']);
		}
	}

	public function testTaxRulesCanBeDeleted()
	{
		foreach ($this->getShop()->getDataStore()->get('tax_rules') as $name => $data)
		{
			$this->getShop()->getTaxManager()->deleteTaxRule($data['id_tax']);
		}
	}
}
