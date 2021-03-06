<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;
    use HelperTrait;

    /**
     * Define custom actions here
     */

    public function login($name, $password)
    {
        $I = $this;
        $I->amOnPage('/Login');
        $I->fillField('name', $name);
        $I->fillField('password', $password);
        $I->click('button[type=submit]');
        $I->click('.dropdown-toggle', '.avatar');
        $I->see($name, '.dropdown-username');
    }
}
